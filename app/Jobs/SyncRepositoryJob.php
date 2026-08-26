<?php

namespace App\Jobs;

use App\Models\Repository;
use App\Models\RepositoryIndexation;
use App\Models\RepositorySource;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class SyncRepositoryJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;
    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 900;
    private const ALLOWED_EXTENSIONS = [
        'php',
        'js',
        'ts',
        'jsx',
        'tsx',
        'py',
        'java',
        'go',
        'rb',
        'md',
        'json',
        'yaml',
        'yml',
        'vue',
        'css',
        'scss',
        'sql',
    ];
    private const IGNORED_PATHS = [
        'node_modules/',
        'vendor/',
        '.git/',
        'dist/',
        'build/',
        'storage/',
        '.next/',
        '__pycache__/',
        'coverage/',
        'public/build/',
    ];
    private const MAX_FILE_SIZE = 1_000_000;
    private const CACHE_TTL_HOURS = 2;

    public function __construct(public Repository $repository, public User $user)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $connection = $this->user->githubConnection;
        if (!$connection) {
            Log::error("SyncRepositoryJob: No GitHub connection found for user {$this->user->id}");
            return;
        }

        if ($connection->isExpired()) {
            Log::error("SyncRepositoryJob: GitHub token has expired for user {$this->user->id}");
            return;
        }
        $indexation = RepositoryIndexation::create(
            [
                'repository_id' => $this->repository->id,
                'status' => 'running',
                'trigger_type' => 'initial',
                'started_at' => now(),
            ]
        );
        try {
            $token = $connection->access_token;
            $files = $this->fetchRepositoryTree($token);
            $indexation->update(['source_discovered' => count($files)]);

            Log::info("SyncRepositoryJob: Found " . count($files) . " files for repository {$this->repository->id}");

            foreach ($files as $file) {
                $this->processFile($file, $token, $indexation);
            }
            $this->repository->update(['last_synced_at' => now()]);
            $indexation->markAsCompleted();
            Log::info("SyncRepositoryJob: Completed syncing repository {$this->repository->id}");

        } catch (\Exception $e) {
            Log::error("SyncRepositoryJob: Error syncing repository {$this->repository->id}: " . $e->getMessage());
            $indexation->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    private function processFile(array $file, string $token, RepositoryIndexation $indexation): void
    {
        $content = $this->fetchFileContent($token, $file['path']);

        if ($content === null) {
            $indexation->incrementProgress();
            return;
        }

        $contentHash = hash('sha256', $content);
        $existingSource = RepositorySource::where('repository_id', $this->repository->id)
            ->where('source_type', 'file')
            ->where('source_identifier', $file['path'])
            ->first();
        if ($existingSource && $existingSource->content_hash === $contentHash) {
            $indexation->incrementProgress();
            return;
        }

        $source = RepositorySource::updateOrCreate(
            [
                'repository_id' => $this->repository->id,
                'source_type' => 'file',
                'source_identifier' => $file['path'],
            ],
            [
                'language' => $this->detectLanguage($file['path']),
                'content_hash' => $contentHash,
            ]
        );
        Cache::put(
            $this->contentCacheKey($source->id),
            $content,
            now()->addHours(self::CACHE_TTL_HOURS)
        );
        ChunkRepositoryJob::dispatch($source);
        $indexation->incrementProgress();
    }

    //Fetch the repository file tree from GitHub.
    private function fetchRepositoryTree(string $token): array
    {
        $response = Http::withToken($token)
            ->get("https://api.github.com/repos/{$this->repository->owner}/{$this->repository->name}/git/trees/HEAD", [
                'recursive' => 1,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Unable to fetch repository tree: ' . $response->body());
        }

        $tree = $response->json('tree', []);

        return collect($tree)
            ->filter(fn($item) => $item['type'] === 'blob')
            ->filter(fn($item) => $this->isAllowedFile($item['path']))
            ->values()
            ->all();
    }

    private function fetchFileContent(string $token, string $path): ?string
    {
        $response = Http::withToken($token)
            ->get("https://api.github.com/repos/{$this->repository->owner}/{$this->repository->name}/contents/{$path}");
        if ($response->failed()) {
            return null;
        }
        $data = $response->json();

        if (($data['size'] ?? 0) > self::MAX_FILE_SIZE) {
            return null;
        }

        if (($data['encoding'] ?? null) !== 'base64') {
            return null;
        }
        return base64_decode($data['content']);
    }


    //Check if a file is allowed for indexing.
    private function isAllowedFile(string $path): bool
    {
        foreach (self::IGNORED_PATHS as $ignored) {
            if (str_contains($path, $ignored)) {
                return false;
            }
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        return in_array($extension, self::ALLOWED_EXTENSIONS, true);
    }

    private function detectLanguage(string $path): ?string
    {
        return pathinfo($path, PATHINFO_EXTENSION) ?: null;
    }

    public static function contentCacheKey(string $sourceId): string
    {
        return "source_content:{$sourceId}";
    }
}

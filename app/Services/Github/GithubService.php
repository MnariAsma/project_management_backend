<?php

namespace App\Services\Github;

use App\Models\GithubConnection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GithubService
{
    public function __construct(private readonly GithubConnection $connection) {
    }
    public function listRepositories(int $perPage = 100): array
    {
        $response = $this->request()->get('/user/repos', [
            'per_page' => $perPage,
            'sort' => 'updated',
            'affiliation' => 'owner,collaborator,organization_member',
        ]);

        $this->throwIfFailed($response, 'Impossible to get repositories');

        return $response->json();
    }


    public function getRepositoryById(string $githubRepoId): array
    {
        $response = $this->request()->get("/repositories/{$githubRepoId}");

        $this->throwIfFailed($response, "repository {$githubRepoId} doesn't exist or inaccessible");

        return $response->json();
    }

    public function getRepositoriesByIds(array $githubRepoIds): array
    {
        $responses = Http::pool(fn($pool) => collect($githubRepoIds)
            ->map(fn($id) => $pool->as((string) $id)
                ->withToken($this->connection->access_token)
                ->acceptJson()
                ->get(config('services.github.api_url') . "/repositories/{$id}"))
            ->all());

        $repositories = [];

        foreach ($githubRepoIds as $id) {
            $response = $responses[(string) $id];

            $this->throwIfFailed($response, "repo {$id} doesn't existe.");

            $repositories[$id] = $response->json();
        }

        return $repositories;
    }

    public function formatRepository(array $repo): array
    {
        return [
            'github_repo_id' => $repo['id'],
            'name' => $repo['name'],
            'full_name' => $repo['full_name'],
            'owner' => $repo['owner']['login'],
            'private' => $repo['private'],
            'default_branch' => $repo['default_branch'],
            'html_url' => $repo['html_url'],
            'github_url' => $repo['html_url'],
            'description' => $repo['description'],
            'github_created_at' => $repo['created_at'] ?? null,
            'github_updated_at' => $repo['updated_at'] ?? null,
        ];
    }
    private function request()
    {
        return Http::withToken($this->connection->access_token)
            ->acceptJson()
            ->baseUrl(config('services.github.api_url'));
    }
    private function throwIfFailed(Response $response, string $message): void
    {
        if ($response->failed()) {
            throw new GithubApiException(
                message: $message,
                status: $response->status() === 404 ? 422 : $response->status(),
                githubMessage: $response->json('message'),
            );
        }
    }
}
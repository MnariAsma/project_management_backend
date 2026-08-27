<?php

namespace App\Jobs;

use App\Models\CodeChunk;
use App\Models\RepositorySource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Log;
use Throwable;

class GenerateEmbeddingsJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;
    public int $tries = 3;
    public int $backoff = 20;
    public int $timeout = 180;

    public function __construct(
        public RepositorySource $source,
    ) {}

    public function handle(): void
    {
        $chunks = $this->source->codeChunks()
            ->where('embedding_status', 'pending')
            ->get();
        if ($chunks->isEmpty()) {
            Log::info("GenerateEmbeddingsJob: no pending chunks for source {$this->source->id}");
            return;
        }
        $batchSize = (int) config('services.embedding.batch_size', 8);
        foreach ($chunks->chunk($batchSize) as $batch) {
            $this->processBatch($batch);
        }
    }

    private function processBatch($batch): void
    {
        $inputs = $batch->map(fn (CodeChunk $chunk) => $chunk->chunk_content)->values()->all();
        try {
            $response = Http::timeout(120)
                ->post(config('services.embedding.url') . '/embed', [
                    'inputs' => $inputs,
                ]);

            if ($response->failed()) {
                Log::error("GenerateEmbeddingsJob: failed to call embeddings service ({$response->status()}): {$response->body()}");
                $batch->each(fn (CodeChunk $chunk) => $chunk->update(['embedding_status' => 'failed']));
                return;
            }
            $embeddings = $response->json();
            $expectedDimensions = (int) config('services.embedding.dimensions');

            foreach ($batch->values() as $index => $chunk) {
                $vector = $embeddings[$index] ?? null;

                if ($vector === null || count($vector) !== $expectedDimensions) {
                    Log::warning("GenerateEmbeddingsJob: invalid embedding for chunk {$chunk->id}");
                    $chunk->update(['embedding_status' => 'failed']);
                    continue;
                }
                $chunk->update([
                    'embedding' => $vector,
                    'embedding_status' => 'completed',
                ]);
            }
            Log::info("GenerateEmbeddingsJob: {$batch->count()} embeddings generated for source {$this->source->id}");

        } catch (Throwable $e) {
            Log::error("GenerateEmbeddingsJob: exception occurred while processing batch: {$e->getMessage()}");
            $batch->each(fn (CodeChunk $chunk) => $chunk->update(['embedding_status' => 'failed']));
            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error("GenerateEmbeddingsJob: definitively failed for source {$this->source->id}: {$exception->getMessage()}");
        $this->source->codeChunks()
            ->where('embedding_status', 'pending')
            ->update(['embedding_status' => 'failed']);
    }

}

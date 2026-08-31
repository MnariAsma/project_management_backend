<?php

namespace App\Services\chat;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class CodeSearchService
{
    public function __construct(
        private EmbeddingService $embeddingService,
    ) {}

    public function search(Project $project, string $question, int $topK = 5): array
    {
        $vector = $this->embeddingService->embed($question);
        if ($vector === null) {
            return [];
        }
        $repositoryIds = $project->repositories()->pluck('id');
        if ($repositoryIds->isEmpty()) {
            return [];
        }

        $vectorString = '[' . implode(',', $vector) . ']';
        $placeholders = implode(',', array_fill(0, $repositoryIds->count(), '?'));
        $results = DB::select("
            SELECT
                cc.chunk_content,
                cc.metadata,
                cc.embedding <=> ?::extensions.vector AS distance
            FROM code_chunks cc
            INNER JOIN repository_sources rs ON rs.id = cc.repository_source_id
            WHERE rs.repository_id IN ($placeholders)
              AND cc.embedding_status = 'completed'
              AND cc.deleted_at IS NULL
              AND rs.deleted_at IS NULL
            ORDER BY distance ASC
            LIMIT ?
        ", [$vectorString, ...$repositoryIds->all(), $topK]);

        return array_map(function ($row) {
            $metadata = json_decode($row->metadata, true) ?? [];

            return [
                'content' => $row->chunk_content,
                'source_path' => $metadata['source_path'] ?? null,
                'language' => $metadata['language'] ?? null,
                'start_line' => $metadata['start_line'] ?? null,
                'end_line' => $metadata['end_line'] ?? null,
                'distance' => (float) $row->distance,
            ];
        }, $results);
    }
}
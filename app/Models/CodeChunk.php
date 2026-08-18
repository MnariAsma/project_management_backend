<?php

namespace App\Models;

use Illuminate\Console\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['repository_source_id', 'chunk_content', 'token_count', 'chunk_index', 'metadata', 'embedding_status', 'embedding'])]
class CodeChunk extends Model
{
    use SoftDeletes, HasFactory, HasUuids;

    protected $casts = [
        'metadata' => 'array',
        'embedding' => Vector::class,
    ];

    public function repositorySource()
    {
        return $this->belongsTo(RepositorySource::class);
    }

    public function repository()
    {
        return $this->repositorySource->repository ?? null;
    }

    public function scopeEmbeddingStatus($query, string $status)
    {
        return $query->where('embedding_status', $status);
    }

    public function scopeEmbedded($query)
    {
        return $query->where('embedding_status', 'completed')
            ->whereNotNull('embedding');
    }

    public function scopePending($query)
    {
        return $query->where('embedding_status', 'pending');
    }

}

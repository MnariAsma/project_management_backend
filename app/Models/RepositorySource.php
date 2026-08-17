<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['repository_id', 'source_type', 'source_identifier', 'content_hash', 'last_indexed_at'])]
class RepositorySource extends Model
{
    use SoftDeletes, HasFactory, HasUuids;

    protected $casts = [
        'last_indexed_at' => 'datetime',
    ];
    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function codeChunks(): HasMany
    {
        return $this->hasMany(CodeChunk::class);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('source_type', $type);
    }

    public function scopeChanged($query, string $newHash)
    {
        return $query->where('content_hash', '!=', $newHash)
            ->orWhereNull('content_hash');
    }
}

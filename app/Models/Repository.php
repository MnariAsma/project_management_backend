<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['project_id','github_repo_id','name','owner','github_url','github_created_at','github_updated_at','last_synced_at'])]
class Repository extends Model
{
    use SoftDeletes,HasUuids,HasFactory;
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function indexation(): HasMany
    {
        return $this->hasMany(RepositoryIndexation::class);
    }

}

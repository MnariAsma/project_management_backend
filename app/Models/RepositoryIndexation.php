<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;


#[Fillable(['repository_id','status','trigger_type','sources_discovered','sources_processed','chunks_created','error_message','started_at','completed_at'])]
class RepositoryIndexation extends Model
{
    use SoftDeletes, HasFactory, HasUuids;

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

        public function markAsRunning(): void
    {
        $this->update(['status' => 'running', 'started_at' => now()]);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed', 'completed_at' => now()]);
    }

    public function markAsFailed(string $message): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $message,
            'completed_at' => now(),
        ]);
    }
}

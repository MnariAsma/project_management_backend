<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class conversationMessage extends Model
{
   use HasUuids, HasFactory;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'sources',
        'tokens_used',
    ];

    protected $casts = [
        'sources' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}

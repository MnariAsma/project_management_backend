<?php

namespace App\Models;

use Illuminate\Console\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'project_id', 'access_token', 'refresh_token', 'token_expires_at'])]
#[Hidden(['refresh_token', 'access_token'])]
class GithubConnection extends Model
{
    use SoftDeletes, HasUuids, HasFactory;

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
        ];
    }
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }



}

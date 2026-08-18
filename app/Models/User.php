<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['company_id', 'name', 'email', 'role', 'avatar_url', 'github_id', 'github_username'])]
#[Hidden(['remember_token'])]
class User extends Authenticatable
{

    use HasFactory, Notifiable, HasUuids, SoftDeletes, HasApiTokens;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
        ];
    }
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot('id')
            ->withTimestamps();
    }

    public function githubConnections(): HasMany
    {
        return $this->hasMany(GithubConnection::class);
    }
}

<?php

namespace App\Services\Auth;

use App\Models\User;

class TokenService
{
    public const ACCESS_TOKEN_NAME = 'access_token';
    public const REFRESH_TOKEN_NAME = 'refresh_token';
    public const ABILITY_ACCESS_API = 'access-api';
    public const ABILITY_ISSUE_ACCESS_TOKEN = 'issue-access-token';


    public function issuePairFor(User $user): array
    {
        $user->tokens()
            ->whereIn('name', [self::ACCESS_TOKEN_NAME, self::REFRESH_TOKEN_NAME])
            ->delete();

        $accessTokenExpiresAt = now()->addMinutes((int) config('sanctum.access_token_ttl'));
        $refreshTokenExpiresAt = now()->addMinutes((int) config('sanctum.refresh_token_ttl'));

        $accessToken = $user->createToken(
            self::ACCESS_TOKEN_NAME,
            [self::ABILITY_ACCESS_API],
            $accessTokenExpiresAt
        );

        $refreshToken = $user->createToken(
            self::REFRESH_TOKEN_NAME,
            [self::ABILITY_ISSUE_ACCESS_TOKEN],
            $refreshTokenExpiresAt
        );

        return [
            'access_token' => $accessToken->plainTextToken,
            'access_token_expires_at' => $accessTokenExpiresAt->toIso8601String(),
            'refresh_token' => $refreshToken->plainTextToken,
            'refresh_token_expires_at' => $refreshTokenExpiresAt->toIso8601String(),
        ];
    }

    public function revokeAllFor(User $user): void
    {
        $user->tokens()
            ->whereIn('name', [self::ACCESS_TOKEN_NAME, self::REFRESH_TOKEN_NAME])
            ->delete();
    }
}
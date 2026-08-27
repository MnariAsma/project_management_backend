<?php

namespace App\Http\Controllers;

use App\Services\Auth\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Auth', description: 'Token lifecycle')]
class TokenController extends Controller
{
    public function __construct(private readonly TokenService $tokens)
    {
    }
    #[OA\Post(
        path: '/api/auth/refresh',
        summary: 'Refresh the access token',
        description: 'Requires a valid, non-expired refresh token sent as Bearer token. Rotates both tokens.',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'New token pair issued'),
            new OA\Response(response: 401, description: 'Refresh token missing, invalid or expired'),
        ]
    )]
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'message' => 'Token refreshed.',
            ...$this->tokens->issuePairFor($user),
        ]);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Revoke current auth tokens',
        tags: ['Auth'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logged out')
            ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $this->tokens->revokeAllFor($request->user());

        return response()->json(['message' => 'Logged out.']);
    }

}

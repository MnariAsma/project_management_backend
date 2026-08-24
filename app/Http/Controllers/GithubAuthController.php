<?php

namespace App\Http\Controllers;

use App\Models\GithubConnection;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GithubProvider;
use Laravel\Socialite\Two\InvalidStateException;
use OpenApi\Attributes as OA;
use Throwable;

#[OA\Tag(name: 'Auth - GitHub', description: 'User authentication via GitHub')]

#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum'
)]

class GithubAuthController extends Controller
{
    public function __construct(private readonly TokenService $tokens)
    {
    }

    #[OA\Get(
        path: '/api/auth/github/redirect',
        summary: 'Redirect to the GitHub authorization page',
        description: 'Redirects the user to the GitHub OAuth page to authorize the application.',
        tags: ['Auth - GitHub'],
        responses: [
            new OA\Response(
                response: 302,
                description: 'Redirect to github.com/login/oauth/authorize'
            ),
        ]
    )]
    public function redirect(): RedirectResponse
    {
        /** @var GithubProvider $driver */
        $driver = Socialite::driver('github');
        return $driver
            ->scopes(['user:email','repo'])
            ->stateless()
            ->redirect();
    }

    #[OA\Get(
        path: '/api/auth/github/callback',
        summary: 'GitHub OAuth callback',
        description: 'Creates/updates the user and returns a Sanctum token.',
        tags: ['Auth - GitHub'],
        parameters: [
            new OA\Parameter(name: 'code', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authentication successful',
            ),
            new OA\Response(response: 401, description: 'GitHub authentication failed'),
        ]
    )]
    public function callback(): JsonResponse
    {
        try {
            /** @var GithubProvider $driver */
            $driver = Socialite::driver('github');
            $githubUser = $driver->stateless()->user();
            // dd($githubUser);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'GitHub authentication failed.',
                'error' => $e->getMessage(),
            ], 401);
        }
        $user = User::updateOrCreate([
            'github_id' => $githubUser->getId(),
        ], [
            'name' => $githubUser->getNickname(),
            'company_id' => "01a03116-c8d1-702d-bc90-09434ad6327e",
            'email' => $githubUser->getEmail(),
            'avatar_url' => $githubUser->getAvatar(),
        ]);

        GithubConnection::updateOrCreate(
            ['user_id' => $user->id],
            [
                'access_token' => $githubUser->token ?? null,
                'refresh_token' => $githubUser->refreshToken ?? null,
                'token_expires_at' => isset($githubUser->expiresIn)
                    ? now()->addSeconds($githubUser->expiresIn)
                    : null,
            ]
        );

        $tokens = $this->tokens->issuePairFor($user);

        return response()->json([
            'message' => 'Authentication successful.',
            'user' => $user,
            ...$tokens,
        ]);
    }
}

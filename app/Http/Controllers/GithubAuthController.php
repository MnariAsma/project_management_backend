<?php

namespace App\Http\Controllers;

use App\Models\GithubConnection;
use App\Models\User;
use App\Services\Auth\TokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GithubProvider;
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
            ->scopes(['user:email', 'repo'])
            ->stateless()
            ->redirect();
    }
    #[OA\Get(
        path: '/api/auth/github/callback',
        summary: 'GitHub OAuth callback',
        description: 'Creates/updates the user, issues tokens, then redirects to the frontend callback page.',
        tags: ['Auth - GitHub'],
        parameters: [
            new OA\Parameter(name: 'code', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 302,
                description: 'Redirects to FRONTEND_URL/auth/callback with tokens in query params',
            ),
            
        ]
    )]
    public function callback(): RedirectResponse
    {
        $frontendUrl = rtrim(config('app.frontend_url'), '/');

        try {
            /** @var GithubProvider $driver */
            $driver = Socialite::driver('github');
            $githubUser = $driver->stateless()->user();
        } catch (Throwable $e) {
            return redirect($frontendUrl . '/login?error=' . urlencode('GitHub authentication failed.'));
        }

        $user = User::updateOrCreate([
            'github_id' => $githubUser->getId(),
        ], [
            'name'       => $githubUser->getNickname(),
            'company_id' => '01a0315d-6353-72ed-964e-b5390e62d234',
            'email'      => $githubUser->getEmail(),
            'avatar_url' => $githubUser->getAvatar(),
        ]);

        GithubConnection::updateOrCreate(
            ['user_id' => $user->id],
            [
                'access_token'    => $githubUser->token ?? null,
                'refresh_token'   => $githubUser->refreshToken ?? null,
                'token_expires_at' => isset($githubUser->expiresIn)
                    ? now()->addSeconds($githubUser->expiresIn)
                    : null,
            ]
        );
        $tokens = $this->tokens->issuePairFor($user);
        $userPayload = base64_encode(json_encode([
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'role'       => $user->role ?? null,
            'avatar_url' => $user->avatar_url,
            'github_id'  => $user->github_id,
            'company_id' => $user->company_id,
        ]));
        $query = http_build_query([
            'access_token'  => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'],
            'user'          => $userPayload,
        ]);
        return redirect($frontendUrl . '/auth/callback?' . $query);
    }
}

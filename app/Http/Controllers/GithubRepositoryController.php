<?php

namespace App\Http\Controllers;

use App\Services\Github\GithubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use OpenApi\Attributes as OA;

class GithubRepositoryController extends Controller
{

    #[OA\Get(
        path: '/api/github/repositories',
        summary: 'Get GitHub repositories',
        description: 'Retrieve the authenticated user\'s GitHub repositories using their connected GitHub account.',
        security: [['bearerAuth' => []]],
        tags: ['GitHub'],
        responses: [
            new OA\Response(response: 200, description: 'List of repositories'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(
                response: 422,
                description: 'GitHub account is not connected or validation failed',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'No GitHub account connected.'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $connection = $request->user()->githubConnection;

        if (!$connection) {
            return response()->json([
                'message' => 'No GitHub account connected',
            ], 422);
        }

        $github = new GithubService($connection);

        $repositories = collect($github->listRepositories())
            ->map(fn (array $repo) => $github->formatRepository($repo));

        return response()->json(['data' => $repositories]);
    }
}

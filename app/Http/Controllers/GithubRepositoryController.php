<?php

namespace App\Http\Controllers;

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
        security: [
            ['bearerAuth' => []]
        ],
        tags: ['GitHub'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Project created successfully',

            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 422,
                description: 'GitHub account is not connected or validation failed',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'No GitHub account connected.'
                        ),
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

        $response = Http::withToken($connection->access_token)
            ->get('https://api.github.com/user/repos', [
                'per_page' => 100,
                'sort' => 'updated',
                'affiliation' => 'owner,collaborator,organization_member',
            ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Impossible to get repositories',
                'error' => $response->json('message'),
            ], $response->status());
        }

        $repositories = collect($response->json())->map(fn($repo) => [
            'github_repo_id' => $repo['id'],
            'name' => $repo['name'],
            'full_name' => $repo['full_name'],
            'private' => $repo['private'],
            'default_branch' => $repo['default_branch'],
            'html_url' => $repo['html_url'],
            'description' => $repo['description'],
            'updated_at' => $repo['updated_at'],
        ]);

        return response()->json(['data' => $repositories]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Models\Project;
use App\Models\Repository;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class ProjectController extends Controller
{

    #[OA\Get(
        path: '/api/projects',
        summary: 'List all projects',
        tags: ['Projects'],
        security: [
            ['bearerAuth' => []]
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of projects'
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $projects = Project::query()
            ->where('company_id', $user->company_id)
            ->with('repositories')
            ->latest()
            ->get();

        return response()->json([
            'projects' => $projects,
        ]);
    }


    #[OA\Post(
        path: '/api/projects',
        summary: 'Create a project',
        tags: ['Projects'],
        security: [
            ['bearerAuth' => []]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                example: [
                    'name' => 'My Project',
                    'description' => 'My project description',
                    'repositories' => [
                        [
                            'github_repo_id' => '123456789',
                            'name' => 'my-project',
                            'owner' => 'john',
                            'github_url' => 'https://github.com/john/my-project',
                            'github_created_at' => '2026-08-20T10:00:00Z',
                            'github_updated_at' => '2026-08-23T15:30:00Z',
                        ]
                    ]
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Project created successfully'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error or GitHub account not connected'
            ),
        ]
    )]
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        if (!$user->githubConnection) {
            return response()->json([
                'message' => 'No GitHub account connected.',
            ], 422);
        }

        $project = DB::transaction(function () use ($validated, $user) {
            $project = Project::create([
                'company_id' => $user->company_id,
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . Str::random(6),
                'description' => $validated['description'] ?? null,
            ]);

            $project->users()->attach($user->id);

            foreach ($validated['repositories'] as $repo) {
                $repository = Repository::create([
                    'project_id' => $project->id,
                    'github_repo_id' => $repo['github_repo_id'],
                    'name' => $repo['name'],
                    'owner' => $repo['owner'],
                    'github_url' => $repo['github_url'],
                    'github_created_at' => $repo['github_created_at'] ?? null,
                    'github_updated_at' => $repo['github_updated_at'] ?? null,
                ]);

                //to do:create jobs for synchronization
            }

            return $project;
        });

        return response()->json([
            'message' => 'Project successfully created.',
            'project' => $project->load('repositories'),
        ], 201);
    }
}

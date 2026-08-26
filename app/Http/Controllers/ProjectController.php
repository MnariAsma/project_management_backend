<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Jobs\SyncRepositoryJob;
use App\Models\Project;
use App\Models\Repository;
use App\Services\Github\GithubService;
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
            new OA\Response(response: 200,description: 'List of projects'),
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
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                example: [
                    'name' => 'My Project',
                    'description' => 'My project description',
                    'repositories' => [
                        ['github_repo_id' => 123456789],
                    ],
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Project created successfully'),
            new OA\Response(response: 422, description: 'Validation error or GitHub account not connected'),
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
        $github = new GithubService($user->githubConnection);
        $githubRepoIds = collect($validated['repositories'])
            ->pluck('github_repo_id')
            ->all();

        $repoDetails = $github->getRepositoriesByIds($githubRepoIds);
        $project = DB::transaction(function () use ($validated, $repoDetails, $github, $user) {
            $project = Project::create([
                'company_id' => $user->company_id,
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . Str::random(6),
                'description' => $validated['description'] ?? null,
            ]);
            $project->users()->attach($user->id);
            foreach ($repoDetails as $repo) {
                $formatted = $github->formatRepository($repo);

                $repository = Repository::create([
                    'project_id' => $project->id,
                    'github_repo_id' => $formatted['github_repo_id'],
                    'name' => $formatted['name'],
                    'owner' => $formatted['owner'],
                    'github_url' => $formatted['github_url'],
                    'github_created_at' => $formatted['github_created_at'],
                    'github_updated_at' => $formatted['github_updated_at'],
                ]);

                SyncRepositoryJob::dispatch($repository, $user);
            }
            return $project;
        });

        return response()->json([
            'message' => 'Project successfully created.',
            'project' => $project->load('repositories'),
        ], 201);
    }

}

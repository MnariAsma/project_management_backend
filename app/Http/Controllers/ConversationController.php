<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\conversationMessage;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Conversations', description: 'Management of chatbot IA discussions by project')]
class ConversationController extends Controller
{
    #[OA\Get(
        path: '/api/projects/{project}/conversations',
        summary: 'list project conversations',
        description: 'return history of discussions for a project, sorted by last activity.',
        tags: ['Conversations'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'project',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of conversations',
            ),
            new OA\Response(response: 401, description: 'unauthenticated'),
        ]
    )]
    public function index(Project $project): JsonResponse
    {
        $conversations = $project->conversations()
            ->latest('updated_at')
            ->get(['id', 'project_id', 'user_id', 'title', 'created_at', 'updated_at']);

        $conversationIds = $conversations->pluck('id');

        if ($conversationIds->isNotEmpty()) {
            $lastMessages = conversationMessage::whereIn('conversation_id', $conversationIds)
                ->orderBy('conversation_id')
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('conversation_id')
                ->map(fn($messages) => $messages->first());

            $conversations->each(function ($conversation) use ($lastMessages) {
                $conversation->setRelation('lastMessage', $lastMessages->get($conversation->id));
            });
        }

        return response()->json(['data' => $conversations]);
    }
    #[OA\Post(
        path: '/api/projects/{project}/conversations',
        summary: 'create a new conversation',
        description: 'Creates a new empty chat for a project.',
        tags: ['Conversations'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'project',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Conversation created successfully',

            ),
            new OA\Response(response: 401, description: 'unauthenticated'),
        ]
    )]
    public function store(Request $request, Project $project): JsonResponse
    {
        $conversation = $project->conversations()->create([
            'user_id' => $request->user()->id,
            'title' => 'New Conversation',
        ]);

        return response()->json(['data' => $conversation], 201);
    }

    #[OA\Get(
        path: '/api/conversations/{conversation}/messages',
        summary: 'Retrieve messages from a conversation',
        description: 'Returns the complete history of questions and answers from a conversation.',
        tags: ['Conversations'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'conversation',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of messages',
            ),
            new OA\Response(response: 401, description: 'unauthenticated'),
        ]
    )]
    public function messages(Conversation $conversation): JsonResponse
    {
        return response()->json([
            'data' => $conversation->messages()->get(),
        ]);
    }

    #[OA\Delete(
        path: '/api/conversations/{conversation}',
        summary: 'Delete a conversation',
        description: 'Permanently deletes a conversation and its history.',
        tags: ['Conversations'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'conversation',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Conversation deleted',
            ),
            new OA\Response(response: 401, description: 'unauthenticated'),
        ]
    )]
    public function destroy(Conversation $conversation): JsonResponse
    {
        $conversation->delete();

        return response()->json(['message' => 'Conversation deleted.']);
    }
}
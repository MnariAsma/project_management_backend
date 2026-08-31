<?php
namespace App\Http\Controllers;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Conversation',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'project_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'user_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'title', type: 'string', example: 'Comment fonctionne l\'authentification ?'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'ConversationMessage',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'conversation_id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'role', type: 'string', enum: ['user', 'assistant']),
        new OA\Property(property: 'content', type: 'string'),
        new OA\Property(
            property: 'sources',
            type: 'array',
            nullable: true,
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'source_path', type: 'string', example: 'app/Http/Controllers/AuthController.php'),
                    new OA\Property(property: 'start_line', type: 'integer', example: 12),
                    new OA\Property(property: 'end_line', type: 'integer', example: 45),
                ]
            )
        ),
        new OA\Property(property: 'tokens_used', type: 'integer', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]

abstract class Controller
{
    //
}

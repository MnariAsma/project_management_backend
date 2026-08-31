<?php

namespace App\Services\chat;

use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Project;
use App\Services\chat\CodeSearchService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RagChatService
{
    public function __construct(
        private CodeSearchService $codeSearchService,
    ) {}

    public function ask(Conversation $conversation, string $question): ConversationMessage
    {
        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $question,
        ]);
        if ($conversation->messages()->count() === 1) {
            $conversation->update(['title' => Str::limit($question, 60)]);
        }
        $relevantChunks = $this->codeSearchService->search($conversation->project, $question);

        $systemPrompt = $this->buildSystemPrompt($relevantChunks);
        $conversationHistory = $this->buildConversationHistory($conversation);

        $answer = $this->callLlm($systemPrompt, $conversationHistory, $question);

        return $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $answer['content'],
            'sources' => array_map(fn ($chunk) => [
                'source_path' => $chunk['source_path'],
                'start_line' => $chunk['start_line'],
                'end_line' => $chunk['end_line'],
            ], $relevantChunks),
            'tokens_used' => $answer['tokens_used'] ?? null,
        ]);
    }

    private function buildSystemPrompt(array $chunks): string
    {
        if (empty($chunks)) {
            return "you are an AI assistant who helps developers understand a software project. "
                . "No relevant code excerpt was found for this question. "
                . "Inform the user that you do not have enough context to respond accurately.";
        }

        $context = collect($chunks)->map(function ($chunk, $i) {
            $location = $chunk['source_path']
                ? "{$chunk['source_path']} (lignes {$chunk['start_line']}-{$chunk['end_line']})"
                : 'source inconnue';

            return "### Extrait " . ($i + 1) . " — {$location}\n```{$chunk['language']}\n{$chunk['content']}\n```";
        })->implode("\n\n");

        return "You are an AI assistant who helps developers understand a software project. "
            . "Answer the user's question based only on the following code excerpts. "
            . "If the excerpts are not sufficient to answer with certainty, state this clearly. "
            . "Cite the relevant files in your response when appropriate.\n\n"
            . "## Source Code Context\n\n{$context}";
    }

    private function buildConversationHistory(Conversation $conversation): array
    {
        return $conversation->messages()
            ->latest()
            ->limit(10) 
            ->get()
            ->reverse()
            ->map(fn ($message) => [
                'role' => $message->role,
                'content' => $message->content,
            ])
            ->values()
            ->all();
    }

  private function callLlm(string $systemPrompt, array $history, string $question): array
{
    set_time_limit(180);
    $messages = array_merge(
        [['role' => 'system', 'content' => $systemPrompt]],
        $history,
    );

    try {
        $response = Http::timeout(120)
            ->post(config('services.llm.url'), [
                'model' => config('services.llm.model'),
                'messages' => $messages,
                'stream' => false,
                'options' => [
                    'temperature' => 0.3,
                ],
            ]);

        if ($response->failed()) {
            Log::error("RagChatService: failed to call LLM ({$response->status()}): {$response->body()}");

            return [
                'content' => "Sorry, an error occurred while generating the response.",
            ];
        }
        $data = $response->json();
        return [
            'content' => $data['message']['content'] ?? 'Sorry, no response received from the model.',
            'tokens_used' => ($data['eval_count'] ?? 0) + ($data['prompt_eval_count'] ?? 0),
        ];

    } catch (\Throwable $e) {
        Log::error("RagChatService: exception lors de l'appel LLM: {$e->getMessage()}");
        return [
            'content' => "Sorry, an error occurred while generating the response.",
        ];
    }
}
}
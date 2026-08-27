<?php

namespace App\Services\Github;
use Exception;
use Illuminate\Http\JsonResponse;

class GithubApiException extends Exception
{
    public function __construct(
        string $message,
        public readonly int $status,
        public readonly ?string $githubMessage = null,
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => $this->githubMessage,
        ], $this->status);
    }
}
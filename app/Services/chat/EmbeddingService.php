<?php
namespace App\Services\chat;
use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    public function embed(string $text): ?array
    {
        $response = Http::timeout(30)
            ->post(config('services.embedding.url') . '/embed', [
                'inputs' => $text,
            ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        return is_array($data[0] ?? null) ? $data[0] : $data;
    }
}
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiClient
{
    public function generate(string $prompt, float $temperature = 0.4): ?string
    {
        $key = config('services.gemini.key');

        if (! $key) {
            return null;
        }

        $model = config('services.gemini.model', 'gemini-2.0-flash');

        try {
            $text = Http::withHeaders(['x-goog-api-key' => $key])
                ->timeout(30)
                ->connectTimeout(20)
                ->withOptions(['force_ip_resolve' => 'v4'])
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['temperature' => $temperature],
                ])
                ->throw()
                ->json('candidates.0.content.parts.0.text');

            return $text ? trim($text) : null;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }
}
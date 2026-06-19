<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiVisionService
{
    /**
     * @return array<string, mixed>
     */
    public function chatWithImage(string $systemPrompt, string $userPrompt, string $imagePath): array
    {
        $apiKey = config('services.openai.key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new \RuntimeException('OpenAI API key is not configured. Set OPENAI_API_KEY in your .env file.');
        }

        if (! is_file($imagePath)) {
            throw new \RuntimeException('Image file not found for OpenAI analysis.');
        }

        $mime = mime_content_type($imagePath) ?: 'image/jpeg';
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (! in_array($mime, $allowed, true)) {
            throw new \RuntimeException('Unsupported image type for OpenAI analysis: '.$mime);
        }

        $base64 = base64_encode((string) file_get_contents($imagePath));
        $model = config('services.openai.model', 'gpt-4o');

        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => $userPrompt],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => 'data:'.$mime.';base64,'.$base64,
                                ],
                            ],
                        ],
                    ],
                ],
                'max_tokens' => 600,
            ]);

        if (! $response->successful()) {
            $error = $response->json('error');
            $code = is_array($error) ? (string) ($error['code'] ?? '') : '';
            $message = is_array($error) ? (string) ($error['message'] ?? '') : '';
            $message = $message !== '' ? $message : $response->body();

            Log::warning('OpenAI vision request failed', [
                'status' => $response->status(),
                'code' => $code,
                'message' => $message,
            ]);

            throw new \RuntimeException($this->formatApiError($code, $message));
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('OpenAI returned an empty response.');
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('OpenAI returned invalid JSON.');
        }

        return $decoded;
    }

    private function formatApiError(string $code, string $message): string
    {
        $lower = strtolower($message);

        if ($code === 'insufficient_quota' || str_contains($lower, 'exceeded your current quota')) {
            return 'OpenAI account quota exceeded. Add billing or credits at platform.openai.com, then try again. You can still enter slip details manually on the review page.';
        }

        if ($code === 'invalid_api_key' || str_contains($lower, 'incorrect api key')) {
            return 'OpenAI API key is invalid. Check OPENAI_API_KEY in your .env file.';
        }

        if ($code === 'rate_limit_exceeded' || str_contains($lower, 'rate limit')) {
            return 'OpenAI rate limit reached. Wait a minute and try again, or enter details manually on the review page.';
        }

        if ($code === 'model_not_found' || str_contains($lower, 'does not exist')) {
            return 'OpenAI model not available on this account. Try OPENAI_MODEL=gpt-4o-mini in your .env file.';
        }

        return 'OpenAI request failed: '.$message;
    }
}

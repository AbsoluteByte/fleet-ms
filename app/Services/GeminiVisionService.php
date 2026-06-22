<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiVisionService
{
    /**
     * @return array<string, mixed>
     */
    public function chatWithImage(string $systemPrompt, string $userPrompt, string $imagePath): array
    {
        $apiKey = config('services.gemini.key');

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new \RuntimeException('Gemini API key is not configured. Set GEMINI_API_KEY in your .env file.');
        }

        if (! is_file($imagePath)) {
            throw new \RuntimeException('Image file not found for Gemini analysis.');
        }

        $mime = mime_content_type($imagePath) ?: 'image/jpeg';
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (! in_array($mime, $allowed, true)) {
            throw new \RuntimeException('Unsupported image type for Gemini analysis: '.$mime);
        }

        $base64 = base64_encode((string) file_get_contents($imagePath));
        $model = config('services.gemini.model', 'gemini-2.5-flash');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent';

        $payload = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userPrompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mime,
                                'data' => $base64,
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'maxOutputTokens' => 1024,
                'responseSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'registration' => ['type' => 'string'],
                        'start_date' => ['type' => 'string'],
                        'term' => ['type' => 'string'],
                        'amount_paid' => ['type' => 'number'],
                        'confidence' => [
                            'type' => 'object',
                            'properties' => [
                                'registration' => ['type' => 'string'],
                                'start_date' => ['type' => 'string'],
                                'term' => ['type' => 'string'],
                                'amount_paid' => ['type' => 'string'],
                            ],
                        ],
                        'notes' => ['type' => 'string'],
                    ],
                ],
            ],
        ];

        $response = $this->requestWithRetry($url, $apiKey, $payload);

        if ($response->status() === 400 && isset($payload['generationConfig']['responseSchema'])) {
            unset($payload['generationConfig']['responseSchema']);
            $response = $this->requestWithRetry($url, $apiKey, $payload);
        }

        if (! $response->successful()) {
            $error = $response->json('error');
            $code = is_array($error) ? (string) ($error['status'] ?? '') : '';
            $message = is_array($error) ? (string) ($error['message'] ?? '') : '';
            $message = $message !== '' ? $message : $response->body();

            Log::warning('Gemini vision request failed', [
                'status' => $response->status(),
                'code' => $code,
                'model' => $model,
                'message' => $message,
            ]);

            throw new \RuntimeException($this->formatApiError($code, $message, $response->status(), $model));
        }

        $content = $this->extractTextFromResponse($response);

        if ($content === null || $content === '') {
            $blockReason = $response->json('promptFeedback.blockReason');
            if (is_string($blockReason) && $blockReason !== '') {
                throw new \RuntimeException('Gemini blocked this image: '.$blockReason);
            }

            $finishReason = $response->json('candidates.0.finishReason');
            if ($finishReason === 'MAX_TOKENS') {
                throw new \RuntimeException('Gemini response was truncated. Try a clearer image or enter details manually.');
            }

            throw new \RuntimeException('Gemini returned an empty response.');
        }

        return $this->parseJsonResponse($content);
    }

    private function extractTextFromResponse(Response $response): ?string
    {
        $parts = $response->json('candidates.0.content.parts');

        if (! is_array($parts)) {
            return null;
        }

        $text = '';

        foreach ($parts as $part) {
            if (is_array($part) && isset($part['text']) && is_string($part['text'])) {
                $text .= $part['text'];
            }
        }

        $text = trim($text);

        return $text !== '' ? $text : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonResponse(string $content): array
    {
        $content = trim($content);

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $content, $matches)) {
            $content = trim($matches[1]);
        }

        $decoded = json_decode($content, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $decoded = json_decode($matches[0], true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        Log::warning('Gemini invalid JSON response', [
            'content_preview' => substr($content, 0, 1000),
            'json_error' => json_last_error_msg(),
        ]);

        throw new \RuntimeException('Gemini returned invalid JSON. You can enter slip details manually on the review page.');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function requestWithRetry(string $url, string $apiKey, array $payload): Response
    {
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = Http::timeout(120)
                ->withQueryParameters(['key' => $apiKey])
                ->post($url, $payload);

            if ($response->successful() || $response->status() !== 429 || $attempt === $maxAttempts) {
                return $response;
            }

            $delay = $this->parseRetryDelay($response) ?? ($attempt * 2);
            $delay = min(max((int) ceil($delay), 1), 60);

            Log::info('Gemini rate limited, retrying', [
                'attempt' => $attempt,
                'delay_seconds' => $delay,
            ]);

            sleep($delay);
        }

        return $response;
    }

    private function parseRetryDelay(Response $response): ?float
    {
        $error = $response->json('error');
        $message = is_array($error) ? (string) ($error['message'] ?? '') : $response->body();

        if (preg_match('/retry in ([0-9.]+)s/i', $message, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    private function formatApiError(string $code, string $message, int $httpStatus, string $model): string
    {
        $lower = strtolower($message);
        $codeLower = strtolower($code);

        if (str_contains($lower, 'limit: 0') || str_contains($lower, 'shut down')) {
            return 'Gemini model "'.$model.'" is unavailable on your account (free tier limit is 0). '
                .'Set GEMINI_MODEL=gemini-2.5-flash in .env, run php artisan config:clear, then try again. '
                .'If it still fails, enable billing in Google AI Studio — free tier still applies with limits.';
        }

        if ($httpStatus === 429
            || $codeLower === 'resource_exhausted'
            || str_contains($lower, 'quota')
            || str_contains($lower, 'rate limit')
            || str_contains($lower, 'resource exhausted')) {
            return 'Gemini API quota or rate limit reached. Wait a minute and try again with fewer images, or enter slip details manually on the review page.';
        }

        if ($httpStatus === 403
            || $httpStatus === 401
            || str_contains($lower, 'api key')
            || str_contains($lower, 'permission denied')) {
            return 'Gemini API key is invalid. Check GEMINI_API_KEY in your .env file.';
        }

        if (str_contains($lower, 'not found') || str_contains($lower, 'not supported')) {
            return 'Gemini model "'.$model.'" is not available. Try GEMINI_MODEL=gemini-2.5-flash in your .env file.';
        }

        return 'Gemini request failed: '.$message;
    }
}

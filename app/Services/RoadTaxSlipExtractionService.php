<?php

namespace App\Services;

use Carbon\Carbon;

class RoadTaxSlipExtractionService
{
    public function __construct(
        private readonly OpenAiVisionService $openAi,
        private readonly InsuranceCertificateParser $registrationParser,
    ) {}

    /**
     * @return array{
     *     registration: ?string,
     *     start_date: ?string,
     *     term: ?string,
     *     amount: ?float,
     *     confidence: array<string, string>,
     *     notes: ?string,
     *     needs_review: bool,
     *     extraction_status: string,
     *     message: ?string
     * }
     */
    public function extractFromImage(string $imagePath): array
    {
        $base = [
            'registration' => null,
            'start_date' => null,
            'term' => null,
            'amount' => null,
            'confidence' => [
                'registration' => 'low',
                'start_date' => 'low',
                'term' => 'low',
                'amount_paid' => 'low',
            ],
            'notes' => null,
            'needs_review' => true,
            'extraction_status' => 'failed',
            'message' => null,
        ];

        try {
            $raw = $this->openAi->chatWithImage(
                $this->systemPrompt(),
                $this->userPrompt(),
                $imagePath
            );
        } catch (\Throwable $e) {
            return array_merge($base, [
                'message' => $e->getMessage(),
            ]);
        }

        $registration = $this->normalizeRegistrationDisplay($raw['registration'] ?? null);
        $startDate = $this->normalizeDate($raw['start_date'] ?? null);
        $term = $this->normalizeTerm($raw['term'] ?? null);
        $amount = $this->normalizeAmount($raw['amount_paid'] ?? ($raw['amount'] ?? null));

        $confidence = $this->normalizeConfidence($raw['confidence'] ?? []);
        $notes = is_string($raw['notes'] ?? null) ? trim($raw['notes']) : null;

        if ($notes === '') {
            $notes = null;
        }

        $needsReview = $registration === null
            || $startDate === null
            || $term === null
            || $amount === null
            || $this->hasLowConfidence($confidence);

        return [
            'registration' => $registration,
            'start_date' => $startDate,
            'term' => $term,
            'amount' => $amount,
            'confidence' => $confidence,
            'notes' => $notes,
            'needs_review' => $needsReview,
            'extraction_status' => 'ok',
            'message' => null,
        ];
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
You extract structured data from UK DVLA "Vehicle tax reminder" forms (V11).
Return only valid JSON matching the requested schema.
Use null for any field you cannot read confidently.
Set confidence per field to "high", "medium", or "low".
PROMPT;
    }

    private function userPrompt(): string
    {
        return <<<'PROMPT'
Read this UK DVLA V11 vehicle tax reminder slip and return JSON with exactly these keys:
{
  "registration": "UK registration as shown in the bold top-right box (e.g. KD17 UAP)",
  "start_date": "YYYY-MM-DD — the new tax period start date",
  "term": "6 months or 12 months",
  "amount_paid": number,
  "confidence": {
    "registration": "high|medium|low",
    "start_date": "high|medium|low",
    "term": "high|medium|low",
    "amount_paid": "high|medium|low"
  },
  "notes": "brief explanation of handwriting or ambiguity"
}

Rules:
1. Registration: read the bold registration in the top-right box.
2. Start date: find "Your vehicle tax runs out on [date]" and set start_date to the day AFTER that expiry date (e.g. runs out 30 June 2026 → start_date 2026-07-01). Do NOT use handwritten payment dates for start_date.
3. Term: determine whether 6 months or 12 months was paid by matching the amount to the printed options and any handwritten notes (e.g. "£375 paid" next to 12 months).
4. Amount paid: prefer handwritten "£X paid" notes; otherwise the matched option price.
5. If a field is unclear, use null and set its confidence to "low".
PROMPT;
    }

    private function normalizeRegistrationDisplay(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = $this->registrationParser->normalizeRegistration($value);

        if ($normalized === '') {
            return null;
        }

        if (strlen($normalized) <= 4) {
            return $normalized;
        }

        return substr($normalized, 0, -3).' '.substr($normalized, -3);
    }

    private function normalizeDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeTerm(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $term = strtolower(trim($value));

        if (str_contains($term, '6')) {
            return '6 months';
        }

        if (str_contains($term, '12') || str_contains($term, '1 year') || str_contains($term, 'one year')) {
            return '12 months';
        }

        return null;
    }

    private function normalizeAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $amount = (float) $value;

            return $amount > 0 ? round($amount, 2) : null;
        }

        if (is_string($value)) {
            $clean = preg_replace('/[^0-9.]/', '', $value);
            if ($clean === '' || ! is_numeric($clean)) {
                return null;
            }

            $amount = (float) $clean;

            return $amount > 0 ? round($amount, 2) : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $confidence
     * @return array<string, string>
     */
    private function normalizeConfidence(array $confidence): array
    {
        $fields = ['registration', 'start_date', 'term', 'amount_paid'];
        $normalized = [];

        foreach ($fields as $field) {
            $value = strtolower((string) ($confidence[$field] ?? 'low'));
            $normalized[$field] = in_array($value, ['high', 'medium', 'low'], true) ? $value : 'low';
        }

        return $normalized;
    }

    /**
     * @param  array<string, string>  $confidence
     */
    private function hasLowConfidence(array $confidence): bool
    {
        foreach ($confidence as $level) {
            if ($level === 'low') {
                return true;
            }
        }

        return false;
    }

    public function normalizeRegistrationKey(?string $registration): ?string
    {
        if (! is_string($registration) || trim($registration) === '') {
            return null;
        }

        $key = $this->registrationParser->normalizeRegistration($registration);

        return $key !== '' ? $key : null;
    }
}

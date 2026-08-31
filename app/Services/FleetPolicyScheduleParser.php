<?php

namespace App\Services;

use Carbon\Carbon;
use Smalot\PdfParser\Parser;
use Throwable;

class FleetPolicyScheduleParser
{
    /**
     * @return array{
     *     inception: ?Carbon,
     *     expiry: ?Carbon,
     *     policy_number: ?string,
     *     vehicles: list<array{
     *         date_added: ?Carbon,
     *         make_model: string,
     *         gvw: ?string,
     *         registration: string,
     *         registration_key: string,
     *         cover: string,
     *         annual_rate: ?float
     *     }>,
     *     raw_text_length: int
     * }
     */
    public function parseFile(string $path): array
    {
        $previousLimit = ini_get('memory_limit');
        @ini_set('memory_limit', '2048M');
        @set_time_limit(300);

        try {
            $text = (new Parser)->parseFile($path)->getText();
        } catch (Throwable $e) {
            throw new \RuntimeException('Unable to read the policy schedule PDF: '.$e->getMessage(), 0, $e);
        } finally {
            if (is_string($previousLimit) && $previousLimit !== '') {
                @ini_set('memory_limit', $previousLimit);
            }
        }

        return $this->parseText($text);
    }

    /**
     * @return array{
     *     inception: ?Carbon,
     *     expiry: ?Carbon,
     *     policy_number: ?string,
     *     vehicles: list<array{
     *         date_added: ?Carbon,
     *         make_model: string,
     *         gvw: ?string,
     *         registration: string,
     *         registration_key: string,
     *         cover: string,
     *         annual_rate: ?float
     *     }>,
     *     raw_text_length: int
     * }
     */
    public function parseText(string $text): array
    {
        $vehicles = [];

        if (preg_match_all(
            '/^(\d{2}\/\d{2}\/\d{4})\s+(.+?)[\t ]+(\S+)[\t ]+([A-Z]{2}\d{2}[A-Z]{3})[\t ]+([^\t\n£]+)[\t ]*£([\d,.]+)/im',
            $text,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $registration = $this->normalizeRegistration($match[4]);
                if ($registration === '') {
                    continue;
                }

                $vehicles[] = [
                    'date_added' => $this->parseSlashDate($match[1]),
                    'make_model' => trim(preg_replace('/\s+/', ' ', (string) $match[2])),
                    'gvw' => trim((string) $match[3]) ?: null,
                    'registration' => $registration,
                    'registration_key' => $registration,
                    'cover' => trim((string) $match[5]),
                    'annual_rate' => $this->parseMoney($match[6]),
                ];
            }
        }

        $policyNumber = null;
        if (preg_match('/Policy\s*Number\s*([A-Z0-9\/\-]+)/i', $text, $policyMatch)) {
            $policyNumber = trim($policyMatch[1]);
        }

        return [
            'inception' => $this->matchSlashDate($text, '/Inception\s*(\d{2}\/\d{2}\/\d{4})/i'),
            'expiry' => $this->matchSlashDate($text, '/Expiry\s*(\d{2}\/\d{2}\/\d{4})/i'),
            'policy_number' => $policyNumber,
            'vehicles' => $vehicles,
            'raw_text_length' => strlen($text),
        ];
    }

    public function normalizeRegistration(string $registration): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($registration)) ?? '');
    }

    private function matchSlashDate(string $text, string $pattern): ?Carbon
    {
        if (! preg_match($pattern, $text, $match)) {
            return null;
        }

        return $this->parseSlashDate($match[1]);
    }

    private function parseSlashDate(string $value): ?Carbon
    {
        try {
            return Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function parseMoney(string $value): ?float
    {
        $normalized = str_replace(',', '', trim($value));
        if ($normalized === '' || ! is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, 2);
    }
}

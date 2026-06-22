<?php

namespace App\Services;

use App\Data\ParsedCertificateData;
use Carbon\Carbon;
use Smalot\PdfParser\Parser;

class InsuranceCertificateParser
{
    /**
     * Samore-style: {index}_{REG}_{MODEL}_{DD-MM-YYYY}to{DD-MM-YYYY}.pdf
     */
    public function parseFromFilename(string $basename): ?ParsedCertificateData
    {
        $name = trim($basename);

        if (! preg_match(
            '/^\d+_([A-Z0-9]+)_.+_(\d{2}-\d{2}-\d{4})to(\d{2}-\d{2}-\d{4})\.pdf$/i',
            $name,
            $matches
        )) {
            return null;
        }

        $start = $this->parseUkDate($matches[2]);
        $end = $this->parseUkDate($matches[3]);

        if (! $start || ! $end) {
            return null;
        }

        return new ParsedCertificateData(
            registration: $this->normalizeRegistration($matches[1]),
            startDate: $start,
            expiryDate: $end,
            source: 'filename',
        );
    }

    public function parseFromPdf(string $path): ?ParsedCertificateData
    {
        try {
            $text = (new Parser)->parseFile($path)->getText();
        } catch (\Throwable) {
            return null;
        }

        $registration = null;
        if (preg_match('/Description of Vehicle[:\s]*([A-Z0-9]{2,10})\b/i', $text, $regMatch)) {
            $registration = $this->normalizeRegistration($regMatch[1]);
        } elseif (preg_match('/\b([A-Z]{2}\d{2}[A-Z]{3})\b/', $text, $ukReg)) {
            $registration = $this->normalizeRegistration($ukReg[1]);
        }

        $startDate = null;
        if (preg_match('/Effective Date of Commencement.*?(\d{2}\/\d{2}\/\d{4})/is', $text, $startMatch)) {
            $startDate = $this->parseSlashDate($startMatch[1]);
        }

        $expiryDate = null;
        if (preg_match('/Date and Time of Expiry.*?(\d{2}\/\d{2}\/\d{4})/is', $text, $endMatch)) {
            $expiryDate = $this->parseSlashDate($endMatch[1]);
        }

        if (! $registration || ! $startDate || ! $expiryDate) {
            return null;
        }

        return new ParsedCertificateData(
            registration: $registration,
            startDate: $startDate,
            expiryDate: $expiryDate,
            source: 'pdf',
        );
    }

    /**
     * Prefer filename; validate or fill from PDF when possible.
     */
    public function parse(string $filePath): ?ParsedCertificateData
    {
        $basename = basename($filePath);
        $fromFilename = $this->parseFromFilename($basename);
        $fromPdf = $this->parseFromPdf($filePath);

        if ($fromFilename && $fromPdf) {
            if ($fromFilename->registration !== $fromPdf->registration) {
                return null;
            }

            if (! $fromFilename->startDate->isSameDay($fromPdf->startDate)
                || ! $fromFilename->expiryDate->isSameDay($fromPdf->expiryDate)) {
                return null;
            }

            return $fromFilename;
        }

        return $fromFilename ?? $fromPdf;
    }

    public function normalizeRegistration(string $registration): string
    {
        return strtoupper(preg_replace('/\s+/', '', trim($registration)));
    }

    private function parseUkDate(string $value): ?Carbon
    {
        try {
            return Carbon::createFromFormat('d-m-Y', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseSlashDate(string $value): ?Carbon
    {
        try {
            return Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}

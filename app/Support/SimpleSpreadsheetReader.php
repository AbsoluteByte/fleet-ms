<?php

namespace App\Support;

use ZipArchive;

class SimpleSpreadsheetReader
{
    /**
     * @return list<list<string>>
     */
    public static function read(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv' => self::readCsv($path),
            'xlsx' => self::readXlsx($path),
            default => throw new \InvalidArgumentException('Unsupported file type. Please upload a CSV or XLSX file.'),
        };
    }

    /**
     * @return list<list<string>>
     */
    private static function readCsv(string $path): array
    {
        $rows = [];
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new \RuntimeException('Could not read CSV file.');
        }

        while (($data = fgetcsv($handle)) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }

            $rows[] = array_map(function ($value) {
                $value = trim((string) $value);

                return ltrim($value, "\xEF\xBB\xBF");
            }, $data);
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return list<list<string>>
     */
    private static function readXlsx(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Could not open spreadsheet file.');
        }

        $sharedStrings = self::readSharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new \RuntimeException('Could not read worksheet data from spreadsheet.');
        }

        $xml = simplexml_load_string($sheetXml);

        if ($xml === false) {
            throw new \RuntimeException('Could not parse worksheet XML.');
        }

        $xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];

        foreach ($xml->xpath('//m:sheetData/m:row') as $row) {
            $row->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $cells = [];
            $maxIndex = -1;

            foreach ($row->xpath('m:c') as $cell) {
                $cellRef = (string) ($cell['r'] ?? '');
                $index = $cellRef !== '' ? self::columnIndex($cellRef) : ++$maxIndex;
                $maxIndex = max($maxIndex, $index);
                $cells[$index] = self::cellValue($cell, $sharedStrings);
            }

            if ($cells === []) {
                $rows[] = [];

                continue;
            }

            ksort($cells);
            $normalized = [];
            $lastIndex = array_key_last($cells);

            for ($i = 0; $i <= $lastIndex; $i++) {
                $normalized[] = $cells[$i] ?? '';
            }

            $rows[] = $normalized;
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private static function readSharedStrings(ZipArchive $zip): array
    {
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedXml === false) {
            return [];
        }

        $xml = simplexml_load_string($sharedXml);

        if ($xml === false) {
            return [];
        }

        $xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $sharedStrings = [];

        foreach ($xml->xpath('//m:si') as $item) {
            $item->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $parts = $item->xpath('.//m:t');
            $text = '';

            foreach ($parts as $part) {
                $text .= (string) $part;
            }

            $sharedStrings[] = $text;
        }

        return $sharedStrings;
    }

    private static function cellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) ($cell['t'] ?? '');
        $valueNode = $cell->children('http://schemas.openxmlformats.org/spreadsheetml/2006/main')->v;

        if ($valueNode === null) {
            return '';
        }

        $value = (string) $valueNode;

        if ($type === 's') {
            return trim($sharedStrings[(int) $value] ?? '');
        }

        return trim($value);
    }

    private static function columnIndex(string $cellRef): int
    {
        if (! preg_match('/^([A-Z]+)/', strtoupper($cellRef), $matches)) {
            return 0;
        }

        $letters = $matches[1];
        $index = 0;

        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - ord('A') + 1);
        }

        return $index - 1;
    }
}

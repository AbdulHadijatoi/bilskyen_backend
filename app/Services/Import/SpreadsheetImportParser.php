<?php

namespace App\Services\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;

class SpreadsheetImportParser
{
    /**
     * Parse Excel or CSV into rows keyed by normalized header names.
     *
     * @return list<array<string, mixed>>
     */
    public function parse(string $filePath, string $fileType): array
    {
        $fileType = strtolower($fileType);

        if (in_array($fileType, ['xlsx', 'xls'], true)) {
            return $this->parseSpreadsheet($filePath, $fileType);
        }

        if ($fileType === 'csv') {
            return $this->parseCsv($filePath);
        }

        throw new \InvalidArgumentException("Unsupported file type: {$fileType}");
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseSpreadsheet(string $filePath, string $fileType): array
    {
        if (class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            $data = \Maatwebsite\Excel\Facades\Excel::toArray([], $filePath)[0];
        } else {
            $reader = IOFactory::createReader($fileType === 'xlsx' ? 'Xlsx' : 'Xls');
            $spreadsheet = $reader->load($filePath);
            $data = $spreadsheet->getActiveSheet()->toArray();
        }

        $headers = array_shift($data);
        if ($headers === null) {
            return [];
        }

        return $this->mapRows($headers, $data);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseCsv(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Could not open CSV file');
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);

            throw new \RuntimeException('CSV file is empty or invalid');
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $this->mapRows($headers, $rows);
    }

    /**
     * @param  array<int, mixed>  $headers
     * @param  array<int, array<int, mixed>>  $data
     * @return list<array<string, mixed>>
     */
    private function mapRows(array $headers, array $data): array
    {
        $normalizedHeaders = array_map(
            static fn ($header) => self::normalizeHeader((string) $header),
            $headers
        );

        $mappedData = [];
        foreach ($data as $row) {
            if (! is_array($row) || empty(array_filter($row, static fn ($v) => $v !== null && $v !== ''))) {
                continue;
            }

            $mappedRow = [];
            foreach ($normalizedHeaders as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $canonicalHeader = \App\Services\VehicleImport\VehicleImportColumnDefinitions::resolveCanonicalKey($header);
                $mappedRow[$canonicalHeader] = $row[$index] ?? '';
            }
            $mappedData[] = $mappedRow;
        }

        return $mappedData;
    }

    public static function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = str_replace([' ', '-'], '_', $header);

        return $header;
    }
}

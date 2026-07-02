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
        $contents = file_get_contents($filePath);
        if ($contents === false) {
            throw new \RuntimeException('Could not open CSV file');
        }

        $contents = $this->stripUtf8Bom($contents);

        $handle = fopen('php://memory', 'r+');
        if ($handle === false) {
            throw new \RuntimeException('Could not open CSV file');
        }

        fwrite($handle, $contents);
        rewind($handle);

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);

            throw new \RuntimeException('CSV file is empty or invalid');
        }

        $delimiter = $this->detectCsvDelimiter($firstLine);
        rewind($handle);

        $headers = fgetcsv($handle, 0, $delimiter);
        if ($headers === false) {
            fclose($handle);

            throw new \RuntimeException('CSV file is empty or invalid');
        }

        $headers = array_map(fn ($header) => $this->stripUtf8Bom((string) $header), $headers);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $this->mapRows($headers, $rows);
    }

    public static function detectCsvDelimiter(string $line): string
    {
        $candidates = [';', ',', "\t", '|'];
        $best = ',';
        $bestCount = 0;

        foreach ($candidates as $delimiter) {
            $count = substr_count($line, $delimiter);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $delimiter;
            }
        }

        return $best;
    }

    public static function stripUtf8Bom(string $value): string
    {
        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            return substr($value, 3);
        }

        return $value;
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
        $header = self::stripUtf8Bom($header);
        $header = strtolower(trim($header));
        $header = str_replace([' ', '-'], '_', $header);

        return $header;
    }
}

<?php

namespace App\Http\Controllers\Concerns;

use Symfony\Component\HttpFoundation\StreamedResponse;

trait ExportsAnalyticsCsv
{
    /**
     * @param  list<list<string|int|float|null>>  $rows
     */
    protected function csvDownload(array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}

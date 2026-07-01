<?php

namespace App\Services\VehicleImport;

use App\Services\LookupService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class VehicleImportTemplateBuilder
{
    public function __construct(
        private LookupService $lookupService,
    ) {}

    public function buildXlsx(): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Vehicles');

        $headers = VehicleImportColumnDefinitions::TEMPLATE_HEADERS;
        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValue([$colIndex + 1, 1], $header);
        }

        $sample = VehicleImportColumnDefinitions::SAMPLE_ROW;
        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValue([$colIndex + 1, 2], $sample[$header] ?? '');
        }

        $lookupSheet = $spreadsheet->createSheet();
        $lookupSheet->setTitle('Lookups');
        $constants = $this->lookupService->getDealerConstants();
        $row = 1;
        foreach ($constants as $group => $items) {
            $lookupSheet->setCellValue([1, $row], (string) $group);
            $row++;
            if (! is_array($items)) {
                continue;
            }
            foreach ($items as $item) {
                $name = is_array($item) ? ($item['name'] ?? $item['label'] ?? null) : null;
                if ($name !== null && $name !== '') {
                    $lookupSheet->setCellValue([2, $row], (string) $name);
                    $row++;
                }
            }
            $row++;
        }

        $temp = tempnam(sys_get_temp_dir(), 'vehicle_import_template_');
        $path = $temp.'.xlsx';
        @unlink($temp);
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}

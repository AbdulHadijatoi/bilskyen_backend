<?php

namespace App\Services\Feeds;

use App\Constants\VehicleListStatus;
use App\Models\Dealer;
use App\Models\Vehicle;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VehicleExportService
{
    public function __construct(
        private VehicleFeedBuilderService $feedBuilder
    ) {}

    /**
     * @return Collection<int, Vehicle>
     */
    public function dealerStock(Dealer $dealer, ?int $statusId = null): Collection
    {
        $query = Vehicle::with(['images', 'brand', 'fuelType'])
            ->where('dealer_id', $dealer->id)
            ->orderByDesc('updated_at');

        if ($statusId !== null) {
            $query->where('list_status_id', $statusId);
        }

        return $query->get();
    }

    public function downloadResponse(Dealer $dealer, string $format = 'csv', ?int $statusId = null): StreamedResponse
    {
        $vehicles = $this->dealerStock($dealer, $statusId);
        $filename = 'vehicles-'.$dealer->id.'-'.now()->format('Y-m-d').'.'.$format;

        if ($format === 'xlsx') {
            return $this->xlsxResponse($vehicles, $filename);
        }

        return $this->csvResponse($vehicles, $filename);
    }

    /**
     * @param  Collection<int, Vehicle>  $vehicles
     */
    private function csvResponse(Collection $vehicles, string $filename): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($vehicles);
        $writer = new Csv($spreadsheet);
        $writer->setDelimiter(';');
        $writer->setEnclosure('"');

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  Collection<int, Vehicle>  $vehicles
     */
    private function xlsxResponse(Collection $vehicles, string $filename): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($vehicles);
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  Collection<int, Vehicle>  $vehicles
     */
    private function buildSpreadsheet(Collection $vehicles): Spreadsheet
    {
        $sheet = new Spreadsheet;
        $active = $sheet->getActiveSheet();
        $headers = ['ID', 'Title', 'Registration', 'Price', 'KM', 'Status', 'Brand', 'Fuel', 'Published', 'URL'];
        $active->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($vehicles as $vehicle) {
            $mapped = $this->feedBuilder->mapVehicle($vehicle);
            $active->fromArray([
                $vehicle->id,
                $vehicle->title,
                $vehicle->registration,
                $vehicle->price,
                $vehicle->km_driven,
                $vehicle->list_status_id,
                $mapped['brand'],
                $mapped['fuel_type'],
                $vehicle->published_at?->format('Y-m-d'),
                $mapped['url'],
            ], null, 'A'.$row);
            $row++;
        }

        return $sheet;
    }
}

<?php

namespace App\Services\VehicleImport;

use App\Models\Dealer;
use App\Models\User;
use App\Models\VehicleImportBatch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class VehicleImportBatchService
{
    public function __construct(
        private VehicleImportService $vehicleImportService,
    ) {}

    public function queueImport(
        UploadedFile $file,
        Dealer $dealer,
        User $user,
        bool $dryRun = false,
    ): VehicleImportBatch {
        $extension = strtolower($file->getClientOriginalExtension());
        $storedPath = $file->storeAs(
            'vehicle-imports/'.$dealer->id,
            uniqid('import_', true).'.'.$extension,
            'local'
        );

        return VehicleImportBatch::create([
            'dealer_id' => $dealer->id,
            'user_id' => $user->id,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'file_extension' => $extension,
            'dry_run' => $dryRun,
            'status' => VehicleImportBatch::STATUS_PENDING,
        ]);
    }

    /**
     * @return array{summary: array<string, int>, rows: list<array<string, mixed>>}
     */
    public function processBatch(VehicleImportBatch $batch): array
    {
        $absolutePath = Storage::disk('local')->path($batch->file_path);
        if (! is_readable($absolutePath)) {
            throw new \RuntimeException(__('messages.api.vehicle_import_file_unreadable'));
        }

        $dealer = $batch->dealer ?? Dealer::findOrFail($batch->dealer_id);
        $user = $batch->user ?? User::findOrFail($batch->user_id);

        return $this->vehicleImportService->importFromPath(
            $absolutePath,
            $batch->file_extension,
            (int) $batch->dealer_id,
            (int) $batch->user_id,
            $dealer,
            (bool) $batch->dry_run,
        );
    }
}

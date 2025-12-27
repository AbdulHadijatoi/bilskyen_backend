<?php

namespace App\Console\Commands;

use App\Services\NummerpladeApiService;
use App\Models\BodyType;
use App\Models\Color;
use App\Models\FuelType;
use App\Models\Equipment;
use App\Models\Type;
use App\Models\VehicleUse;
use App\Models\Category;
use App\Models\GearType;
use App\Models\Condition;
use App\Models\Permit;
use App\Models\SalesType;
use App\Models\VehicleListStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncNummerpladeReferenceData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nummerplade:sync-reference-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync reference data from Nummerplade API to lookup tables';

    /**
     * Execute the console command.
     */
    public function handle(NummerpladeApiService $nummerpladeService): int
    {
        $this->info('Starting Nummerplade reference data sync...');

        try {
            DB::beginTransaction();

            // Sync Body Types
            $this->info('Syncing body types...');
            $bodyTypes = $nummerpladeService->getBodyTypes();
            $this->syncLookupTable(BodyType::class, $bodyTypes, 'body types');

            // Sync Colors
            $this->info('Syncing colors...');
            $colors = $nummerpladeService->getColors();
            $this->syncLookupTable(Color::class, $colors, 'colors');

            // Sync Fuel Types
            $this->info('Syncing fuel types...');
            $fuelTypes = $nummerpladeService->getFuelTypes();
            $this->syncLookupTable(FuelType::class, $fuelTypes, 'fuel types');

            // Sync Equipment
            $this->info('Syncing equipment...');
            $equipment = $nummerpladeService->getEquipment();
            $this->syncLookupTable(Equipment::class, $equipment, 'equipment');

            // Sync Types
            $this->info('Syncing types...');
            $types = $nummerpladeService->getTypes();
            $this->syncLookupTable(Type::class, $types, 'types');

            // Sync Uses
            $this->info('Syncing uses...');
            $uses = $nummerpladeService->getUses();
            $this->syncLookupTable(VehicleUse::class, $uses, 'uses');

            // Sync Permits
            $this->info('Syncing permits...');
            $permits = $nummerpladeService->getPermits();
            $this->syncLookupTable(Permit::class, $permits, 'permits');

            // Sync Categories (hardcoded values)
            $this->info('Syncing categories...');
            $this->syncHardcodedLookupTable(Category::class, [
                'Passenger car',
                'Van incl. VAT',
                'Van excluding VAT',
                'Bus',
                'Lorry',
                'Motorhome'
            ], 'categories');

            // Sync Gear Types (hardcoded values)
            $this->info('Syncing gear types...');
            $this->syncHardcodedLookupTable(GearType::class, [
                'Manual',
                'Automatic'
            ], 'gear types');

            // Sync Conditions (hardcoded values)
            $this->info('Syncing conditions...');
            $this->syncHardcodedLookupTable(Condition::class, [
                'New',
                'Used'
            ], 'conditions');

            // Sync Sales Types (hardcoded values)
            $this->info('Syncing sales types...');
            $this->syncHardcodedLookupTable(SalesType::class, [
                'Consignment',
                'Facilitated'
            ], 'sales types');

            // Sync Vehicle List Statuses (hardcoded values)
            $this->info('Syncing vehicle list statuses...');
            $this->syncHardcodedLookupTable(VehicleListStatus::class, [
                'Draft',
                'Published',
                'Sold',
                'Archived'
            ], 'vehicle list statuses');

            DB::commit();
            $this->info('Reference data sync completed successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to sync reference data: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Sync data to a lookup table
     */
    private function syncLookupTable(string $modelClass, array $data, string $typeName): void
    {
        $count = 0;
        $newCount = 0;

        // Handle different response formats
        if (isset($data['data']) && is_array($data['data'])) {
            $items = $data['data'];
        } elseif (is_array($data) && isset($data[0])) {
            $items = $data;
        } else {
            $items = [];
        }

        foreach ($items as $item) {
            // Handle different item formats
            $name = null;
            if (is_string($item)) {
                $name = $item;
            } elseif (is_array($item)) {
                $name = $item['name'] ?? $item['title'] ?? $item['value'] ?? null;
            }

            if ($name) {
                $existing = $modelClass::where('name', $name)->first();
                if (!$existing) {
                    $modelClass::create(['name' => $name]);
                    $newCount++;
                }
                $count++;
            }
        }

        $this->line("  Synced {$count} {$typeName} ({$newCount} new)");
    }

    /**
     * Sync hardcoded values to a lookup table
     */
    private function syncHardcodedLookupTable(string $modelClass, array $names, string $typeName): void
    {
        $count = 0;
        $newCount = 0;

        foreach ($names as $name) {
            $existing = $modelClass::where('name', $name)->first();
            if (!$existing) {
                $modelClass::create(['name' => $name]);
                $newCount++;
            }
            $count++;
        }

        $this->line("  Synced {$count} {$typeName} ({$newCount} new)");
    }
}


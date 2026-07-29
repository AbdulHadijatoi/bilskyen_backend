<?php

use App\Models\ListingType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Vehicles imported with Salgstype "leasing" / Leasingdetaljer kept listing_type = Purchase.
 * Align them to listing_type Leasing so public "Leasing" filters include them.
 */
return new class extends Migration
{
    public function up(): void
    {
        $leasingSalesTypeId = DB::table('sales_types')
            ->where('name', 'Leasingdetaljer')
            ->whereNull('deleted_at')
            ->value('id');

        $leasingListingTypeId = DB::table('listing_types')
            ->whereRaw('LOWER(name) = ?', ['leasing'])
            ->whereNull('deleted_at')
            ->value('id');

        if ($leasingSalesTypeId === null || $leasingListingTypeId === null) {
            return;
        }

        DB::table('vehicles')
            ->where('sales_type_id', $leasingSalesTypeId)
            ->where(function ($q) {
                $q->whereNull('listing_type_id')
                    ->orWhere('listing_type_id', ListingType::PURCHASE_ID);
            })
            ->update(['listing_type_id' => $leasingListingTypeId]);
    }

    public function down(): void
    {
        // Irreversible data alignment — no-op
    }
};

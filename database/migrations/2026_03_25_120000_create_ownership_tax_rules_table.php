<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ownership_tax_rules', function (Blueprint $table) {
            $table->id();

            $table->unsignedSmallInteger('registration_year_from');
            $table->unsignedSmallInteger('registration_year_to');

            $table->decimal('km_per_liter_from', 10, 3);
            $table->decimal('km_per_liter_to', 10, 3);

            $table->bigInteger('dmr_drive_energy_id');
            $table->unsignedInteger('tax_amount');

            $table->timestamps();

            $table->foreign('dmr_drive_energy_id')
                ->references('id')
                ->on('dmr_drive_energies')
                ->restrictOnDelete();

            $table->index([
                'dmr_drive_energy_id',
                'registration_year_from',
                'registration_year_to',
                'km_per_liter_from',
                'km_per_liter_to',
            ], 'ownership_tax_rules_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ownership_tax_rules');
    }
};


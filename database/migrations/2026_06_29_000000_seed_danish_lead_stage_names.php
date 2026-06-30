<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Seed Danish default names for lead stages (admin-editable thereafter).
     */
    public function up(): void
    {
        $stages = [
            1 => 'Ny',
            2 => 'Kontaktet',
            3 => 'Kvalificeret',
            4 => 'Tilbudt',
            5 => 'Forhandler',
            6 => 'Vundet',
            7 => 'Tabt',
        ];

        foreach ($stages as $id => $name) {
            DB::table('lead_stages')->where('id', $id)->update(['name' => $name]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $stages = [
            1 => 'New',
            2 => 'Contacted',
            3 => 'Qualified',
            4 => 'Quoted',
            5 => 'Negotiating',
            6 => 'Won',
            7 => 'Lost',
        ];

        foreach ($stages as $id => $name) {
            DB::table('lead_stages')->where('id', $id)->update(['name' => $name]);
        }
    }
};

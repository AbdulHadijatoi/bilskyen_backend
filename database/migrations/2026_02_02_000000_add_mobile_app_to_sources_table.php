<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Source;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add "Mobile App" source if it doesn't exist
        Source::firstOrCreate(['name' => Source::MOBILE_APP]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sources')->where('name', Source::MOBILE_APP)->delete();
    }
};

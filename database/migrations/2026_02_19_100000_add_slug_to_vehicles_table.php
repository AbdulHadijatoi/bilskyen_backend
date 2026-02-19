<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Vehicle;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('slug', 255)->nullable()->unique()->after('title');
            $table->index('slug');
        });

        $this->generateSlugsForExistingVehicles();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn(['slug']);
        });
    }

    /**
     * Generate slugs for existing vehicles from title.
     */
    private function generateSlugsForExistingVehicles(): void
    {
        $vehicles = Vehicle::withoutGlobalScopes()->get();

        foreach ($vehicles as $vehicle) {
            $title = $vehicle->getRawOriginal('title') ?: $vehicle->title;
            if (empty($title)) {
                $title = 'vehicle-' . $vehicle->id;
            }
            $slug = Str::slug($title);
            if (empty($slug)) {
                $slug = 'vehicle-' . $vehicle->id;
            }
            $originalSlug = $slug;
            $counter = 1;
            while (Vehicle::withoutGlobalScopes()->where('slug', $slug)->where('id', '!=', $vehicle->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $vehicle->slug = $slug;
            $vehicle->saveQuietly();
        }
    }
};

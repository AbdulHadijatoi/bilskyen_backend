<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketplace_cities')) {
            Schema::create('marketplace_cities', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 120)->unique();
                $table->string('region', 100)->nullable();
                $table->json('aliases')->nullable();
                $table->unsignedInteger('published_vehicle_count')->default(0);
                $table->unsignedInteger('dealer_count')->default(0);
                $table->decimal('min_price', 12, 2)->nullable();
                $table->decimal('max_price', 12, 2)->nullable();
                $table->json('top_brands')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_computed_at')->nullable();
                $table->timestamps();

                $table->index('is_active');
                $table->index('published_vehicle_count');
                $table->index('dealer_count');
            });
        }

        if (Schema::hasTable('dealers') && ! Schema::hasColumn('dealers', 'marketplace_city_id')) {
            Schema::table('dealers', function (Blueprint $table) {
                $table->foreignId('marketplace_city_id')
                    ->nullable()
                    ->after('city')
                    ->constrained('marketplace_cities')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dealers') && Schema::hasColumn('dealers', 'marketplace_city_id')) {
            Schema::table('dealers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('marketplace_city_id');
            });
        }

        Schema::dropIfExists('marketplace_cities');
    }
};

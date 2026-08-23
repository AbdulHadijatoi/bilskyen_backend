<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vehicles')) {
            $addModelYear = Schema::hasColumn('vehicles', 'model_year')
                && ! $this->hasIndex('vehicles', 'vehicles_model_year_index');
            $addKmDriven = Schema::hasColumn('vehicles', 'km_driven')
                && ! $this->hasIndex('vehicles', 'vehicles_km_driven_index');

            if ($addModelYear || $addKmDriven) {
                Schema::table('vehicles', function (Blueprint $table) use ($addModelYear, $addKmDriven) {
                    if ($addModelYear) {
                        $table->index('model_year');
                    }
                    if ($addKmDriven) {
                        $table->index('km_driven');
                    }
                });
            }
        }

        if (Schema::hasTable('listing_views_log') && ! $this->hasIndex('listing_views_log', 'listing_views_log_user_id_viewed_at_index')) {
            Schema::table('listing_views_log', function (Blueprint $table) {
                $table->index(['user_id', 'viewed_at']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vehicles')) {
            $dropModelYear = $this->hasIndex('vehicles', 'vehicles_model_year_index');
            $dropKmDriven = $this->hasIndex('vehicles', 'vehicles_km_driven_index');
            if ($dropModelYear || $dropKmDriven) {
                Schema::table('vehicles', function (Blueprint $table) use ($dropModelYear, $dropKmDriven) {
                    if ($dropModelYear) {
                        $table->dropIndex('vehicles_model_year_index');
                    }
                    if ($dropKmDriven) {
                        $table->dropIndex('vehicles_km_driven_index');
                    }
                });
            }
        }

        if (Schema::hasTable('listing_views_log') && $this->hasIndex('listing_views_log', 'listing_views_log_user_id_viewed_at_index')) {
            Schema::table('listing_views_log', function (Blueprint $table) {
                $table->dropIndex('listing_views_log_user_id_viewed_at_index');
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        foreach (Schema::getIndexes($table) as $row) {
            if (($row['name'] ?? '') === $index) {
                return true;
            }
        }

        return false;
    }
};

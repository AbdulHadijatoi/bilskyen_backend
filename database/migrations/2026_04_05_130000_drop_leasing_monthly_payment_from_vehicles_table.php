<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        if (Schema::hasColumn('vehicles', 'leasing_monthly_payment')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('leasing_monthly_payment');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('vehicles')) {
            return;
        }

        if (! Schema::hasColumn('vehicles', 'leasing_monthly_payment')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->decimal('leasing_monthly_payment', 12, 2)->nullable()->after('leasing_customer_type');
            });
        }
    }
};

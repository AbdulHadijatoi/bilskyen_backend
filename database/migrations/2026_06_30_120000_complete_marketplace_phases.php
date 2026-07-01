<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vehicles', 'view_3d_url')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->string('view_3d_url', 500)->nullable()->after('cover_image_index');
            });
        }

        DB::table('vehicle_list_statuses')->updateOrInsert(
            ['id' => 5],
            ['name' => 'Pending Review']
        );

        $auditFeature = DB::table('features')->where('key', 'audit_logs')->first();
        $basicPlan = DB::table('plans')->where('slug', 'basic')->first();
        if ($auditFeature && $basicPlan) {
            DB::table('plan_features')->updateOrInsert(
                ['plan_id' => $basicPlan->id, 'feature_id' => $auditFeature->id],
                ['value' => 'false']
            );
        }

        DB::table('plans')->whereIn('slug', ['enterprise', 'trial'])->update([
            'is_active' => false,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('vehicles', 'view_3d_url')) {
            Schema::table('vehicles', function (Blueprint $table) {
                $table->dropColumn('view_3d_url');
            });
        }

        DB::table('vehicle_list_statuses')->where('id', 5)->delete();
    }
};

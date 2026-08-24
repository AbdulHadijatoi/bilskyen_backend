<?php

use App\Services\PlatformSettingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('listing_views_log')) {
            Schema::table('listing_views_log', function (Blueprint $table) {
                if (! Schema::hasColumn('listing_views_log', 'session_id')) {
                    $table->string('session_id', 64)->nullable()->after('user_agent');
                }
                if (! Schema::hasColumn('listing_views_log', 'traffic_source')) {
                    $table->string('traffic_source', 32)->nullable()->after('session_id');
                }
                if (! Schema::hasColumn('listing_views_log', 'utm_source')) {
                    $table->string('utm_source', 191)->nullable()->after('traffic_source');
                }
                if (! Schema::hasColumn('listing_views_log', 'utm_campaign')) {
                    $table->string('utm_campaign', 191)->nullable()->after('utm_source');
                }
            });

            Schema::table('listing_views_log', function (Blueprint $table) {
                if (! $this->hasIndex('listing_views_log', 'listing_views_log_traffic_source_viewed_at_index')) {
                    $table->index(['traffic_source', 'viewed_at']);
                }
                if (! $this->hasIndex('listing_views_log', 'listing_views_log_session_id_index')) {
                    $table->index('session_id');
                }
            });
        }

        if (! Schema::hasTable('listing_funnel_events')) {
            Schema::create('listing_funnel_events', function (Blueprint $table) {
                $table->id();
                $table->string('session_id', 64);
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                $table->string('traffic_source', 32)->nullable();
                $table->string('event_name', 32);
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['session_id', 'vehicle_id', 'event_name'], 'listing_funnel_session_vehicle_event_index');
                $table->index(['traffic_source', 'event_name', 'created_at'], 'listing_funnel_source_event_created_index');
                $table->index(['vehicle_id', 'created_at']);
            });
        }

        $settings = app(PlatformSettingService::class);
        $settings->setGroup('marketing', [
            'microsoft_clarity_enabled' => $settings->get('marketing', 'microsoft_clarity_enabled', false) ?: 'false',
            'microsoft_clarity_project_id' => (string) ($settings->get('marketing', 'microsoft_clarity_project_id', '') ?? ''),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('listing_funnel_events')) {
            Schema::dropIfExists('listing_funnel_events');
        }

        if (Schema::hasTable('listing_views_log')) {
            Schema::table('listing_views_log', function (Blueprint $table) {
                if ($this->hasIndex('listing_views_log', 'listing_views_log_traffic_source_viewed_at_index')) {
                    $table->dropIndex('listing_views_log_traffic_source_viewed_at_index');
                }
                if ($this->hasIndex('listing_views_log', 'listing_views_log_session_id_index')) {
                    $table->dropIndex('listing_views_log_session_id_index');
                }
                foreach (['session_id', 'traffic_source', 'utm_source', 'utm_campaign'] as $column) {
                    if (Schema::hasColumn('listing_views_log', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        $sm = Schema::getConnection()->getSchemaBuilder();
        if (! method_exists($sm, 'getIndexes')) {
            return false;
        }

        foreach ($sm->getIndexes($table) as $row) {
            if (($row['name'] ?? '') === $index) {
                return true;
            }
        }

        return false;
    }
};

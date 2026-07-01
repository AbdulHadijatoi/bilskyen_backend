<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'billing_model')) {
                $table->string('billing_model', 32)->default('subscription')->after('trial_days');
            }
            if (! Schema::hasColumn('plans', 'price_per_listing_per_day')) {
                $table->unsignedInteger('price_per_listing_per_day')->nullable()->after('billing_model');
            }
        });

        Schema::table('dealer_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('dealer_subscriptions', 'billing_cycle')) {
                $table->string('billing_cycle', 20)->nullable()->after('auto_renew');
            }
        });

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'listing_billing_started_at')) {
                $table->timestamp('listing_billing_started_at')->nullable()->after('published_at');
            }
            if (! Schema::hasColumn('vehicles', 'listing_billing_paused_at')) {
                $table->timestamp('listing_billing_paused_at')->nullable()->after('listing_billing_started_at');
            }
            if (! Schema::hasColumn('vehicles', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('listing_billing_paused_at');
            }
        });

        if (! Schema::hasTable('dealer_invoices')) {
            Schema::create('dealer_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->date('period_start');
                $table->date('period_end');
                $table->unsignedInteger('total_cents')->default(0);
                $table->char('currency', 3)->default('DKK');
                $table->string('status', 20)->default('draft');
                $table->text('notes')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->timestamps();

                $table->index(['dealer_id', 'status']);
            });
        }

        if (! Schema::hasTable('listing_billing_periods')) {
            Schema::create('listing_billing_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
                $table->date('billing_date');
                $table->unsignedInteger('amount_cents');
                $table->string('status', 20)->default('pending');
                $table->foreignId('dealer_invoice_id')->nullable()->constrained('dealer_invoices')->nullOnDelete();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['vehicle_id', 'billing_date']);
                $table->index(['dealer_id', 'billing_date', 'status']);
            });
        }

        if (! Schema::hasTable('dealer_invoice_lines')) {
            Schema::create('dealer_invoice_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dealer_invoice_id')->constrained('dealer_invoices')->cascadeOnDelete();
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                $table->string('description');
                $table->unsignedSmallInteger('days')->default(1);
                $table->unsignedInteger('unit_price_cents');
                $table->unsignedInteger('line_total_cents');
                $table->timestamp('created_at')->useCurrent();
            });
        }

        $this->seedPayAsYouGoPlans();
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_invoice_lines');
        Schema::dropIfExists('listing_billing_periods');
        Schema::dropIfExists('dealer_invoices');

        Schema::table('vehicles', function (Blueprint $table) {
            $columns = ['listing_billing_started_at', 'listing_billing_paused_at', 'expires_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('vehicles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('dealer_subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('dealer_subscriptions', 'billing_cycle')) {
                $table->dropColumn('billing_cycle');
            }
        });

        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'price_per_listing_per_day')) {
                $table->dropColumn('price_per_listing_per_day');
            }
            if (Schema::hasColumn('plans', 'billing_model')) {
                $table->dropColumn('billing_model');
            }
        });
    }

    private function seedPayAsYouGoPlans(): void
    {
        if (! Schema::hasTable('plans') || ! Schema::hasTable('features')) {
            return;
        }

        $dealerRole = DB::table('roles')->where('name', 'dealer')->first();
        if (! $dealerRole) {
            return;
        }

        $features = DB::table('features')->get()->keyBy('key');
        if ($features->isEmpty()) {
            return;
        }

        $plans = [
            [
                'slug' => 'basic-payg',
                'name' => 'Basic Pay As You Go',
                'description' => 'Pay per listing per day. Ideal for low inventory dealers.',
                'price_per_listing_per_day' => 190,
                'feature_values' => [
                    'max_listings' => '9999',
                    'enquiry_management' => 'true',
                    'lead_management' => 'true',
                    'staff_management' => 'false',
                    'max_feature_listings' => '0',
                    'priority_support' => 'false',
                    'analytics' => 'false',
                    'max_vehicle_images' => '10',
                    'max_equipment_per_vehicle' => '10',
                    'upload_3d_view' => 'false',
                    'audit_logs' => 'false',
                ],
            ],
            [
                'slug' => 'premium-payg',
                'name' => 'Premium Pay As You Go',
                'description' => 'Premium features with per listing per day billing.',
                'price_per_listing_per_day' => 290,
                'feature_values' => [
                    'max_listings' => '9999',
                    'enquiry_management' => 'true',
                    'lead_management' => 'true',
                    'staff_management' => 'true',
                    'max_feature_listings' => '10',
                    'priority_support' => 'true',
                    'analytics' => 'true',
                    'max_vehicle_images' => '20',
                    'max_equipment_per_vehicle' => '30',
                    'upload_3d_view' => 'true',
                    'audit_logs' => 'true',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            $existing = DB::table('plans')->where('slug', $planData['slug'])->first();
            if ($existing) {
                DB::table('plans')->where('id', $existing->id)->update([
                    'billing_model' => 'usage_daily',
                    'price_per_listing_per_day' => $planData['price_per_listing_per_day'],
                    'trial_days' => 0,
                    'is_active' => true,
                    'updated_at' => now(),
                ]);
                $planId = $existing->id;
            } else {
                $planId = DB::table('plans')->insertGetId([
                    'name' => $planData['name'],
                    'slug' => $planData['slug'],
                    'description' => $planData['description'],
                    'is_active' => true,
                    'trial_days' => 0,
                    'billing_model' => 'usage_daily',
                    'price_per_listing_per_day' => $planData['price_per_listing_per_day'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($planData['feature_values'] as $featureKey => $value) {
                $feature = $features->get($featureKey);
                if (! $feature) {
                    continue;
                }

                DB::table('plan_features')->updateOrInsert(
                    ['plan_id' => $planId, 'feature_id' => $feature->id],
                    ['value' => $value]
                );
            }

            DB::table('plan_availability')->updateOrInsert(
                [
                    'plan_id' => $planId,
                    'allowed_role_id' => $dealerRole->id,
                    'dealer_id' => null,
                ],
                ['is_enabled' => true, 'created_at' => now()]
            );
        }

        DB::table('plans')
            ->whereIn('slug', ['basic', 'professional'])
            ->update(['billing_model' => 'subscription', 'updated_at' => now()]);
    }
};

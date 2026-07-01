<?php

use App\Services\RolePermissionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $adminPermissions = [
            'admin.integrations.view',
            'admin.integrations.manage',
            'admin.dealers.view',
            'admin.dealers.update',
            'admin.crm-settings.manage',
        ];

        $staffPermissions = [
            'staff.dashboard.view',
            'staff.vehicles.view',
            'staff.vehicles.create',
            'staff.vehicles.update',
            'staff.vehicles.delete',
            'staff.vehicles.status',
            'staff.vehicles.media',
            'staff.leads.view',
            'staff.leads.assign',
            'staff.leads.update',
            'staff.leads.messages',
            'staff.enquiries.view',
            'staff.enquiries.update',
            'staff.subscription.view',
            'staff.audit.view',
            'staff.saved-searches.view',
            'staff.saved-searches.manage',
            'staff.crm.notes',
            'staff.crm.tasks',
        ];

        $dealerCrmPermissions = [
            'dealer.crm.notes',
            'dealer.crm.tasks',
            'dealer.saved-searches.view',
            'dealer.saved-searches.manage',
        ];

        $rolePermissionService = app(RolePermissionService::class);

        foreach (array_merge($adminPermissions, $staffPermissions, $dealerCrmPermissions) as $permission) {
            $rolePermissionService->createPermission($permission, 'web');
        }

        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($adminPermissions);
        }

        $staffRole = Role::where('name', 'staff')->where('guard_name', 'web')->first();
        if (! $staffRole) {
            $staffRole = $rolePermissionService->createRole('staff', 'web');
        }
        $staffRole->syncPermissions($staffPermissions);

        $dealerRole = Role::where('name', 'dealer')->where('guard_name', 'web')->first();
        if ($dealerRole) {
            $dealerRole->givePermissionTo($dealerCrmPermissions);
        }

        if (! Schema::hasTable('lead_lost_reasons')) {
            Schema::create('lead_lost_reasons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dealer_id')->nullable()->constrained('dealers')->cascadeOnDelete();
                $table->string('name');
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lead_notes')) {
            Schema::create('lead_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('body');
                $table->boolean('is_pinned')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lead_tasks')) {
            Schema::create('lead_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
                $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('priority', 16)->default('normal');
                $table->timestamp('due_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lead_activities')) {
            Schema::create('lead_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('type', 64);
                $table->string('title');
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('lead_reminders')) {
            Schema::create('lead_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('remind_at');
                $table->string('channel', 32)->default('in_app');
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                if (! Schema::hasColumn('leads', 'lost_reason_id')) {
                    $table->foreignId('lost_reason_id')->nullable()->after('lead_category_id')
                        ->constrained('lead_lost_reasons')->nullOnDelete();
                }
                if (! Schema::hasColumn('leads', 'utm_source')) {
                    $table->string('utm_source')->nullable()->after('lost_reason_id');
                }
                if (! Schema::hasColumn('leads', 'utm_medium')) {
                    $table->string('utm_medium')->nullable()->after('utm_source');
                }
                if (! Schema::hasColumn('leads', 'utm_campaign')) {
                    $table->string('utm_campaign')->nullable()->after('utm_medium');
                }
                if (! Schema::hasColumn('leads', 'referrer_url')) {
                    $table->string('referrer_url', 512)->nullable()->after('utm_campaign');
                }
                if (! Schema::hasColumn('leads', 'first_contacted_at')) {
                    $table->timestamp('first_contacted_at')->nullable()->after('last_activity_at');
                }
            });
        }

        $defaults = [
            ['dealer_id' => null, 'name' => 'Price too high', 'sort_order' => 1],
            ['dealer_id' => null, 'name' => 'Bought elsewhere', 'sort_order' => 2],
            ['dealer_id' => null, 'name' => 'No response', 'sort_order' => 3],
            ['dealer_id' => null, 'name' => 'Financing declined', 'sort_order' => 4],
            ['dealer_id' => null, 'name' => 'Other', 'sort_order' => 99],
        ];
        foreach ($defaults as $row) {
            if (! \Illuminate\Support\Facades\DB::table('lead_lost_reasons')->where('dealer_id', $row['dealer_id'])->where('name', $row['name'])->exists()) {
                \Illuminate\Support\Facades\DB::table('lead_lost_reasons')->insert(array_merge($row, [
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        $platformSettingService = app(\App\Services\PlatformSettingService::class);
        $platformSettingService->set('crm', 'email_on_new_lead', 'true');
        $platformSettingService->set('crm', 'stale_lead_hours', '24');
        $platformSettingService->set('payment', 'stripe_enabled', 'false');
        $platformSettingService->set('payment', 'stripe_mode', 'test');
        $platformSettingService->set('payment', 'instant_subscription_checkout', 'true');
        $platformSettingService->set('ai', 'openai_enabled', 'false');
        $platformSettingService->set('ai', 'anthropic_enabled', 'false');
        $platformSettingService->set('ai', 'gemini_enabled', 'false');

        $rolePermissionService->clearCaches();
    }

    public function down(): void
    {
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                foreach (['lost_reason_id', 'utm_source', 'utm_medium', 'utm_campaign', 'referrer_url', 'first_contacted_at'] as $col) {
                    if (Schema::hasColumn('leads', $col)) {
                        if ($col === 'lost_reason_id') {
                            $table->dropForeign(['lost_reason_id']);
                        }
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('lead_reminders');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('lead_tasks');
        Schema::dropIfExists('lead_notes');
        Schema::dropIfExists('lead_lost_reasons');
    }
};

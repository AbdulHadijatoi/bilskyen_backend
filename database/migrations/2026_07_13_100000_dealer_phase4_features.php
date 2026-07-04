<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('dealer_marketing_campaigns')) {
            Schema::create('dealer_marketing_campaigns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name', 255);
                $table->string('type', 32)->default('email');
                $table->string('audience', 64)->default('all_leads');
                $table->string('subject', 255)->nullable();
                $table->text('body')->nullable();
                $table->string('status', 32)->default('draft')->index();
                $table->timestamp('scheduled_at')->nullable()->index();
                $table->unsignedInteger('sent_count')->default(0);
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('dealer_deal_quotes')) {
            Schema::create('dealer_deal_quotes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dealer_id')->constrained('dealers')->cascadeOnDelete();
                $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('list_price')->default(0);
                $table->unsignedInteger('discount_amount')->default(0);
                $table->unsignedInteger('trade_in_value')->default(0);
                $table->decimal('finance_apr', 5, 2)->nullable();
                $table->unsignedSmallInteger('finance_term_months')->nullable();
                $table->unsignedInteger('monthly_payment')->nullable();
                $table->text('notes')->nullable();
                $table->string('status', 32)->default('draft')->index();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dealer_deal_quotes');
        Schema::dropIfExists('dealer_marketing_campaigns');
    }
};

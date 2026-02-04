<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            // User agent as separate column for easier querying (currently stored in metadata)
            $table->text('user_agent')->nullable()->after('ip_address');
            
            // Dealer context - track which dealer the action relates to (for staff/admin actions)
            $table->unsignedBigInteger('dealer_id')->nullable()->after('actor_id');
            
            // Session tracking
            $table->string('session_id', 255)->nullable()->after('user_agent');
            
            // Request correlation ID for tracking related requests
            $table->string('request_id', 255)->nullable()->after('session_id');
            
            // Action status: success, failure, partial
            $table->enum('status', ['success', 'failure', 'partial'])->default('success')->after('action');
            
            // Error message if action failed
            $table->text('error_message')->nullable()->after('status');
            
            // Performance tracking - duration in milliseconds
            $table->unsignedInteger('duration_ms')->nullable()->after('error_message');
            
            // HTTP request details
            $table->string('request_method', 10)->nullable()->after('duration_ms'); // GET, POST, PUT, DELETE, PATCH
            $table->string('request_url', 500)->nullable()->after('request_method');
            
            // Related target for tracking relationships (e.g., Vehicle related to Sale, Lead related to Enquiry)
            $table->string('related_target_type', 50)->nullable()->after('target_id');
            $table->unsignedBigInteger('related_target_id')->nullable()->after('related_target_type');
            
            // Human-readable description
            $table->text('description')->nullable()->after('related_target_id');
            
            // Tags for categorization and filtering (JSON array)
            $table->json('tags')->nullable()->after('description');
            
            // Severity/Level: info, warning, error, critical
            $table->enum('severity', ['info', 'warning', 'error', 'critical'])->default('info')->after('tags');
            
            // Add foreign key for dealer_id
            $table->foreign('dealer_id')->references('id')->on('dealers')->nullOnDelete();
            
            // Add indexes for better query performance
            $table->index('dealer_id');
            $table->index('status');
            $table->index('severity');
            $table->index('request_id');
            $table->index('session_id');
            $table->index(['related_target_type', 'related_target_id']);
            $table->index(['target_type', 'target_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['dealer_id']);
            $table->dropIndex(['dealer_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['severity']);
            $table->dropIndex(['request_id']);
            $table->dropIndex(['session_id']);
            $table->dropIndex(['related_target_type', 'related_target_id']);
            $table->dropIndex(['target_type', 'target_id']);
            $table->dropIndex(['created_at']);
            
            $table->dropColumn([
                'user_agent',
                'dealer_id',
                'session_id',
                'request_id',
                'status',
                'error_message',
                'duration_ms',
                'request_method',
                'request_url',
                'related_target_type',
                'related_target_id',
                'description',
                'tags',
                'severity',
            ]);
        });
    }
};

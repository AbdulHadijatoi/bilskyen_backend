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
        Schema::table('leads', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['buyer_user_id']);
            
            // Make the column nullable
            $table->unsignedBigInteger('buyer_user_id')->nullable()->change();
            
            // Re-add the foreign key constraint with nullOnDelete
            $table->foreign('buyer_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['buyer_user_id']);
            
            // Make the column non-nullable again
            $table->unsignedBigInteger('buyer_user_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint with cascadeOnDelete
            $table->foreign('buyer_user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Dealer;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->string('slug', 255)->nullable()->unique()->after('user_id');
            
            $table->index('slug');
        });

        // Generate slugs for existing dealers
        $this->generateSlugsForExistingDealers();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropColumn(['slug']);
        });
    }

    /**
     * Generate slugs for existing dealers
     */
    private function generateSlugsForExistingDealers(): void
    {
        $dealers = Dealer::with('owner')->get();
        
        foreach ($dealers as $dealer) {
            $slug = null;
            
            // Use owner's name to generate slug
            if ($dealer->owner && $dealer->owner->name) {
                $slug = Str::slug($dealer->owner->name);
            }
            // Fallback to CVR
            elseif ($dealer->cvr) {
                $slug = Str::slug($dealer->cvr);
            }
            
            // Ensure uniqueness
            if ($slug) {
                $originalSlug = $slug;
                $counter = 1;
                while (Dealer::where('slug', $slug)->where('id', '!=', $dealer->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                
                $dealer->slug = $slug;
                $dealer->save();
            }
        }
    }
};

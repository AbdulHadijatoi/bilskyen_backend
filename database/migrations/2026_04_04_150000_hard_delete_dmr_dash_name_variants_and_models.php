<?php

use App\Models\DmrModel;
use App\Models\DmrVariant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dmr_variants')) {
            $this->stripDoubleQuotesWhenUnique('dmr_variants', 'model_id');
        }

        if (Schema::hasTable('dmr_models')) {
            $this->stripDoubleQuotesWhenUnique('dmr_models', 'brand_id');
        }

        // delete variants where name contains no letters or numbers
        DmrVariant::whereNull('name')
            ->orWhereRaw("TRIM(name) = ''")
            ->orWhereRaw("name NOT REGEXP '[a-zA-Z0-9]'")
            ->delete();

        // delete models where name contains no letters or numbers
        DmrModel::whereNull('name')
            ->orWhereRaw("TRIM(name) = ''")
            ->orWhereRaw("name NOT REGEXP '[a-zA-Z0-9]'")
            ->delete();
    }

    public function down(): void
    {
        // cannot restore deleted records
    }

    /**
     * Remove ASCII double quotes from `name` only when no other row shares the same
     * parent FK and already uses the cleaned name (preserves uq_* unique constraints).
     * Rows are processed by ascending id so duplicate quoted rows (e.g. two "90") only
     * the first keeps the cleaned name.
     */
    private function stripDoubleQuotesWhenUnique(string $table, string $parentKey): void
    {
        DB::table($table)
            ->whereNotNull('name')
            ->where('name', 'LIKE', '%"%')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($table, $parentKey) {
                foreach ($rows as $row) {
                    $clean = str_replace('"', '', $row->name);
                    if ($clean === $row->name) {
                        continue;
                    }
                    $parentId = $row->{$parentKey};
                    if ($parentId === null) {
                        continue;
                    }
                    $conflicts = DB::table($table)
                        ->where($parentKey, $parentId)
                        ->where('id', '!=', $row->id)
                        ->where('name', $clean)
                        ->exists();
                    if (! $conflicts) {
                        DB::table($table)->where('id', $row->id)->update(['name' => $clean]);
                    }
                }
            });
    }
};
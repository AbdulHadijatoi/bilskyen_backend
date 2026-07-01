<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DmrModel extends Model
{
    use SoftDeletes;

    protected $table = 'dmr_models';

    public $timestamps = false;

    protected $fillable = [
        'brand_id',
        'name',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(DmrBrand::class, 'brand_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(DmrVariant::class, 'model_id');
    }

    /**
     * Short label for dropdowns (API payloads): at most the first two whitespace-separated words.
     * Does not change stored {@see $name}.
     */
    public static function dropdownDisplayName(string $name): string
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return '';
        }

        $parts = preg_split('/\s+/u', $trimmed, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false || $parts === []) {
            return $trimmed;
        }

        return implode(' ', array_slice($parts, 0, 2));
    }
}

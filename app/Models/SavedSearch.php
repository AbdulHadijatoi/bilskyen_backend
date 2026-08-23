<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'name',
        'filters',
        'created_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get user for this saved search
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Query string for reopening this search on /biler.
     *
     * @param  array<string, mixed>|null  $filters
     */
    public static function toVehiclesQuery(?array $filters): string
    {
        $multi = ['brand_id', 'model_id', 'fuel_type_id', 'body_type_id', 'gear_type_id', 'listing_type_id'];
        $pairs = [];
        foreach ($filters ?? [] as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (is_array($value)) {
                foreach ($value as $item) {
                    if ($item === null || $item === '') {
                        continue;
                    }
                    $name = in_array((string) $key, $multi, true) ? $key.'[]' : (string) $key;
                    $pairs[] = rawurlencode($name).'='.rawurlencode((string) $item);
                }

                continue;
            }
            $name = in_array((string) $key, $multi, true) ? $key.'[]' : (string) $key;
            $pairs[] = rawurlencode($name).'='.rawurlencode((string) $value);
        }

        return implode('&', $pairs);
    }
}

<?php

namespace App\Models;

use App\Traits\FirstOrCreateInsensitive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListingType extends Model
{
    use FirstOrCreateInsensitive;
    use SoftDeletes;

    /** Purchase — default {@see Vehicle::$listing_type_id} when none is provided on create. */
    public const PURCHASE_ID = 1;

    public $timestamps = false;

    /**
     * @param  mixed  $value  Raw listing_type_id from request or mass-assignment
     */
    public static function idOrDefaultPurchase(mixed $value): int
    {
        if ($value === null || $value === '') {
            return self::PURCHASE_ID;
        }

        $id = (int) $value;

        return $id > 0 ? $id : self::PURCHASE_ID;
    }

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
    ];
}


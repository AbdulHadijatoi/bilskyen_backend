<?php

namespace App\Models;

use App\Traits\FirstOrCreateInsensitive;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesType extends Model
{
    use FirstOrCreateInsensitive;
    use SoftDeletes;

    public $timestamps = false;

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $fillable = [
        'name',
    ];

    /**
     * Excel/extension may send "Leasing"; seeded lookup name is "Leasingdetaljer".
     */
    public static function isLeasingName(?string $name): bool
    {
        $key = mb_strtolower(trim((string) $name));

        return in_array($key, ['leasing', 'leasingdetaljer', 'lease'], true);
    }
}


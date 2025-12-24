<?php

namespace App\Traits;

trait HasSerialNumber
{
    /**
     * Boot the trait and set up serial number generation
     */
    protected static function bootHasSerialNumber(): void
    {
        static::creating(function ($model) {
            if (empty($model->serial_no)) {
                $model->serial_no = static::max('serial_no') + 1;
            }
        });
    }
}



<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleDetail extends Model
{
    protected $fillable = [
        'vehicle_id',
        'description',
        'views_count',
        'vin_location',
        'type',
        'version',
        'type_name',
        'registration_status',
        'registration_status_updated_date',
        'expire_date',
        'status_updated_date',
        'model_year',
        'total_weight',
        'vehicle_weight',
        'technical_total_weight',
        'coupling',
        'towing_weight_brakes',
        'minimum_weight',
        'gross_combination_weight',
        'fuel_efficiency',
        'engine_displacement',
        'engine_cylinders',
        'engine_code',
        'category',
        'last_inspection_date',
        'last_inspection_result',
        'last_inspection_odometer',
        'type_approval_code',
        'top_speed',
        'doors',
        'minimum_seats',
        'maximum_seats',
        'wheels',
        'extra_equipment',
        'axles',
        'drive_axles',
        'wheelbase',
        'leasing_period_start',
        'leasing_period_end',
        'use',
        'color',
        'body_type',
        'dispensations',
        'permits',
        'equipment',
        'ncap_five',
        'airbags',
        'integrated_child_seats',
        'seat_belt_alarms',
        'euronorm',
    ];

    protected $casts = [
        'views_count' => 'integer',
        'total_weight' => 'integer',
        'vehicle_weight' => 'integer',
        'technical_total_weight' => 'integer',
        'coupling' => 'integer',
        'towing_weight_brakes' => 'integer',
        'minimum_weight' => 'integer',
        'gross_combination_weight' => 'integer',
        'fuel_efficiency' => 'decimal:2',
        'engine_displacement' => 'integer',
        'engine_cylinders' => 'integer',
        'last_inspection_odometer' => 'integer',
        'top_speed' => 'integer',
        'doors' => 'integer',
        'minimum_seats' => 'integer',
        'maximum_seats' => 'integer',
        'wheels' => 'integer',
        'axles' => 'integer',
        'drive_axles' => 'integer',
        'wheelbase' => 'integer',
        'equipment' => 'array',
        'ncap_five' => 'boolean',
        'airbags' => 'integer',
        'integrated_child_seats' => 'integer',
        'seat_belt_alarms' => 'integer',
        'registration_status_updated_date' => 'date',
        'expire_date' => 'date',
        'status_updated_date' => 'date',
        'last_inspection_date' => 'date',
        'leasing_period_start' => 'date',
        'leasing_period_end' => 'date',
    ];

    /**
     * Get vehicle for this detail
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }
}

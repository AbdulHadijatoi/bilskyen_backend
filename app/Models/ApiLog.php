<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory;

    protected $table = 'api_logs';

    public $timestamps = false;

    protected $fillable = [
        'api_service',
        'endpoint',
        'status_code',
        'execution_time_ms',
        'created_at',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'execution_time_ms' => 'integer',
        'created_at' => 'datetime',
    ];
}

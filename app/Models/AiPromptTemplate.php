<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPromptTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'system_prompt',
        'user_prompt_template',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadCategory extends Model
{
    use HasFactory;

    public const PRICE_NEGOTIATION_REQUEST = 1;
    public const FINANCING_REQUEST = 2;
    public const WHATSAPP_CLICKED = 3;
    public const EMAIL_CLICKED = 4;
    public const ENQUIRY_FORM_SUBMISSION = 5;
    public const PHONE_NUMBER_REVEALED = 6;
    public const REQUEST_TEST_DRIVE = 7;

    protected $fillable = [
        'name',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get leads in this category
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'lead_category_id');
    }
}

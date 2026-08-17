<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialRentDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'price_per_month',
        'deposit',
        'commission',
        'utilities_included',
        'rent_type',
    ];

    protected $casts = [
        'utilities_included' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(CommercialProperty::class, 'property_id');
    }
}

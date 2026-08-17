<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialSaleDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'price',
        'commission',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(CommercialProperty::class, 'property_id');
    }
}

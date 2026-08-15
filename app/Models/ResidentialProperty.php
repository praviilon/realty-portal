<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ResidentialProperty extends Model
{
    protected $fillable = [
        'user_id',
        'deal_type',
        'property_type',
        'address',
        'lat',
        'lng',
        'area',
        'floor',
        'total_floors',
        'price',
        'description',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photos(): MorphMany
    {
        return $this->morphMany(PropertyPhoto::class, 'photoable');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}

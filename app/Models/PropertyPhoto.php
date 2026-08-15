<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PropertyPhoto extends Model
{
    use HasFactory;

    protected $fillable = ['path', 'is_main', 'sort_order'];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    public function photoable(): MorphTo
    {
        return $this->morphTo();
    }
}

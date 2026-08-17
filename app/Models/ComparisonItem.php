<?php

namespace App\Models;

use Database\Factories\ComparisonItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ComparisonItem extends Model
{
    /** @use HasFactory<ComparisonItemFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'comparison_list_id',
        'comparable_type',
        'comparable_id',
        'added_at',
    ];

    protected function casts(): array
    {
        return [
            'added_at' => 'datetime',
        ];
    }

    public function comparisonList(): BelongsTo
    {
        return $this->belongsTo(ComparisonList::class);
    }

    public function comparable(): MorphTo
    {
        return $this->morphTo();
    }
}

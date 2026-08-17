<?php

namespace App\Models;

use Database\Factories\WorkspacePricingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspacePricing extends Model
{
    /** @use HasFactory<WorkspacePricingFactory> */
    use HasFactory;

    // Таблица называется workspace_pricing (см. раздел 3 плана), а не
    // workspace_pricings — Eloquent сам не угадает единственное число здесь.
    protected $table = 'workspace_pricing';

    protected $fillable = [
        'workspace_id',
        'period',
        'price',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function getPeriodLabelAttribute(): string
    {
        return Workspace::pricingPeriodLabels()[$this->period] ?? $this->period;
    }
}

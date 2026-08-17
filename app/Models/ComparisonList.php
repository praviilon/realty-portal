<?php

namespace App\Models;

use Database\Factories\ComparisonListFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComparisonList extends Model
{
    /** @use HasFactory<ComparisonListFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'list_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ComparisonItem::class);
    }

    public static function listTypeLabels(): array
    {
        return [
            'residential_sale' => 'Жильё — продажа',
            'residential_rent' => 'Жильё — аренда',
            'commercial_sale' => 'Коммерция — продажа',
            'commercial_rent' => 'Коммерция — аренда',
            'workspace' => 'Рабочие пространства',
        ];
    }
}

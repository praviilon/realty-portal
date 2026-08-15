<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'seller_id',
        'listable_type',
        'listable_id',
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function listable(): MorphTo
    {
        return $this->morphTo();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function otherParticipant(int $currentUserId): User
    {
        return $currentUserId === $this->buyer_id ? $this->seller : $this->buyer;
    }

    public function isParticipant(int $userId): bool
    {
        return $userId === $this->buyer_id || $userId === $this->seller_id;
    }

    /**
     * Находит существующий тред "покупатель ↔ продавец по объявлению" или
     * создаёт новый — кнопка "Написать продавцу" на детальной карточке (эпик 10).
     */
    public static function findOrCreateFor(User $buyer, Model $listable): self
    {
        $sellerId = $listable->user_id;

        if ($sellerId === $buyer->id) {
            throw new RuntimeException('Нельзя написать самому себе по своему же объявлению.');
        }

        return static::firstOrCreate([
            'buyer_id' => $buyer->id,
            'seller_id' => $sellerId,
            'listable_type' => $listable::class,
            'listable_id' => $listable->id,
        ]);
    }
}

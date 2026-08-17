<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['first_name', 'last_name', 'email', 'phone', 'password', 'avatar_path', 'role', 'name'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Доступ в панель Filament (/admin) — только у супер-пользователя(ей) с role=admin.
     * Создаётся командой `php artisan make:filament-user`.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin';
    }

    public function residentialProperties(): HasMany
    {
        return $this->hasMany(ResidentialProperty::class);
    }

    public function commercialProperties(): HasMany
    {
        return $this->hasMany(CommercialProperty::class);
    }

    public function chatsAsBuyer(): HasMany
    {
        return $this->hasMany(Chat::class, 'buyer_id');
    }

    public function chatsAsSeller(): HasMany
    {
        return $this->hasMany(Chat::class, 'seller_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Имя пользователя для интерфейса Filament (шапка админ-панели, аватар-заглушка).
     */
    public function getFilamentName(): string
    {
        return $this->full_name;
    }

    /**
     * Аватар пользователя для интерфейса Filament — эпик 9 дорожной карты
     * (Intervention Image, серверный ресайз в квадрат 256×256 WebP).
     */
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_path ? Storage::url($this->avatar_path) : null;
    }

    /**
     * Виртуальный сеттер "name" — совместимость с `php artisan make:filament-user`
     * (команда Filament всегда создаёт пользователя через поле name). В схеме
     * проекта своей колонки name нет, поэтому значение разбирается на first_name/last_name.
     */
    public function setNameAttribute(string $value): void
    {
        [$first, $last] = array_pad(explode(' ', trim($value), 2), 2, null);

        $this->attributes['first_name'] = $first;
        $this->attributes['last_name'] = $last;
    }
}

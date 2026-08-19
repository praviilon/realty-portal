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
     * Временный дефолтный пароль, на который администратор может сбросить
     * пароль пользователя из админ-панели (эпик: управление пользователями).
     * Это осознанное временное решение — на сервере нет реального почтового
     * сервера, поэтому отправить пользователю ссылку для сброса пароля
     * невозможно, и учётные данные приходится сообщать в обход e-mail.
     * Значение подобрано так, чтобы проходить App\Rules\PasswordPolicy
     * (6–60 символов, только латиница, обязательны строчные+заглавные+цифры).
     */
    public const DEFAULT_RESET_PASSWORD = 'ChangeMe123';

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
            'last_login_at' => 'datetime',
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

    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class);
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

    public function comparisonLists(): HasMany
    {
        return $this->hasMany(ComparisonList::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Полное удаление пользователя вместе со всеми его данными — по
     * просьбе пользователя ("удаление реально удаляло фото и уведомления").
     *
     * Раньше и самостоятельное удаление профиля
     * (resources/views/livewire/profile/delete-user-form.blade.php), и
     * удаление администратором (App\Filament\Resources\UserResource)
     * просто вызывали ->delete() на модели, полагаясь на каскадные внешние
     * ключи миграций. Это действительно удаляет сами объявления
     * (residential_properties/commercial_properties/workspaces —
     * foreignId('user_id')->cascadeOnDelete()), но НЕ фото объявлений и НЕ
     * уведомления: обе эти таблицы — полиморфные связи без внешнего ключа
     * на уровне БД (см. миграции create_property_photos_table и
     * create_notifications_table), поэтому ON DELETE CASCADE в принципе не
     * может их подчистить. Более того, каскад через FK выполняется на
     * уровне СУБД и не поднимает события Eloquent-моделей, так что даже
     * добавленный в PropertyPhoto::booted() хук 'deleting' не сработал бы
     * для фото объявлений, удалённых таким каскадом.
     *
     * Поэтому здесь фото и уведомления удаляются явно, через сами модели
     * (чтобы у PropertyPhoto сработало событие 'deleting' и файл в storage
     * тоже удалился — см. App\Models\PropertyPhoto), и только после этого
     * удаляется сам пользователь (объявления и всё остальное с
     * cascadeOnDelete по-прежнему подчищаются штатным каскадом БД).
     */
    public function deleteAccount(): void
    {
        foreach (['residentialProperties', 'commercialProperties', 'workspaces'] as $relation) {
            $this->$relation->each(function ($listing) {
                $listing->photos()->get()->each->delete();
            });
        }

        // У уведомлений нет файлов в storage — обычный bulk delete() безопасен.
        $this->notifications()->delete();

        if ($this->avatar_path) {
            Storage::disk('public')->delete($this->avatar_path);
        }

        $this->delete();
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

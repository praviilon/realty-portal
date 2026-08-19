<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Доработка по просьбе пользователя: список всех пользователей сервиса
 * в админ-панели, с возможностью удалить пользователя (кроме админов) и
 * сбросить его пароль на временный дефолтный (см. User::DEFAULT_RESET_PASSWORD).
 *
 * Ресурс сознательно без формы create/edit — управление профилем
 * пользователя вне задачи, здесь только просмотр списка и два действия.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Пользователи';

    protected static ?string $modelLabel = 'пользователь';

    protected static ?string $pluralModelLabel = 'пользователи';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount([
                'residentialProperties',
                'commercialProperties',
                'workspaces',
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Имя')
                    ->searchable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Роль')
                    ->badge()
                    ->color(fn (string $state) => $state === 'admin' ? 'danger' : 'gray')
                    ->formatStateUsing(fn (string $state) => $state === 'admin' ? 'Админ' : 'Пользователь'),
                // ?? -> запасной вариант на случай, если счётчики не были
                // подгружены через withCount() (например, при прямом
                // обращении к колонке в тестах через assertTableColumnStateSet).
                Tables\Columns\TextColumn::make('listings_count')
                    ->label('Объявлений')
                    ->state(fn (User $record) => ($record->residential_properties_count ?? $record->residentialProperties()->count())
                        + ($record->commercial_properties_count ?? $record->commercialProperties()->count())
                        + ($record->workspaces_count ?? $record->workspaces()->count())),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Дата регистрации')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Последний вход')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Роль')
                    ->options([
                        'user' => 'Пользователь',
                        'admin' => 'Админ',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('resetPassword')
                    ->label('Сбросить пароль')
                    ->color('warning')
                    ->icon('heroicon-o-key')
                    ->requiresConfirmation()
                    ->modalHeading('Сбросить пароль пользователя')
                    ->modalDescription(
                        'Пароль будет заменён на временный дефолтный: '.User::DEFAULT_RESET_PASSWORD
                        .'. Сообщите его пользователю самостоятельно — письмо не отправляется.'
                    )
                    ->modalSubmitActionLabel('Сбросить')
                    ->action(function (User $record) {
                        $record->update(['password' => User::DEFAULT_RESET_PASSWORD]);

                        Notification::make()
                            ->title('Пароль сброшен')
                            ->body('Временный пароль для '.$record->email.': '.User::DEFAULT_RESET_PASSWORD)
                            ->success()
                            ->persistent()
                            ->send();
                    }),
                Tables\Actions\Action::make('deleteUser')
                    ->label('Удалить')
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading('Удалить пользователя')
                    ->modalDescription('Будут безвозвратно удалены профиль пользователя и все его объявления. Это действие необратимо.')
                    ->modalSubmitActionLabel('Удалить')
                    ->visible(fn (User $record) => $record->role !== 'admin')
                    ->action(function (User $record) {
                        // Та же логика, что и при самостоятельном удалении
                        // профиля пользователем (см.
                        // resources/views/livewire/profile/delete-user-form.blade.php):
                        // User::deleteAccount() явно чистит фото объявлений
                        // (файлы + записи) и уведомления, а не только сами
                        // объявления — каскадные внешние ключи миграций эти
                        // две полиморфные таблицы не подчищают (см.
                        // подробный комментарий в App\Models\User::deleteAccount()).
                        $record->deleteAccount();

                        Notification::make()
                            ->title('Пользователь удалён')
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }
}

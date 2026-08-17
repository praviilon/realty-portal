<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkspaceResource\Pages;
use App\Models\Workspace;
use App\Notifications\ListingStatusChanged;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Модерация рабочих пространств — эпик 24 дорожной карты (Веха 3), по
 * образцу CommercialPropertyResource (эпик 14).
 */
class WorkspaceResource extends Resource
{
    protected static ?string $model = Workspace::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Рабочие пространства';

    protected static ?string $modelLabel = 'объявление (рабочее пространство)';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('status')
                ->label('Статус')
                ->options([
                    'moderation' => 'На модерации',
                    'active' => 'Активно',
                    'rejected' => 'Отклонено',
                    'archived' => 'В архиве',
                ])
                ->required(),
            Forms\Components\Textarea::make('rejection_reason')
                ->label('Причина отклонения')
                ->visible(fn (Forms\Get $get) => $get('status') === 'rejected'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('address')->label('Адрес')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('workspace_type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Workspace::workspaceTypeLabels()[$state] ?? $state),
                Tables\Columns\TextColumn::make('display_price')
                    ->label('Цена от')
                    ->money('rub')
                    ->getStateUsing(fn (Workspace $record) => $record->display_price),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'active' => 'success',
                        'moderation' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('user.full_name')->label('Автор'),
                Tables\Columns\TextColumn::make('created_at')->label('Создано')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'moderation' => 'На модерации',
                        'active' => 'Активно',
                        'rejected' => 'Отклонено',
                        'archived' => 'В архиве',
                    ]),
                Tables\Filters\SelectFilter::make('workspace_type')
                    ->label('Тип')
                    ->options(Workspace::workspaceTypeLabels()),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Одобрить')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->visible(fn (Workspace $record) => $record->status === 'moderation')
                    ->action(function (Workspace $record) {
                        $record->update(['status' => 'active']);
                        $record->user->notify(new ListingStatusChanged($record));
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (Workspace $record) => $record->status === 'moderation')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')->label('Причина')->required(),
                    ])
                    ->action(function (Workspace $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                        $record->user->notify(new ListingStatusChanged($record));
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkspaces::route('/'),
            'edit' => Pages\EditWorkspace::route('/{record}/edit'),
        ];
    }
}

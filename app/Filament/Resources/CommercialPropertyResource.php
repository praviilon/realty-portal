<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommercialPropertyResource\Pages;
use App\Models\CommercialProperty;
use App\Notifications\ListingStatusChanged;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CommercialPropertyResource extends Resource
{
    protected static ?string $model = CommercialProperty::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Коммерческая недвижимость';

    protected static ?string $modelLabel = 'объявление (коммерция)';

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
                Tables\Columns\TextColumn::make('deal_type')
                    ->label('Сделка')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => CommercialProperty::dealTypeLabels()[$state] ?? $state),
                Tables\Columns\TextColumn::make('purpose_type')
                    ->label('Назначение')
                    ->formatStateUsing(fn (string $state) => CommercialProperty::purposeTypeLabels()[$state] ?? $state),
                Tables\Columns\TextColumn::make('display_price')
                    ->label('Цена')
                    ->money('rub')
                    ->getStateUsing(fn (CommercialProperty $record) => $record->display_price),
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
                Tables\Filters\SelectFilter::make('deal_type')
                    ->label('Сделка')
                    ->options(CommercialProperty::dealTypeLabels()),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Одобрить')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->visible(fn (CommercialProperty $record) => $record->status === 'moderation')
                    ->action(function (CommercialProperty $record) {
                        $record->update(['status' => 'active']);
                        $record->user->notify(new ListingStatusChanged($record));
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (CommercialProperty $record) => $record->status === 'moderation')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')->label('Причина')->required(),
                    ])
                    ->action(function (CommercialProperty $record, array $data) {
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
            'index' => Pages\ListCommercialProperties::route('/'),
            'edit' => Pages\EditCommercialProperty::route('/{record}/edit'),
        ];
    }
}

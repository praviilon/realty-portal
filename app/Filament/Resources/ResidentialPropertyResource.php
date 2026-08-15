<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResidentialPropertyResource\Pages;
use App\Models\ResidentialProperty;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ResidentialPropertyResource extends Resource
{
    protected static ?string $model = ResidentialProperty::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Жилая недвижимость';

    protected static ?string $modelLabel = 'объявление (жильё)';

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
                    ->formatStateUsing(fn (string $state) => ResidentialProperty::dealTypeLabels()[$state] ?? $state),
                Tables\Columns\TextColumn::make('property_type')
                    ->label('Тип')
                    ->formatStateUsing(fn (string $state) => ResidentialProperty::propertyTypeLabels()[$state] ?? $state),
                Tables\Columns\TextColumn::make('price')->label('Цена')->money('rub'),
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
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Одобрить')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->visible(fn (ResidentialProperty $record) => $record->status === 'moderation')
                    ->action(fn (ResidentialProperty $record) => $record->update(['status' => 'active'])),
                Tables\Actions\Action::make('reject')
                    ->label('Отклонить')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn (ResidentialProperty $record) => $record->status === 'moderation')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')->label('Причина')->required(),
                    ])
                    ->action(function (ResidentialProperty $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResidentialProperties::route('/'),
            'edit' => Pages\EditResidentialProperty::route('/{record}/edit'),
        ];
    }
}

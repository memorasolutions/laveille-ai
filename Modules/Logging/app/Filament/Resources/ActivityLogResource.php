<?php

declare(strict_types=1);

namespace Modules\Logging\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Logging\Filament\Resources\ActivityLogResource\Pages;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'Système';

    protected static ?int $navigationSort = 99;

    protected static ?string $navigationLabel = 'Journal d\'activité';

    protected static ?string $modelLabel = 'activité';

    protected static ?string $pluralModelLabel = 'activités';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Forms\Components\TextInput::make('log_name')
                ->label('Journal')
                ->disabled(),
            \Filament\Forms\Components\TextInput::make('description')
                ->disabled(),
            \Filament\Forms\Components\TextInput::make('subject_type')
                ->label('Type sujet')
                ->disabled(),
            \Filament\Forms\Components\TextInput::make('event')
                ->label('Événement')
                ->disabled(),
            \Filament\Forms\Components\KeyValue::make('properties')
                ->label('Propriétés')
                ->disabled(),
            \Filament\Forms\Components\TextInput::make('created_at')
                ->label('Date')
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Journal')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(60),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Sujet')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Par')
                    ->default('-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('event')
                    ->label('Événement')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Journal')
                    ->options(fn () => Activity::distinct()->pluck('log_name', 'log_name')->toArray()),
                Tables\Filters\SelectFilter::make('event')
                    ->label('Événement')
                    ->options([
                        'created' => 'Créé',
                        'updated' => 'Modifié',
                        'deleted' => 'Supprimé',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}

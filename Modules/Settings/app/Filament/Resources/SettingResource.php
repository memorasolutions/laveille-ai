<?php

declare(strict_types=1);

namespace Modules\Settings\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Settings\Filament\Resources\SettingResource\Pages;
use Modules\Settings\Models\Setting;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Système';

    protected static ?int $navigationSort = 100;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            \Filament\Forms\Components\TextInput::make('key')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            \Filament\Forms\Components\Select::make('group')
                ->options([
                    'general' => 'Général',
                    'mail' => 'Courriels',
                    'seo' => 'SEO',
                    'theme' => 'Thème',
                    'api' => 'API',
                ])
                ->default('general')
                ->required(),
            \Filament\Forms\Components\Select::make('type')
                ->options([
                    'string' => 'Texte',
                    'boolean' => 'Booléen',
                    'integer' => 'Nombre',
                    'json' => 'JSON',
                ])
                ->default('string')
                ->required(),
            \Filament\Forms\Components\Textarea::make('value')
                ->maxLength(65535),
            \Filament\Forms\Components\Textarea::make('description')
                ->maxLength(255),
            \Filament\Forms\Components\Toggle::make('is_public')
                ->label('Public'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('value')
                    ->limit(50),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_public')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->options([
                        'general' => 'Général',
                        'mail' => 'Courriels',
                        'seo' => 'SEO',
                        'theme' => 'Thème',
                        'api' => 'API',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSettings::route('/'),
            'create' => Pages\CreateSetting::route('/create'),
            'edit' => Pages\EditSetting::route('/{record}/edit'),
        ];
    }
}

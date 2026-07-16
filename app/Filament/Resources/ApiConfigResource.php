<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiConfigResource\Pages;
use App\Filament\Resources\ApiConfigResource\RelationManagers;
use App\Models\ApiConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ApiConfigResource extends Resource
{
    protected static ?string $model = ApiConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('network_type')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('api_name')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('api_endpoint')
                    ->required()
                    ->maxLength(500),
                Forms\Components\TextInput::make('api_key')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('api_key_id')
                    ->maxLength(100)
                    ->default(null),
                Forms\Components\TextInput::make('api_secret')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('request_method')
                    ->required()
                    ->maxLength(10)
                    ->default('POST'),
                Forms\Components\Textarea::make('request_headers')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('request_body_template')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('response_success_field')
                    ->required()
                    ->maxLength(100)
                    ->default('success'),
                Forms\Components\TextInput::make('response_data_field')
                    ->maxLength(100)
                    ->default(null),
                Forms\Components\TextInput::make('response_error_field')
                    ->maxLength(100)
                    ->default(null),
                Forms\Components\TextInput::make('timeout_seconds')
                    ->required()
                    ->numeric()
                    ->default(30),
                Forms\Components\TextInput::make('retry_attempts')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('status_endpoint')
                    ->maxLength(500)
                    ->default(null),
                Forms\Components\TextInput::make('balance_endpoint')
                    ->maxLength(500)
                    ->default(null),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('network_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('api_name')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('api_endpoint')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('request_method')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('response_success_field')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('response_data_field')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('timeout_seconds')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('retry_attempts')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('network_type')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiConfigs::route('/'),
            'create' => Pages\CreateApiConfig::route('/create'),
            'edit' => Pages\EditApiConfig::route('/{record}/edit'),
        ];
    }
}


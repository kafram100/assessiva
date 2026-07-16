<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MtnMashupResource\Pages;
use App\Models\AgentPackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MtnMashupResource extends Resource
{
    protected static ?string $model = AgentPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 4;

    public static function getLabel(): string
    {
        return 'MTN Mashup';
    }

    public static function getPluralLabel(): string
    {
        return 'MTN Mashup';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('manual_fulfillment', true);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('role')
                    ->default('all'),
                Forms\Components\Select::make('network_type')
                    ->required()
                    ->options([
                        'MTN' => 'MTN',
                    ])
                    ->default('MTN')
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\TextInput::make('package_name')
                    ->required()
                    ->maxLength(120)
                    ->default('MTN Mashup'),
                Forms\Components\TextInput::make('package_size_gb')
                    ->required()
                    ->numeric()
                    ->default(1.0)
                    ->label('Data Size (GB)'),
                Forms\Components\TextInput::make('voice_minutes')
                    ->numeric()
                    ->default(0)
                    ->label('Voice Minutes'),
                Forms\Components\Toggle::make('non_expiring')
                    ->default(true)
                    ->label('Non-Expiring'),
                Forms\Components\Hidden::make('package_size_mb')
                    ->default(0),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->default(5.00),
                Forms\Components\TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->required()
                    ->default(true),
                Forms\Components\Hidden::make('manual_fulfillment')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('package_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('package_size_gb')
                    ->label('Data (GB)')
                    ->numeric(decimalPlaces: 1)
                    ->sortable(),
                Tables\Columns\TextColumn::make('voice_minutes')
                    ->label('Voice (mins)')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? $state . ' mins' : '—'),
                Tables\Columns\IconColumn::make('non_expiring')
                    ->label('Non-Expiring')
                    ->boolean(),
                Tables\Columns\TextColumn::make('price')
                    ->money('GHS')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalWidth('md'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('md'),
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
            'index' => Pages\ListMtnMashups::route('/'),
        ];
    }
}

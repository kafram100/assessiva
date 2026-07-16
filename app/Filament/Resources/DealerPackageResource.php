<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DealerPackageResource\Pages;
use App\Models\AgentPackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DealerPackageResource extends Resource
{
    protected static ?string $model = AgentPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $label = 'Dealer Package';

    protected static ?string $pluralLabel = 'Dealer Packages';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'dealer');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('role')
                    ->required()
                    ->maxLength(20)
                    ->default('dealer')
                    ->readOnly(),
                Forms\Components\Select::make('network_type')
                    ->required()
                    ->options(function ($record) {
                        $networks = [
                            'MTN' => 'MTN',
                            'VODAFONE' => 'Telecel',
                            'AIRTELTIGO' => 'AirtelTigo',
                        ];
                        if (!$record) {
                            $networks['ALL'] = 'All Networks';
                        }
                        return $networks;
                    }),
                Forms\Components\TextInput::make('package_name')
                    ->required()
                    ->maxLength(120),
                Forms\Components\TextInput::make('package_size_gb')
                    ->required()
                    ->numeric()
                    ->default(0.0000)
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) =>
                        $set('package_size_mb', $state ? (int) round((float) $state * 1024) : 0)
                    ),
                Forms\Components\TextInput::make('package_size_mb')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->readOnly(),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
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
                Tables\Columns\TextColumn::make('package_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('package_size_gb')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('package_size_mb')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
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
            ->filters([
                Tables\Filters\SelectFilter::make('network_type')
                    ->options([
                        'MTN' => 'MTN',
                        'VODAFONE' => 'Telecel',
                        'AIRTELTIGO' => 'AirtelTigo',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalWidth('md')
                    ->using(function (array $data) {
                        if (($data['network_type'] ?? '') === 'ALL') {
                            $networks = ['MTN', 'VODAFONE', 'AIRTELTIGO'];
                            $record = null;
                            foreach ($networks as $network) {
                                $data['network_type'] = $network;
                                $record = AgentPackage::create($data);
                            }
                            return $record;
                        }
                        return AgentPackage::create($data);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('md'),
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
            'index' => Pages\ListDealerPackages::route('/'),
            'create' => Pages\CreateDealerPackage::route('/create'),
            'edit' => Pages\EditDealerPackage::route('/{record}/edit'),
        ];
    }
}



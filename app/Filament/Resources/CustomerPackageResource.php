<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerPackageResource\Pages;
use App\Filament\Resources\CustomerPackageResource\RelationManagers;
use App\Models\CustomerPackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerPackageResource extends Resource
{
    protected static ?string $model = CustomerPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->maxLength(100),
                Forms\Components\TextInput::make('package_size_gb')
                    ->label('Package Size (GB)')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->reactive()
                    ->debounce(800)
                    ->afterStateUpdated(fn ($state, callable $set) =>
                        $set('package_size', $state ? ((int) round((float) $state * 1024)) . ' MB' : '')
                    )
                    ->formatStateUsing(function ($record) {
                        if (!$record) return null;
                        $size = $record->package_size;
                        if (preg_match('/^(\d+)\s*MB$/i', $size, $m)) return (int) $m[1] / 1024;
                        if (preg_match('/^([\d.]+)\s*GB$/i', $size, $m)) return (float) $m[1];
                        return null;
                    })
                    ->dehydrated(false),
                Forms\Components\TextInput::make('package_size')
                    ->required()
                    ->maxLength(50)
                    ->readOnly(),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric(),
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
                Tables\Columns\TextColumn::make('package_size')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->numeric(decimalPlaces: 2)
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
                                $record = CustomerPackage::create($data);
                            }
                            return $record;
                        }
                        return CustomerPackage::create($data);
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
            'index' => Pages\ListCustomerPackages::route('/'),
        ];
    }
}






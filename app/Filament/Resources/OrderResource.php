<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('package_size')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('processing_fee')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('beneficiary_number')
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('network_type')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(20)
                    ->default('pending'),
                Forms\Components\TextInput::make('order_reference')
                    ->maxLength(100)
                    ->default(null),
                Forms\Components\TextInput::make('reference')
                    ->maxLength(100)
                    ->default(null),
                Forms\Components\TextInput::make('external_transaction_id')
                    ->maxLength(100)
                    ->default(null),
                Forms\Components\TextInput::make('external_reference')
                    ->maxLength(100)
                    ->default(null),
                Forms\Components\Textarea::make('api_response_data')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('momo_name')
                    ->maxLength(100)
                    ->default(null),
                Forms\Components\TextInput::make('momo_number')
                    ->maxLength(20)
                    ->default(null),
                Forms\Components\TextInput::make('payment_method')
                    ->maxLength(50)
                    ->default(null),
                Forms\Components\TextInput::make('tracking_key')
                    ->maxLength(20)
                    ->default(null),
                Forms\Components\DateTimePicker::make('status_updated_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('package_size')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('processing_fee')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('beneficiary_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('network_type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('order_reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('external_transaction_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('external_reference')
                    ->searchable(),
                Tables\Columns\TextColumn::make('momo_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('momo_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tracking_key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status_updated_at')
                    ->dateTime()
                    ->sortable(),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}


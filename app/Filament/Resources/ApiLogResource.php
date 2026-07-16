<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApiLogResource\Pages;
use App\Models\ApiLog;
use App\Models\Order;
use App\Models\AgentOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ApiLogResource extends Resource
{
    protected static ?string $model = ApiLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'API Transactions';

    protected static ?string $slug = 'api-transactions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->label('Order')
                    ->options(fn () => Order::pluck('id', 'id'))
                    ->searchable(),
                Forms\Components\Select::make('agent_order_id')
                    ->label('Agent Order')
                    ->options(fn () => AgentOrder::pluck('id', 'id'))
                    ->searchable(),
                Forms\Components\TextInput::make('network_type')
                    ->maxLength(50),
                Forms\Components\TextInput::make('api_endpoint')
                    ->maxLength(500),
                Forms\Components\Textarea::make('request_data'),
                Forms\Components\Textarea::make('response_data'),
                Forms\Components\TextInput::make('response_code')
                    ->numeric(),
                Forms\Components\Toggle::make('success'),
                Forms\Components\Textarea::make('error_message'),
                Forms\Components\TextInput::make('execution_time_ms')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date/Time')
                    ->dateTime('M d, Y g:i A')
                    ->sortable()
                    ->size('sm'),
                Tables\Columns\TextColumn::make('network_type')
                    ->label('Network')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('api_endpoint')
                    ->label('Endpoint')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\IconColumn::make('success')
                    ->label('OK')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('response_code')
                    ->label('Code')
                    ->sortable()
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('execution_time_ms')
                    ->label('Time')
                    ->formatStateUsing(fn ($state) => $state ? "{$state}ms" : '-')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(20)
                    ->toggleable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('agent_order_id')
                    ->label('Agent Order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('network_type')
                    ->label('Network')
                    ->multiple()
                    ->options(fn () => ApiLog::distinct()->orderBy('network_type')->pluck('network_type', 'network_type')),
                Tables\Filters\TernaryFilter::make('success')
                    ->label('Success'),
                Tables\Filters\Filter::make('created_at')
                    ->label('Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date))),
                Tables\Filters\SelectFilter::make('response_code')
                    ->label('Response Code')
                    ->options([
                        '200' => '200',
                        '201' => '201',
                        '400' => '400',
                        '401' => '401',
                        '403' => '403',
                        '404' => '404',
                        '500' => '500',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiLogs::route('/'),
            'view' => Pages\ViewApiLog::route('/{record}'),
        ];
    }
}


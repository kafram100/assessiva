<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderStatusHistoryResource\Pages;
use App\Models\OrderStatusHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderStatusHistoryResource extends Resource
{
    protected static ?string $model = OrderStatusHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Status Tracking';

    protected static ?string $slug = 'status-tracking';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('order_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('old_status')
                    ->maxLength(50),
                Forms\Components\TextInput::make('new_status')
                    ->required()
                    ->maxLength(50),
                Forms\Components\Textarea::make('change_reason')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('changed_by')
                    ->maxLength(50)
                    ->default('system'),
                Forms\Components\Textarea::make('external_data')
                    ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('order_id')
                    ->label('Order #')
                    ->sortable()
                    ->searchable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('old_status')
                    ->label('From')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('new_status')
                    ->label('To')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed', 'delivered' => 'success',
                        'failed', 'cancelled' => 'danger',
                        'pending' => 'warning',
                        'processing' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('change_reason')
                    ->label('Reason')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('changed_by')
                    ->label('Changed By')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->label('Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date))),
                Tables\Filters\SelectFilter::make('new_status')
                    ->label('Status')
                    ->multiple()
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'delivered' => 'Delivered',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
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
            'index' => Pages\ListOrderStatusHistories::route('/'),
            'view' => Pages\ViewOrderStatusHistory::route('/{record}'),
        ];
    }
}


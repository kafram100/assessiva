<?php

namespace App\Filament\Widgets;

use App\Models\AgentOrder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentOrders extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AgentOrder::query()
                    ->with('agent')
                    ->latest()
                    ->limit(20)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tracking_key')
                    ->label('Tracking')
                    ->searchable(),

                Tables\Columns\TextColumn::make('agent.username')
                    ->label('Agent')
                    ->searchable(),

                Tables\Columns\TextColumn::make('network_type')
                    ->label('Network'),

                Tables\Columns\TextColumn::make('package_size')
                    ->label('Package'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('GHS'),

                Tables\Columns\TextColumn::make('beneficiary_number')
                    ->label('Phone'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed', 'delivered' => 'success',
                        'pending' => 'warning',
                        'verified' => 'info',
                        'processing' => 'gray',
                        'failed', 'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->poll('30s');
    }
}

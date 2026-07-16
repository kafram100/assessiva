<?php

namespace App\Filament\Widgets;

use App\Models\AgentOrder;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class AgentList extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('role', 'agent')
                    ->withCount(['agentOrders', 'SubAgents'])
                    ->withSum('agentOrders', 'amount')
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Balance')
                    ->money('GHS')
                    ->sortable(),

                Tables\Columns\TextColumn::make('agent_orders_count')
                    ->label('Orders')
                    ->counts('agentOrders')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sub_agents_count')
                    ->label('Sub Agents')
                    ->counts('SubAgents')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->date()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

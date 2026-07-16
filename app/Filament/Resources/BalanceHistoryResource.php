<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BalanceHistoryResource\Pages;
use App\Filament\Widgets\BalanceHistoryStats;
use App\Models\BalanceHistory;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BalanceHistoryResource extends Resource
{
    protected static ?string $model = BalanceHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Balance History';

    protected static ?string $slug = 'balance-history';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('agent_id')
                    ->label('User')
                    ->required()
                    ->options(fn () => User::whereIn('role', ['agent', 'dealer'])->pluck('username', 'id'))
                    ->searchable(),
                Forms\Components\TextInput::make('change_amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('balance_after')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('reason')
                    ->required()
                    ->maxLength(30),
                Forms\Components\TextInput::make('beneficiary_number')
                    ->maxLength(15),
                Forms\Components\TextInput::make('description')
                    ->maxLength(255),
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
                Tables\Columns\TextColumn::make('user.username')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('user.role')
                    ->label('Role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'agent' => 'success',
                        'dealer' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('balance_before')
                    ->label('Balance Before')
                    ->getStateUsing(fn (BalanceHistory $record): float => (float) ($record->balance_after - $record->change_amount))
                    ->money('GHS')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('change_amount')
                    ->label('Amount Changed')
                    ->money('GHS')
                    ->color(fn (float $state): string => $state >= 0 ? 'success' : 'danger')
                    ->sortable()
                    ->alignEnd()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('GHS')),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Balance After')
                    ->money('GHS')
                    ->sortable()
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('reason')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin_credit' => 'info',
                        'admin_debit' => 'danger',
                        'order_purchase' => 'danger',
                        'topup' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),
                Tables\Columns\TextColumn::make('beneficiary_number')
                    ->label('Beneficiary')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('description')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('agent_id')
                    ->label('User')
                    ->multiple()
                    ->searchable()
                    ->attribute('agent_id')
                    ->options(fn () => User::whereIn('role', ['agent', 'dealer'])->pluck('username', 'id')),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['from'] && !$data['until']) return null;
                        $from = $data['from'] ?: '...';
                        $until = $data['until'] ?: '...';
                        return "Date: {$from} – {$until}";
                    }),
                Tables\Filters\SelectFilter::make('reason')
                    ->label('Type')
                    ->multiple()
                    ->options([
                        'admin_credit' => 'Admin Credit',
                        'admin_debit' => 'Admin Debit',
                        'order_purchase' => 'Order Purchase',
                        'topup' => 'Top-up',
                    ]),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getWidgets(): array
    {
        return [
            BalanceHistoryStats::class,
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBalanceHistories::route('/'),
            'create' => Pages\CreateBalanceHistory::route('/create'),
            'edit' => Pages\EditBalanceHistory::route('/{record}/edit'),
        ];
    }
}


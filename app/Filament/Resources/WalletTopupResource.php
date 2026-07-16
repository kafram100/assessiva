<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletTopupResource\Pages;
use App\Filament\Widgets\WalletTopupStats;
use App\Models\User;
use App\Models\WalletTopup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletTopupResource extends Resource
{
    protected static ?string $model = WalletTopup::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference')
                    ->required()
                    ->maxLength(120),
                Forms\Components\Select::make('account_id')
                    ->label('User')
                    ->required()
                    ->options(fn () => User::whereIn('role', ['agent', 'dealer'])->pluck('username', 'id'))
                    ->searchable(),
                Forms\Components\TextInput::make('account_type')
                    ->required()
                    ->maxLength(32)
                    ->default('agent'),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(191)
                    ->default(null),
                Forms\Components\TextInput::make('base_amount')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('processing_fee')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('total_amount')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('provider')
                    ->maxLength(50)
                    ->default(null),
                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ])
                    ->default('pending'),
                Forms\Components\DateTimePicker::make('credited_at'),
                Forms\Components\DateTimePicker::make('paid_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->copyable()
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
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('GHS')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('GHS')),
                Tables\Columns\TextColumn::make('base_amount')
                    ->label('Base')
                    ->money('GHS')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('processing_fee')
                    ->label('Fee')
                    ->money('GHS')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'info',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'admin' => 'Admin Credit',
                        default => ucfirst($state) ?: 'Top-up',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('credited_at')
                    ->label('Credited')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user')
                    ->label('User')
                    ->multiple()
                    ->searchable()
                    ->attribute('account_id')
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
                Tables\Filters\SelectFilter::make('provider')
                    ->label('Source')
                    ->options([
                        'admin' => 'Admin Credit',
                    ]),
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

    public static function getWidgets(): array
    {
        return [
            WalletTopupStats::class,
        ];
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
            'index' => Pages\ListWalletTopups::route('/'),
            'create' => Pages\CreateWalletTopup::route('/create'),
            'edit' => Pages\EditWalletTopup::route('/{record}/edit'),
        ];
    }
}


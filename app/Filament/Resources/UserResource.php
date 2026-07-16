<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $modelLabel = 'Dealer/Agent';

    protected static ?string $pluralModelLabel = 'Dealers/Agents';

    protected static ?string $slug = 'dealers-agents';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('role', ['agent', 'dealer']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('username')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(100)
                    ->default(null),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->minLength(6)
                    ->maxLength(12),
                Forms\Components\Hidden::make('role')
                    ->default('agent'),
                Forms\Components\Hidden::make('balance')
                    ->default(0.00),
                Forms\Components\Hidden::make('status')
                    ->default('active'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('balance')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->colors([
                        'success' => 'agent',
                        'warning' => 'dealer',
                    ])
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('toggleRole')
                    ->label(fn ($record) => $record->role === 'agent' ? 'Upgrade to Dealer' : 'Downgrade to Agent')
                    ->icon(fn ($record) => $record->role === 'agent' ? 'heroicon-o-arrow-up-circle' : 'heroicon-o-arrow-down-circle')
                    ->color(fn ($record) => $record->role === 'agent' ? 'success' : 'warning')
                    ->action(fn ($record) => $record->update(['role' => $record->role === 'agent' ? 'dealer' : 'agent']))
                    ->requiresConfirmation(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}


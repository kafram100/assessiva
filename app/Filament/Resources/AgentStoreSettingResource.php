<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentStoreSettingResource\Pages;
use App\Models\AgentStoreSetting;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AgentStoreSettingResource extends Resource
{
    protected static ?string $model = AgentStoreSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Agent Stores';

    protected static ?string $slug = 'agent-stores';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('agent_id')
                    ->label('Agent')
                    ->required()
                    ->options(fn () => User::whereIn('role', ['agent', 'dealer'])->pluck('username', 'id'))
                    ->searchable(),
                Forms\Components\TextInput::make('store_name')
                    ->maxLength(100),
                Forms\Components\TextInput::make('store_slug')
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('store_description'),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
                Forms\Components\TextInput::make('theme_color')
                    ->maxLength(7)
                    ->default('#3B82F6')
                    ->placeholder('#3B82F6'),
                Forms\Components\TextInput::make('logo_url')
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_phone')
                    ->tel()
                    ->maxLength(20),
                Forms\Components\TextInput::make('contact_whatsapp')
                    ->maxLength(20),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('agent.username')
                    ->label('Agent')
                    ->searchable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('store_name')
                    ->label('Store Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('store_slug')
                    ->label('Slug')
                    ->searchable()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('contact_phone')
                    ->label('Phone'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->date('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('agent_id')
                    ->label('Agent')
                    ->multiple()
                    ->searchable()
                    ->attribute('agent_id')
                    ->options(fn () => User::whereIn('role', ['agent', 'dealer'])->pluck('username', 'id')),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgentStoreSettings::route('/'),
            'create' => Pages\CreateAgentStoreSetting::route('/create'),
            'edit' => Pages\EditAgentStoreSetting::route('/{record}/edit'),
        ];
    }
}


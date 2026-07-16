<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Support Tickets';

    protected static ?string $slug = 'support-tickets';

    protected static ?string $navigationGroup = 'Management';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->disabled(),
                    ]),
                Forms\Components\Section::make('Conversation')
                    ->schema([
                        Forms\Components\ViewField::make('messages')
                            ->label('')
                            ->view('filament.support-messages'),
                        Forms\Components\Textarea::make('admin_reply')
                            ->label('Your Reply')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Type your reply and save...'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'open' => 'Open',
                                'replied' => 'Replied',
                                'closed' => 'Closed',
                            ])
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y g:i A')
                    ->sortable()
                    ->size('sm'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('messages_count')
                    ->label('Messages')
                    ->counts('messages')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'replied' => 'success',
                        'closed' => 'gray',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Activity')
                    ->dateTime('M d, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'replied' => 'Replied',
                        'closed' => 'Closed',
                    ]),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date))),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('View & Reply'),
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
            'index' => Pages\ListSupportTickets::route('/'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }
}

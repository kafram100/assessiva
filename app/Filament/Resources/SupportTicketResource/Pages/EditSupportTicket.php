<?php

namespace App\Filament\Resources\SupportTicketResource\Pages;

use App\Filament\Resources\SupportTicketResource;
use App\Models\SupportMessage;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSupportTicket extends EditRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['admin_reply'])) {
            SupportMessage::create([
                'support_ticket_id' => $this->record->id,
                'sender_type' => 'admin',
                'message' => $data['admin_reply'],
            ]);

            if ($data['status'] === 'open') {
                $data['status'] = 'replied';
            }
        }

        unset($data['admin_reply']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

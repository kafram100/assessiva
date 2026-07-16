<?php

namespace App\Filament\Resources\AgentStoreSettingResource\Pages;

use App\Filament\Resources\AgentStoreSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgentStoreSetting extends EditRecord
{
    protected static string $resource = AgentStoreSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

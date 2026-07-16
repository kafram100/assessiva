<?php

namespace App\Filament\Resources\AgentPackageResource\Pages;

use App\Filament\Resources\AgentPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAgentPackage extends EditRecord
{
    protected static string $resource = AgentPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

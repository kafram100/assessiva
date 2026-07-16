<?php

namespace App\Filament\Resources\ApiConfigResource\Pages;

use App\Filament\Resources\ApiConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApiConfig extends EditRecord
{
    protected static string $resource = ApiConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

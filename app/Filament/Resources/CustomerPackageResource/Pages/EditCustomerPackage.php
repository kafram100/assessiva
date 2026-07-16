<?php

namespace App\Filament\Resources\CustomerPackageResource\Pages;

use App\Filament\Resources\CustomerPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerPackage extends EditRecord
{
    protected static string $resource = CustomerPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

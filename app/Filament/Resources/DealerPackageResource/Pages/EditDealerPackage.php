<?php

namespace App\Filament\Resources\DealerPackageResource\Pages;

use App\Filament\Resources\DealerPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDealerPackage extends EditRecord
{
    protected static string $resource = DealerPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

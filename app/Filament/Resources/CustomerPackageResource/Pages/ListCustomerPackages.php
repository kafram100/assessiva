<?php

namespace App\Filament\Resources\CustomerPackageResource\Pages;

use App\Filament\Resources\CustomerPackageResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerPackages extends ListRecords
{
    protected static string $resource = CustomerPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

<?php

namespace App\Filament\Resources\CustomerPackageResource\Pages;

use App\Filament\Resources\CustomerPackageResource;
use App\Models\CustomerPackage;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerPackage extends CreateRecord
{
    protected static string $resource = CustomerPackageResource::class;

    protected function getRedirectUrl(): string
    {
        return CustomerPackageResource::getUrl('index');
    }

    protected function handleRecordCreation(array $data): CustomerPackage
    {
        if (($data['network_type'] ?? '') === 'ALL') {
            $networks = ['MTN', 'VODAFONE', 'AIRTELTIGO'];
            $record = null;
            foreach ($networks as $network) {
                $data['network_type'] = $network;
                $record = CustomerPackage::create($data);
            }
            return $record;
        }
        return CustomerPackage::create($data);
    }
}

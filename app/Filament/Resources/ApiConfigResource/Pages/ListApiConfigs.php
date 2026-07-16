<?php

namespace App\Filament\Resources\ApiConfigResource\Pages;

use App\Filament\Resources\ApiConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApiConfigs extends ListRecords
{
    protected static string $resource = ApiConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

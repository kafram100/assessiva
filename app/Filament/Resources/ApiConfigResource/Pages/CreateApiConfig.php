<?php

namespace App\Filament\Resources\ApiConfigResource\Pages;

use App\Filament\Resources\ApiConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateApiConfig extends CreateRecord
{
    protected static string $resource = ApiConfigResource::class;
}

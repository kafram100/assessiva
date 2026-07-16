<?php

namespace App\Filament\Resources\OrderStatusHistoryResource\Pages;

use App\Filament\Resources\OrderStatusHistoryResource;
use Filament\Resources\Pages\ViewRecord;

class ViewOrderStatusHistory extends ViewRecord
{
    protected static string $resource = OrderStatusHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

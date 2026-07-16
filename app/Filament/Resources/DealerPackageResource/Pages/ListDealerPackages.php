<?php

namespace App\Filament\Resources\DealerPackageResource\Pages;

use App\Filament\Resources\DealerPackageResource;
use Filament\Actions\CreateAction as GlobalCreateAction;
use Filament\Forms\Form;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\EditAction;
use Illuminate\Database\Eloquent\Model;

class ListDealerPackages extends ListRecords
{
    protected static string $resource = DealerPackageResource::class;

    protected function configureCreateAction(GlobalCreateAction|\Filament\Tables\Actions\CreateAction $action): void
    {
        $action
            ->authorize(static::getResource()::canCreate())
            ->model($this->getModel())
            ->modelLabel($this->getModelLabel() ?? static::getResource()::getModelLabel())
            ->form(fn (Form $form): Form => $this->form($form->columns(2)));
    }

    protected function configureEditAction(EditAction $action): void
    {
        $action
            ->authorize(fn (Model $record): bool => static::getResource()::canEdit($record))
            ->form(fn (Form $form): Form => $this->form($form->columns(2)));
    }
}

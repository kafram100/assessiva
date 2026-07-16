<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use App\Services\DataBundle\DataBundleRouter;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class DataProviderSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string $view = 'filament.pages.data-provider-settings';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $title = 'Data Provider Settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'data_provider' => AppSetting::getValue('data_provider', DataBundleRouter::PROVIDER_LEGACY),
            'api_status' => AppSetting::getValue('api_status', '1') === '1',
            'mtn_status' => AppSetting::getValue('mtn_status', '1') === '1',
            'vodafone_status' => AppSetting::getValue('vodafone_status', '1') === '1',
            'airteltigo_status' => AppSetting::getValue('airteltigo_status', '1') === '1',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('data_provider')
                    ->label('Active Data Bundle Provider')
                    ->options(DataBundleRouter::getAvailableProviders())
                    ->helperText('Select which API provider to use when fulfilling data bundle orders after payment.'),
                    
                Toggle::make('api_status')
                    ->label('Global API Status (On/Off)')
                    ->helperText('Turn this off to put the entire data bundle purchasing system into maintenance mode. All new orders will be blocked.')
                    ->default(true),

                Toggle::make('mtn_status')
                    ->label('MTN Network Status')
                    ->helperText('Turn off to block all new MTN data bundle orders.')
                    ->default(true),

                Toggle::make('vodafone_status')
                    ->label('Vodafone/Telecel Network Status')
                    ->helperText('Turn off to block all new Vodafone data bundle orders.')
                    ->default(true),

                Toggle::make('airteltigo_status')
                    ->label('AirtelTigo/AT Network Status')
                    ->helperText('Turn off to block all new AirtelTigo data bundle orders.')
                    ->default(true),

                Actions::make([
                    Action::make('save')
                        ->label('Save Settings')
                        ->submit('save'),
                ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        AppSetting::setValue('data_provider', $data['data_provider']);
        AppSetting::setValue('api_status', $data['api_status'] ? '1' : '0');
        AppSetting::setValue('mtn_status', $data['mtn_status'] ? '1' : '0');
        AppSetting::setValue('vodafone_status', $data['vodafone_status'] ? '1' : '0');
        AppSetting::setValue('airteltigo_status', $data['airteltigo_status'] ? '1' : '0');

        app(DataBundleRouter::class)->refreshProvider();

        Notification::make()
            ->title('Data provider updated successfully')
            ->success()
            ->send();
    }
}

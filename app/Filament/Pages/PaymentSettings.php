<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class PaymentSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static string $view = 'filament.pages.payment-settings';

    protected static ?string $title = 'Payment Settings';

    protected static ?string $navigationGroup = 'Configuration';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'default_wallet_gateway' => AppSetting::getValue('default_wallet_gateway', 'moolre'),
            'default_public_gateway' => AppSetting::getValue('default_public_gateway', 'paystack'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('default_wallet_gateway')
                    ->label('Agent & Dealer Wallet Top-ups')
                    ->helperText('When agents and dealers top up their wallet, they will use this gateway.')
                    ->options([
                        'moolre' => 'Moolre (Mobile Money)',
                        'paystack' => 'Paystack (Card & Mobile Money)',
                    ])
                    ->default('moolre')
                    ->required(),
                Select::make('default_public_gateway')
                    ->label('Public Storefront Orders')
                    ->helperText('When public customers buy data bundles on the storefront, they will pay through this gateway.')
                    ->options([
                        'paystack' => 'Paystack (Card & Mobile Money)',
                        'moolre' => 'Moolre (Mobile Money)',
                    ])
                    ->default('paystack')
                    ->required(),
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

        AppSetting::setValue('default_wallet_gateway', $data['default_wallet_gateway']);
        AppSetting::setValue('default_public_gateway', $data['default_public_gateway']);

        Notification::make()
            ->title('Payment gateway settings saved')
            ->success()
            ->send();
    }
}

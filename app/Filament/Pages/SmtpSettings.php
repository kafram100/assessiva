<?php

namespace App\Filament\Pages;

use App\Models\AppSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SmtpSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static string $view = 'filament.pages.smtp-settings';

    protected static ?string $title = 'SMTP Settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'mail_mailer' => AppSetting::getValue('mail_mailer', 'smtp'),
            'mail_host' => AppSetting::getValue('mail_host', 'smtp.gmail.com'),
            'mail_port' => AppSetting::getValue('mail_port', '465'),
            'mail_encryption' => AppSetting::getValue('mail_encryption', 'ssl'),
            'mail_username' => AppSetting::getValue('mail_username', 'airlink@gmail.com'),
            'mail_password' => AppSetting::getValue('mail_password', ''),
            'mail_from_address' => AppSetting::getValue('mail_from_address', 'airlink@gmail.com'),
            'mail_from_name' => AppSetting::getValue('mail_from_name', 'Affordable Bundle'),
            'mail_reply_to' => AppSetting::getValue('mail_reply_to', 'airlink@gmail.com'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('mail_mailer')
                    ->label('Mail Driver')
                    ->options(['smtp' => 'SMTP', 'sendmail' => 'Sendmail', 'log' => 'Log (File)'])
                    ->default('smtp')
                    ->required(),
                TextInput::make('mail_host')
                    ->label('SMTP Host')
                    ->default('smtp.gmail.com')
                    ->required(),
                TextInput::make('mail_port')
                    ->label('SMTP Port')
                    ->numeric()
                    ->default(465)
                    ->required(),
                Select::make('mail_encryption')
                    ->label('Encryption')
                    ->options(['ssl' => 'SSL', 'tls' => 'TLS', '' => 'None'])
                    ->default('ssl'),
                TextInput::make('mail_username')
                    ->label('SMTP Username')
                    ->email()
                    ->default('airlink@gmail.com')
                    ->required(),
                TextInput::make('mail_password')
                    ->label('SMTP Password')
                    ->password()
                    ->default(''),
                TextInput::make('mail_from_address')
                    ->label('From Address')
                    ->email()
                    ->default('airlink@gmail.com')
                    ->required(),
                TextInput::make('mail_from_name')
                    ->label('From Name')
                    ->default('Affordable Bundle')
                    ->required(),
                TextInput::make('mail_reply_to')
                    ->label('Reply-To')
                    ->email()
                    ->default('airlink@gmail.com'),
                Actions::make([
                    FormAction::make('save')
                        ->label('Save Settings')
                        ->submit('save'),
                ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            AppSetting::setValue($key, $value === '' ? null : (string) $value);
        }

        $this->applyConfig();

        Notification::make()
            ->title('SMTP settings saved successfully')
            ->success()
            ->send();
    }

    public function sendTestEmail(): void
    {
        $data = $this->form->getState();
        foreach ($data as $key => $value) {
            AppSetting::setValue($key, $value === '' ? null : (string) $value);
        }

        $this->applyConfig();

        try {
            \Illuminate\Support\Facades\Mail::raw(
                'This is a test email from Affordable Bundle. Your SMTP settings are working correctly.',
                fn ($msg) => $msg->to($data['mail_from_address'])
                    ->subject('Test Email - SMTP Settings')
            );

            Notification::make()
                ->title('Test email sent to ' . $data['mail_from_address'])
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Failed to send test email: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function applyConfig(): void
    {
        config([
            'mail.default' => AppSetting::getValue('mail_mailer', 'smtp'),
            'mail.mailers.smtp.host' => AppSetting::getValue('mail_host', 'smtp.gmail.com'),
            'mail.mailers.smtp.port' => (int) AppSetting::getValue('mail_port', '465'),
            'mail.mailers.smtp.encryption' => AppSetting::getValue('mail_encryption', 'ssl') ?: null,
            'mail.mailers.smtp.username' => AppSetting::getValue('mail_username', 'airlink@gmail.com'),
            'mail.mailers.smtp.password' => AppSetting::getValue('mail_password', ''),
            'mail.from.address' => AppSetting::getValue('mail_from_address', 'airlink@gmail.com'),
            'mail.from.name' => AppSetting::getValue('mail_from_name', 'Affordable Bundle'),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendTestEmail')
                ->label('Send Test Email')
                ->icon('heroicon-o-paper-airplane')
                ->color('gray')
                ->action('sendTestEmail'),
        ];
    }
}

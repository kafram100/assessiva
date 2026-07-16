<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\PasswordReset\RequestPasswordReset;
use Illuminate\Support\Facades\Password;

class ForgotPassword extends RequestPasswordReset
{
    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('username')
            ->label('Username')
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $user = User::where('username', $data['username'])->first();

        return [
            'email' => $user?->email,
        ];
    }

    protected function getRequestFormAction(): \Filament\Actions\Action
    {
        return parent::getRequestFormAction()
            ->label('Send Reset Link');
    }
}

<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Inertia\Inertia;
use Inertia\Response;

class EmailSettingsController extends Controller
{
    public function email(): Response
    {
        $settings = [
            'mail_mailer' => AppSetting::getValue('mail_mailer', config('mail.default')),
            'mail_host' => AppSetting::getValue('mail_host', config('mail.mailers.smtp.host')),
            'mail_port' => AppSetting::getValue('mail_port', (string) config('mail.mailers.smtp.port')),
            'mail_encryption' => AppSetting::getValue('mail_encryption', config('mail.mailers.smtp.encryption')),
            'mail_username' => AppSetting::getValue('mail_username', config('mail.mailers.smtp.username')),
            'mail_from_address' => AppSetting::getValue('mail_from_address', config('mail.from.address')),
            'mail_from_name' => AppSetting::getValue('mail_from_name', config('mail.from.name')),
            'mail_reply_to' => AppSetting::getValue('mail_reply_to', ''),
        ];

        return Inertia::render('Lecturer/Settings/Email', [
            'settings' => $settings,
        ]);
    }
}

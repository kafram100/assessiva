<?php

namespace App\Providers;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Auto-clear caches to ensure new routes take effect
        $cachePath = base_path('bootstrap/cache');
        if (\Illuminate\Support\Facades\File::isDirectory($cachePath)) {
            $files = \Illuminate\Support\Facades\File::glob($cachePath . '/*.php');
            foreach ($files as $file) {
                if (basename($file) !== '.gitignore') {
                    \Illuminate\Support\Facades\File::delete($file);
                }
            }
        }

        \Livewire\Livewire::setScriptRoute(function ($handle) {
            return \Illuminate\Support\Facades\Route::get('/lw-assets/livewire-script', $handle)
                ->middleware('web');
        });

        \Livewire\Livewire::setUpdateRoute(function ($handle) {
            return \Illuminate\Support\Facades\Route::post('/lw-assets/livewire-update', $handle)
                ->middleware('web');
        });

        \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
        Vite::prefetch(concurrency: 3);

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('app_settings')) {
                config([
                    'mail.default' => AppSetting::getValue('mail_mailer', 'log'),
                    'mail.mailers.smtp.host' => AppSetting::getValue('mail_host', 'smtp.gmail.com'),
                    'mail.mailers.smtp.port' => (int) AppSetting::getValue('mail_port', '465'),
                    'mail.mailers.smtp.encryption' => AppSetting::getValue('mail_encryption', 'ssl') ?: null,
                    'mail.mailers.smtp.username' => AppSetting::getValue('mail_username', ''),
                    'mail.mailers.smtp.password' => AppSetting::getValue('mail_password', ''),
                    'mail.from.address' => AppSetting::getValue('mail_from_address', 'airlink@gmail.com'),
                    'mail.from.name' => AppSetting::getValue('mail_from_name', 'AirLinks'),
                ]);
            }
        } catch (\Throwable $e) {
            // table might not exist yet during migration
        }
    }
}

<?php

namespace App\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class WindowsFilesystemServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->extend('files', function (Filesystem $files, $app) {
            return new WindowsSafeFilesystem;
        });
    }
}

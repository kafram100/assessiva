<?php
// storage_link.php
// This script runs the 'storage:link' artisan command via the browser or cPanel Cron Job
// Useful for shared hosting without terminal access.

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Call the storage:link command
$status = $kernel->call('storage:link');

echo "<h1>Storage Link Created!</h1>";
echo "<pre>";
echo Illuminate\Support\Facades\Artisan::output();
echo "</pre>";
echo "<p>You can now delete this file (storage_link.php) for security.</p>";

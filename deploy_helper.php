<?php
// deploy_helper.php
// This script runs essential Laravel deployment commands directly via the browser.
// Designed for shared hosting environments like cPanel where Terminal access is disabled.

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "<h1>AirLinks Deployment Helper</h1>";
echo "<pre style='background:#1e1e1e; color:#0f0; padding:20px; border-radius:10px;'>";

// 1. Create storage link
echo "Running storage:link...\n";
$kernel->call('storage:link');
echo Illuminate\Support\Facades\Artisan::output() . "\n";

// 2. Clear all caches
echo "Clearing application cache...\n";
$kernel->call('cache:clear');
echo Illuminate\Support\Facades\Artisan::output() . "\n";

echo "Clearing config cache...\n";
$kernel->call('config:clear');
echo Illuminate\Support\Facades\Artisan::output() . "\n";

echo "Clearing route cache...\n";
$kernel->call('route:clear');
echo Illuminate\Support\Facades\Artisan::output() . "\n";

echo "Clearing view cache...\n";
$kernel->call('view:clear');
echo Illuminate\Support\Facades\Artisan::output() . "\n";

// 3. Optimize configuration
echo "Optimizing configuration for production...\n";
$kernel->call('optimize');
echo Illuminate\Support\Facades\Artisan::output() . "\n";

echo "</pre>";
echo "<h2 style='color:green;'>Deployment preparation complete! 🚀</h2>";
echo "<p style='color:red; font-weight:bold;'>URGENT: Please DELETE this file (deploy_helper.php) and create_full_zip.php immediately from your server for security.</p>";

<?php
// Upload this file to your public_html folder on cPanel
// Then visit https://assessiva.com/debug_check.php
// DELETE THIS FILE after you're done debugging!

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Assessiva Server Diagnostic</h2>";

// 1. PHP Version
echo "<p><b>PHP Version:</b> " . phpversion() . "</p>";

// 2. Check if key files exist
$base = dirname(__DIR__) . '/assessiva';
echo "<p><b>Looking for app in:</b> $base</p>";

$checks = [
    'vendor/autoload.php',
    'bootstrap/app.php',
    'bootstrap/cache',
    '.env',
    'storage/logs',
    'public/build/manifest.json',
];

echo "<table border='1' cellpadding='5'><tr><th>File</th><th>Exists?</th><th>Writable?</th></tr>";
foreach ($checks as $file) {
    $path = $base . '/' . $file;
    $exists = file_exists($path) ? '✅ YES' : '❌ NO';
    $writable = (file_exists($path) && is_writable($path)) ? '✅' : '❌';
    echo "<tr><td>$file</td><td>$exists</td><td>$writable</td></tr>";
}
echo "</table>";

// 3. Try to boot Laravel and catch the actual error
echo "<hr><h3>Attempting to boot Laravel...</h3>";
try {
    require $base . '/vendor/autoload.php';
    $app = require_once $base . '/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    echo "<p style='color:green'><b>✅ Laravel booted successfully!</b></p>";
    
    // Check .env values
    echo "<p><b>APP_DEBUG:</b> " . env('APP_DEBUG') . "</p>";
    echo "<p><b>APP_ENV:</b> " . env('APP_ENV') . "</p>";
    echo "<p><b>DB_DATABASE:</b> " . env('DB_DATABASE') . "</p>";
    
} catch (\Throwable $e) {
    echo "<p style='color:red'><b>❌ LARAVEL CRASHED:</b></p>";
    echo "<pre style='background:#fee;padding:10px;border:1px solid red'>";
    echo get_class($e) . ": " . $e->getMessage() . "\n\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}

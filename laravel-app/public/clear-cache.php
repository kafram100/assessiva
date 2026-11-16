<?php
/**
 * Assessiva Production Cache Cleaner
 * Visit: https://assessiva.com/clear-cache.php?key=assessiva
 */

$secret = 'assessiva';

if (($_GET['key'] ?? '') !== $secret) {
    http_response_code(403);
    die('<h3 style="font-family:sans-serif;color:#e11d48;text-align:center;margin-top:50px;">403 Forbidden: Invalid or missing ?key= parameter</h3>');
}

// Check for self-delete request
if (isset($_GET['delete']) && $_GET['delete'] === 'yes') {
    @unlink(__FILE__);
    die('<h3 style="font-family:sans-serif;color:#16a34a;text-align:center;margin-top:50px;">clear-cache.php has been deleted from your server.</h3>');
}

header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><title>Assessiva Cache Cleaner</title><style>
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #f8fafc; padding: 40px; }
.card { max-width: 600px; margin: 0 auto; background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); }
h2 { margin-top: 0; color: #6366f1; }
pre { background: #0f172a; padding: 12px; border-radius: 8px; border: 1px solid #334155; color: #10b981; font-size: 13px; overflow-x: auto; }
.btn { display: inline-block; background: #e11d48; color: #fff; text-decoration: none; padding: 10px 16px; border-radius: 8px; font-weight: 600; margin-top: 15px; }
.btn-home { background: #4f46e5; margin-right: 10px; }
</style></head><body><div class="card">';
echo '<h2>Assessiva Cache Cleaner</h2>';

// Find Laravel base directory
$candidates = [
    __DIR__ . '/..',
    __DIR__,
    dirname(__DIR__),
];

$baseDir = null;
foreach ($candidates as $dir) {
    if (file_exists($dir . '/bootstrap/app.php')) {
        $baseDir = realpath($dir);
        break;
    }
}

$output = '';

if ($baseDir && file_exists($baseDir . '/vendor/autoload.php')) {
    try {
        require_once $baseDir . '/vendor/autoload.php';
        $app = require_once $baseDir . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();

        Illuminate\Support\Facades\Artisan::call('optimize:clear');
        $output .= Illuminate\Support\Facades\Artisan::output();
    } catch (\Throwable $e) {
        $output .= "Artisan call exception: " . $e->getMessage() . "\nFalling back to manual file purge...\n";
    }
}

// Direct file removal fallback / guarantee
if ($baseDir) {
    $cacheFiles = [
        $baseDir . '/bootstrap/cache/config.php',
        $baseDir . '/bootstrap/cache/routes-v7.php',
        $baseDir . '/bootstrap/cache/events.php',
        $baseDir . '/bootstrap/cache/packages.php',
        $baseDir . '/bootstrap/cache/services.php',
    ];

    foreach ($cacheFiles as $file) {
        if (file_exists($file)) {
            if (@unlink($file)) {
                $output .= "Removed: " . basename($file) . "\n";
            }
        }
    }

    $viewsDir = $baseDir . '/storage/framework/views';
    if (is_dir($viewsDir)) {
        $count = 0;
        foreach (glob($viewsDir . '/*.php') as $vFile) {
            if (@unlink($vFile)) $count++;
        }
        $output .= "Purged {$count} compiled blade/view cache files.\n";
    }
}

echo '<pre>' . htmlspecialchars($output ?: 'Cache files cleared.') . '</pre>';
echo '<p style="color:#94a3b8;font-size:14px;">Your Laravel configuration, routes, and compiled views are completely refreshed.</p>';
echo '<a href="/" class="btn btn-home">Go to Website</a>';
echo '<a href="?key=' . htmlspecialchars($secret) . '&delete=yes" class="btn" onclick="return confirm(\'Delete this cleaner script now?\')">Delete This Cleaner Script</a>';
echo '</div></body></html>';

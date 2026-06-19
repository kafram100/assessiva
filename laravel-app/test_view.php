<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo view('app')->render();
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . ":" . $e->getLine() . "\n";
    if ($e->getPrevious()) {
        echo "PREV: " . $e->getPrevious()->getMessage() . "\n";
        echo "PREV FILE: " . $e->getPrevious()->getFile() . ":" . $e->getPrevious()->getLine() . "\n";
    }
}

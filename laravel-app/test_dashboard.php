<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/admin/dashboard', 'GET');
// Mock the session
$request->setLaravelSession(app('session')->driver('array'));
app('auth')->guard('web')->loginUsingId(1);
$request->setUserResolver(function () { return App\Models\User::find(1); });

$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() >= 400) {
    echo "Content: " . $response->getContent() . "\n";
}

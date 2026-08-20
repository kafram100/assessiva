<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define("LARAVEL_START", microtime(true));

// Determine if the application is in maintenance mode...
// Path updated: public_html/ is one level above the app root (~/assessiva/)
if (file_exists($maintenance = __DIR__."/../assessiva/storage/framework/maintenance.php")) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__."/../assessiva/vendor/autoload.php";

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__."/../assessiva/bootstrap/app.php";

$app->handleRequest(Request::capture());

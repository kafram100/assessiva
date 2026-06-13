<?php

/*
|--------------------------------------------------------------------------
| Offline sync & mobile-friendly APIs (scaffold)
|--------------------------------------------------------------------------
|
| JSON routes mirroring the legacy SyncController and proctoring APIs
| will be registered here in a later module (session- or token-based).
|
*/

use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => ['status' => 'ok']);

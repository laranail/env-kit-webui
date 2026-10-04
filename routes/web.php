<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\EnvKit\WebUI\Http\Controllers\PanelController;

// Vendor-scoped name. The bare `env-kit.panel` still resolves through route()
// as a deprecated alias (see the service provider).
Route::get('/', [PanelController::class, 'show'])->name('laranail-env-kit-webui.panel');

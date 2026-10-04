<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\EnvKit\WebUI\Http\Controllers\EnvController;

// Vendor-scoped names. The bare `env-kit.keys.*` names they replaced still
// resolve through route() as deprecated aliases (see the service provider).
Route::get('keys', [EnvController::class, 'index'])->name('laranail-env-kit-webui.keys.index');
Route::get('keys/{key}', [EnvController::class, 'show'])->name('laranail-env-kit-webui.keys.show');
Route::post('keys', [EnvController::class, 'store'])->name('laranail-env-kit-webui.keys.store');
Route::match(['put', 'patch'], 'keys/{key}', [EnvController::class, 'update'])->name('laranail-env-kit-webui.keys.update');
Route::delete('keys/{key}', [EnvController::class, 'destroy'])->name('laranail-env-kit-webui.keys.destroy');

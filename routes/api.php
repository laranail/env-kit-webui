<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\EnvKit\WebUI\Http\Controllers\EnvController;

Route::get('keys', [EnvController::class, 'index'])->name('env-kit.keys.index');
Route::get('keys/{key}', [EnvController::class, 'show'])->name('env-kit.keys.show');

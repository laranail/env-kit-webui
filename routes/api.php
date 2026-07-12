<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\EnvKit\WebUI\Http\Controllers\EnvController;

Route::get('keys', [EnvController::class, 'index'])->name('env-kit.keys.index');
Route::get('keys/{key}', [EnvController::class, 'show'])->name('env-kit.keys.show');
Route::post('keys', [EnvController::class, 'store'])->name('env-kit.keys.store');
Route::match(['put', 'patch'], 'keys/{key}', [EnvController::class, 'update'])->name('env-kit.keys.update');
Route::delete('keys/{key}', [EnvController::class, 'destroy'])->name('env-kit.keys.destroy');

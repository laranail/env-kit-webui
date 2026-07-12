<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Simtabi\Laranail\EnvKit\WebUI\Http\Controllers\PanelController;

Route::get('/', [PanelController::class, 'show'])->name('env-kit.panel');

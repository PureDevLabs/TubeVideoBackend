<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [App\Install\Installer::class, 'index'])->name('installer');

Route::get('/check', [App\Install\Installer::class, 'check'])->name('checker');

Route::get('/migrate', [App\Install\Installer::class, 'migrate']);

Route::post('/save', [App\Install\Installer::class, 'saveConfig'])->name('saveConfig');

Route::get('/complete', [App\Install\Installer::class, 'completeSetup'])->name('completeSetup');

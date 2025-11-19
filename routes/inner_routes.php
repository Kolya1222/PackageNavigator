<?php

use roilafx\PackageNavigator\Controllers\Module;
use Illuminate\Support\Facades\Route;

Route::name('packagenavigator.')->group(function () {
    Route::get('/', [Module::class, 'index'])->name('module.index');
    Route::post('/install', [Module::class, 'install'])->name('install');
    Route::post('/remove', [Module::class, 'remove'])->name('remove');
    Route::post('/upload', [Module::class, 'uploadModule'])->name('upload');
    Route::post('/package-info', [Module::class, 'getPackageInfo'])->name('package-info');
    Route::post('/install-remote', [Module::class, 'installRemotePackage'])->name('install-remote');
});
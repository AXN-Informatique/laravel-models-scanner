<?php

use Axn\ModelsScanner\Controllers\ScanController;
use Illuminate\Support\Facades\Route;

Route::get(
    '/_models',
    ScanController::class
);

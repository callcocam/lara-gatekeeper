<?php

use Illuminate\Support\Facades\Route;
use Callcocam\LaraGatekeeper\Http\Controllers\FilePondController;

Route::get('/filepond/load', [FilePondController::class, 'load'])->name('filepond.load');

<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SuggestionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::resource('sites', SiteController::class)->except(['show']);

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/dashboard/{site}/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');
Route::post('/dashboard/{site}/suggestions', [SuggestionController::class, 'store'])->name('suggestions.store');

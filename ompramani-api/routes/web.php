<?php

use Illuminate\Support\Facades\Route;

// Redirect root URL directly to Filament Admin panel
Route::get('/', function () {
    return redirect('/admin');
});

// Gracefully handle accidental browser address bar typos like /https:/admin/login
Route::get('/https:/{any?}', function (?string $any = null) {
    return redirect($any ? "/{$any}" : '/admin');
})->where('any', '.*');

Route::get('/https/{any?}', function (?string $any = null) {
    return redirect($any ? "/{$any}" : '/admin');
})->where('any', '.*');

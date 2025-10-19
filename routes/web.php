<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route::get('/app/{any?}', function () {
//  return file_get_contents(public_path('app/index.html'));
// })->where('any', '.*');

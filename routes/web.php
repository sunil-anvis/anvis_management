<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Fallback route to serve storage files with CORS headers when physical symlink is absent or public_storage is requested
Route::any('public_storage/{path}', function ($path) {
    $filePath = storage_path("app/public/{$path}");

    if (!file_exists($filePath)) {
        $filePath = storage_path("app/public_storage/{$path}");
    }

    if (!file_exists($filePath)) {
        abort(404);
    }

    $headers = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => '*',
        'Access-Control-Allow-Headers' => '*',
    ];

    if (request()->isMethod('OPTIONS')) {
        return response('', 200, $headers);
    }

    return response()->file($filePath, $headers);
})->where('path', '.*');

Route::any('storage/{path}', function ($path) {
    $filePath = storage_path("app/public/{$path}");

    if (!file_exists($filePath)) {
        $filePath = storage_path("app/public_storage/{$path}");
    }

    if (!file_exists($filePath)) {
        abort(404);
    }

    $headers = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => '*',
        'Access-Control-Allow-Headers' => '*',
    ];

    if (request()->isMethod('OPTIONS')) {
        return response('', 200, $headers);
    }

    return response()->file($filePath, $headers);
})->where('path', '.*');


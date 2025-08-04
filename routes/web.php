<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Unauthorized'
    ], \Symfony\Component\HttpFoundation\Response::HTTP_UNAUTHORIZED);
});

Route::get('/test', [\App\Http\Controllers\TestController::class, 'index']);

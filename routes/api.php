<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WhatsappGroupController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CategoryController;

Route::prefix('v1')->group(function () {

    Route::get('status', function () {
        return response()->json(['status' => 'API V1 WP Group Manager is alive!'], 200);
    });

    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);

    Route::get('categories', [CategoryController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::apiResource('whatsapp-groups', WhatsappGroupController::class);
        Route::apiResource('contacts', ContactController::class)->only(['index', 'show']);
        Route::post('contacts/add', [WhatsappGroupController::class, 'addContact']);
        Route::get('groups/active', [WhatsappGroupController::class, 'activeGroups']);

    });

});

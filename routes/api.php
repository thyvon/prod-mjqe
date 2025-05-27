<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TelegramController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// // API routes for Supplier CRUD
// Route::get('/suppliers', [SupplierController::class, 'index']);
// Route::post('/suppliers', [SupplierController::class, 'store']);
// Route::put('/suppliers/{supplier}', [SupplierController::class, 'update']);
// Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy']);

Route::prefix('api')->group(function() {
    Route::get('/suppliers', [SupplierController::class, 'getAllSuppliers']); // Get all suppliers
    Route::post('/suppliers', [SupplierController::class, 'store']); // Create new supplier
    Route::put('/suppliers/{id}', [SupplierController::class, 'update']); // Update a supplier
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']); // Delete a supplier
});

Route::post('/telegram/send-message', [TelegramController::class, 'sendMessage']);
Route::get('/telegram/history/{chat_id}', [TelegramController::class, 'getHistory']);
Route::get('/telegram/clients', [TelegramController::class, 'getClients']);
Route::get('/telegram/unread-counts', [TelegramController::class, 'unreadCounts']);
Route::post('/telegram/mark-read', [TelegramController::class, 'markRead']);
Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);
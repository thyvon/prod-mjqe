<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\PrItemController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CashRequestController;
use App\Http\Controllers\PurchaseRequestController;

/*
|----------------------------------------------------------------------
| Web Routes
|----------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Redirect root URL based on authentication status
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard'); // Redirect to dashboard if authenticated
    }

    return redirect()->route('login'); // Redirect to login if not authenticated
});

// Dashboard route (protected with 'auth' middleware)
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Auth routes (login, logout, registration)
Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return Inertia::render('Auth/Login');
    })->name('login');

    // Include registration route if needed
    Route::get('/register', function () {
        return Inertia::render('Auth/Register');
    })->name('register');
});

// Profile routes (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // CRUD
    Route::resource('suppliers', SupplierController::class);
    Route::resource('products', ProductController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('cash-request', CashRequestController::class);
    Route::resource('purchase-requests', PurchaseRequestController::class);
    Route::resource('pr-items', PrItemController::class);



});
require __DIR__.'/auth.php';

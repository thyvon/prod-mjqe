<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ProductController,
    ProfileController,
    CategoryController,
    SupplierController,
    CashRequestController,
    PurchaseRequestController,
    PurchaseOrderController,
    InvoiceController,
    UserController
};

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
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Dashboard route (protected with 'auth' middleware)
Route::get('/dashboard', fn() => Inertia::render('Dashboard'))->middleware(['auth', 'verified'])->name('dashboard');

// Auth routes (login, logout, registration)
Route::middleware('guest')->group(function () {
    Route::get('/login', fn() => Inertia::render('Auth/Login'))->name('login');
    Route::get('/register', fn() => Inertia::render('Auth/Register'))->name('register');
});

// Profile routes (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // CRUD
    Route::resources([
        'suppliers' => SupplierController::class,
        'products' => ProductController::class,
        'categories' => CategoryController::class,
        'cash-request' => CashRequestController::class,
        'purchase-requests' => PurchaseRequestController::class,
        'purchase-orders' => PurchaseOrderController::class,
        'invoices' => InvoiceController::class,
    ]);

    Route::put('/purchase-requests/{id}/cancel', [PurchaseRequestController::class, 'cancel'])->name('purchase-requests.cancel');
    Route::put('/purchase-requests/{id}/cancel-items', [PurchaseRequestController::class, 'cancelItems'])->name('purchase-requests.cancel-items');
    Route::put('/purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::put('/purchase-orders/items/{id}/cancel', [PurchaseOrderController::class, 'cancelItem'])->name('purchase-orders.items.cancel');
    
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/pr-items', [InvoiceController::class, 'getPrItems']);
    Route::get('/po-items', [InvoiceController::class, 'getPoItems']);
    Route::get('/search-suppliers', [InvoiceController::class, 'searchSuppliers']);
    Route::get('/supplier-vat/{id}', [InvoiceController::class, 'getSupplierVat']);
    
});

require __DIR__.'/auth.php';

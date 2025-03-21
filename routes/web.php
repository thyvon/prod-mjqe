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
    ClearInvoiceController,
    PurchaseRequestController,
    PurchaseOrderController,
    InvoiceController,
    UserController,
    InvoiceAttachmentController
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
        'clear-invoice' => ClearInvoiceController::class,
        'purchase-requests' => PurchaseRequestController::class,
        'purchase-orders' => PurchaseOrderController::class,
        'invoices' => InvoiceController::class,
    ]);

    Route::put('/purchase-requests/{id}/cancel', [PurchaseRequestController::class, 'cancel'])->name('purchase-requests.cancel');
    Route::put('/purchase-requests/{id}/cancel-items', [PurchaseRequestController::class, 'cancelItems'])->name('purchase-requests.cancel-items');
    Route::put('/purchase-orders/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::put('/purchase-orders/items/{id}/cancel', [PurchaseOrderController::class, 'cancelItem'])->name('purchase-orders.items.cancel');
    Route::put('/clear-invoice/{id}/approve', [ClearInvoiceController::class, 'approve'])->name('clear-invoice.approve');
    Route::put('clear-invoices/{id}/approve', [ClearInvoiceController::class, 'approve']);
    
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/pr-items', [InvoiceController::class, 'getPrItems']);
    Route::get('/po-items', [InvoiceController::class, 'getPoItems']);
    Route::get('/search-suppliers', [PurchaseOrderController::class, 'searchSuppliers']);
    Route::get('/search-suppliers', [InvoiceController::class, 'searchSuppliers']);
    Route::get('/invoice-items', [InvoiceController::class, 'itemList'])->name('invoice.items');
    Route::post('/invoices/{id}/force-close', [InvoiceController::class, 'forceClose']);
    Route::post('/invoices/filter', [InvoiceController::class, 'filterInvoiceItems']);
    Route::get('/purchase-invoice-itemspr/{prNumber}', [PurchaseRequestController::class, 'getInvoiceItems']);
    Route::get('/purchase-invoice-itemspo/{poNumber}', [PurchaseOrderController::class, 'getInvoiceItems']);
    Route::get('/filter-cash-requests', [InvoiceController::class, 'filterCashRequests']);
    Route::post('/invoices/{id}/attach-file', [InvoiceController::class, 'attachFile'])->name('invoices.attachFile');
    Route::delete('/invoices/attachments/{id}', [InvoiceController::class, 'deleteFile'])->name('invoices.deleteFile');
    Route::post('/invoices/attachments/{id}/update-file', [InvoiceController::class, 'updateFile'])->name('invoices.updateFile');
    Route::get('/purchase/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/clear-invoices', [ClearInvoiceController::class, 'getClearInvoices']);
});

require __DIR__.'/auth.php';

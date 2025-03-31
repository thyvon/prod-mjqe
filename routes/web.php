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
    InvoiceAttachmentController,
    DashboardController
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
    return Auth::check() ? redirect()->route('dashboard.index') : redirect()->route('login');
});

// Dashboard routes (protected with 'auth' middleware)
Route::middleware(['auth', 'verified'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');

    //PR Dashboard
    Route::get('/pr-count', [DashboardController::class, 'countPRByDateRange'])->name('pr-count');
    Route::get('/pr-completed', [DashboardController::class, 'countPRCompleted'])->name('pr-completed');
    Route::get('/completed-percentage', [DashboardController::class, 'getCompletedPercentage'])->name('completed-percentage');
    Route::get('/pr-pending', [DashboardController::class, 'countPRPending'])->name('pr-pending');
    Route::get('/pending-percentage', [DashboardController::class, 'getPendingPercentage'])->name('pending-percentage');
    Route::get('/pr-partial', [DashboardController::class, 'countPRPartial'])->name('pr-partial');
    Route::get('/partial-percentage', [DashboardController::class, 'getPartialPercentage'])->name('partial-percentage');
    Route::get('/pr-void', [DashboardController::class, 'countPRVoid'])->name('pr-void');
    Route::get('/void-percentage', [DashboardController::class, 'getVoidPercentage'])->name('void-percentage');

    //PO Dashboard
    Route::get('/po-count', [DashboardController::class, 'countPOByDateRange'])->name('po-count');
    Route::get('/po-completed', [DashboardController::class, 'countPOCompleted'])->name('po-completed');
    Route::get('/completed-po-percentage', [DashboardController::class, 'getCompletedPOPercentage'])->name('completed-po-percentage');
    Route::get('/po-pending', [DashboardController::class, 'countPOPending'])->name('po-pending');
    Route::get('/pending-po-percentage', [DashboardController::class, 'getPendingPOPercentage'])->name('pending-po-percentage');
    Route::get('/po-partial', [DashboardController::class, 'countPOPartial'])->name('po-partial');
    Route::get('/partial-po-percentage', [DashboardController::class, 'getPartialPOPercentage'])->name('partial-po-percentage');
    Route::get('/po-void', [DashboardController::class, 'countPOVoid'])->name('po-void');
    Route::get('/void-po-percentage', [DashboardController::class, 'getVoidPOPercentage'])->name('void-po-percentage');

    // Expense Data
    Route::get('/expense-data', [DashboardController::class, 'getExpenseData'])->name('expense-data');
    Route::get('/total-paid-by-month', [DashboardController::class, 'getTotalPaidByMonth']);
    Route::get('/expense-data-by-month', [DashboardController::class, 'getExpenseDataByMonth'])->name('expense-data-by-month');
});

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

    // Profile upload routes
    Route::post('/profile/{id}/upload-signature', [UserController::class, 'uploadSignature'])->name('profile.uploadSignature');
    Route::post('/profile/{id}/upload-profile', [UserController::class, 'uploadProfile'])->name('profile.uploadProfile');

    // Add route to retrieve the file URLs for the signature and profile
    Route::get('/profile/{id}/file/{type}', [UserController::class, 'getFileUrl'])->name('profile.getFileUrl');

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
    Route::get('/search-purchaser', [InvoiceController::class, 'searchPurchaser']);
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

    // Add route to fetch approvals for a specific cash request
    Route::get('/cash-request/{cashRequest}/approvals', [CashRequestController::class, 'getApprovals'])->name('cash-request.approvals');
    Route::post('/cash-request/{cashRequest}/approve', [CashRequestController::class, 'approve'])->name('cash-request.approve');
    Route::post('/cash-request/{cashRequest}/reject', [CashRequestController::class, 'reject'])->name('cash-request.reject');

    // Add route to handle the VAT fetching request
    Route::get('/suppliers/{id}/vat', [SupplierController::class, 'getVat']);

    // Route to fetch user details by ID
    Route::get('/users/{id}/details', [UserController::class, 'getUserDetails'])->name('users.details');
});

require __DIR__.'/auth.php';

<?php
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\Auth\MicrosoftAuthController;
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
    DashboardController,
    CancellationController,
    StatementController,
    ApprovalController,
    DocumentController,
    EvaluationController,
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

// Route::get('/run-migrations', function () {
//     try {
//         Artisan::call('migrate', ['--force' => true]);
//         return 'Migrations ran successfully!';
//     } catch (\Exception $e) {
//         return 'Migration failed: ' . $e->getMessage();
//     }
// });
Route::get('/run-storage-link', function () {
    try {
        Artisan::call('storage:link', ['--force' => true]);
        return 'Storage link created successfully!';
    } catch (\Exception $e) {
        return 'Storage link creation failed: ' . $e->getMessage();
    }
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

    // Campus Data
    Route::get('/campus-expense', [DashboardController::class, 'getPurchaseInvoiceItemData'])->name('campus-expense');
});

// Auth routes (login, logout, registration)
Route::middleware('guest')->group(function () {
    Route::get('/login', fn() => Inertia::render('Auth/Login'))->name('login');
    // Route::get('/register', fn() => Inertia::render('Auth/Register'))->name('register');
    Route::get('/auth/microsoft/redirect', [MicrosoftAuthController::class, 'redirect'])->name('auth.microsoft.redirect');
    Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])->name('auth.microsoft.callback');
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
        'cancellations' => CancellationController::class,
        'statements' => StatementController::class,
        'evaluations' => EvaluationController::class,
        // 'documents' => DocumentController::class,
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
    Route::get('/clear-invoices-invoice', [ClearInvoiceController::class, 'getPurchaseInvoices'])->name('clear-invoices-invoice.fetch');
    Route::get('/clear-invoices-invoice-edit', [ClearInvoiceController::class, 'getPurchaseInvoicesEdit'])->name('clear-invoices-invoice.fetch-edit');

    // Add route to fetch approvals for a specific cash request
    Route::get('/cash-request/{cashRequest}/approvals', [CashRequestController::class, 'getApprovals'])->name('cash-request.approvals');
    Route::post('/cash-request/{cashRequest}/approve', [CashRequestController::class, 'approve'])->name('cash-request.approve');
    Route::post('/cash-request/{cashRequest}/reject', [CashRequestController::class, 'reject'])->name('cash-request.reject');

    // Add route to handle the VAT fetching request
    Route::get('/suppliers/{id}/vat', [SupplierController::class, 'getVat']);

    // Route to fetch user details by ID
    Route::get('/users/{id}/details', [UserController::class, 'getUserDetails'])->name('users.details');

    // Add routes for ClearInvoice approvals and rejections
    Route::post('/clear-invoice/{clearInvoice}/approve', [ClearInvoiceController::class, 'approve'])->name('clear-invoice.approve');
    Route::post('/clear-invoice/{clearInvoice}/reject', [ClearInvoiceController::class, 'reject'])->name('clear-invoice.reject');

    // Add route to fetch approvals for a specific ClearInvoice for create form
    Route::get('/clear-invoice/{clearInvoice}/approvals', [ClearInvoiceController::class, 'getApprovals'])->name('clear-invoice.approvals');
    
    // Additional routes for fetching PR and PO items
    Route::get('/pr-items-cancellation', [CancellationController::class, 'getPrItems'])->name('pr-items-cancellation');
    Route::get('/po-items-cancellation', [CancellationController::class, 'getPoItems'])->name('po-items-cancellation');

    Route::get('/search-suppliers', [StatementController::class, 'searchSuppliers'])->name('suppliers.search');

    // Add a route for fetching PurchaseInvoice data
    Route::get('/statements-purchase-invoices', [StatementController::class, 'getPurchaseInvoices'])->name('statements.purchase-invoices');
    Route::get('/statements/{statement}/approvals', [StatementController::class, 'getApprovals'])->name('statements.approvals');
    Route::post('/statements/{statement}/approve', [StatementController::class, 'approve'])->name('statements.approve');
    Route::post('/statements/{statement}/reject', [StatementController::class, 'reject'])->name('statements.reject');

    // Add a route for the index method of the ApprovalController
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');

    // PR PO cancellation
    Route::post('/cancellations/{cancellation}/approve', [CancellationController::class, 'approve'])->name('cancellations.approve');
    Route::post('/cancellations/{cancellation}/reject', [CancellationController::class, 'reject'])->name('cancellations.reject');

    Route::get('/documents-admin', [DocumentController::class, 'backend'])->name('documents.backend');
    Route::post('/upload-article-image', [DocumentController::class, 'uploadArticleImage']);
    Route::get('/documents/items', [DocumentController::class, 'showAllItems']);

    // Document section routes
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::get('/documents-admin', [DocumentController::class, 'backend'])->name('documents.backend');
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::get('/documents/{id}/edit', [DocumentController::class, 'edit']);
    Route::put('/documents/{id}', [DocumentController::class, 'update']);
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);

    // Document item routes
    Route::get('/documents-items', [DocumentController::class, 'showAllItems']); // Table view
    // File upload route
    Route::post('/documents/upload-image', [DocumentController::class, 'uploadArticleImage']);

    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');

    // Evaluation routes
    Route::post('/evaluations/{evaluation}/approve', [EvaluationController::class, 'approve'])->name('evaluations.approve');
    Route::post('/evaluations/{evaluation}/reject', [EvaluationController::class, 'reject'])->name('evaluations.reject');

});


require __DIR__.'/auth.php';

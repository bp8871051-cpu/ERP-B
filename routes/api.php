<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\CallController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CrmController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Api\FinanceController;
use App\Http\Controllers\Api\GlobalSearchController;
use App\Http\Controllers\Api\HrmController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\LayoutPreferenceController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PosController;
use App\Http\Controllers\Api\ProcurementController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\SupportController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\WorkflowController;
use App\Http\Controllers\Api\Crm\CrmDashboardController;
use App\Http\Controllers\Api\Crm\CrmContactController;
use App\Http\Controllers\Api\Crm\CrmLeadController;
use App\Http\Controllers\Api\Crm\CrmDealController;
use App\Http\Controllers\Api\Crm\CrmPipelineController;
use App\Http\Controllers\Api\Crm\CrmCampaignController;
use App\Http\Controllers\Api\Crm\CrmFeedbackController;
use App\Http\Controllers\Api\Crm\CrmAnalyticsController;
use App\Http\Controllers\Api\Crm\CrmActivityController;
use App\Http\Controllers\Api\Crm\CrmSearchController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\MembershipController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\UiController;
use App\Http\Controllers\Api\HelpController;
use Illuminate\Support\Facades\Route;

// Public authentication and metadata routes
Route::post('/login', [AuthController::class, 'login']);
Route::get('/login', fn() => response()->json(['message' => 'Unauthenticated.'], 401))->name('login');
Route::get('/companies', [AuthController::class, 'companies']);

// UI Interface Showcase APIs (Data Table, Dragula Kanban, Table Preferences)
Route::prefix('v1/ui')->group(function () {
    Route::get('/data-table', [UiController::class, 'getDataTable']);
    Route::post('/data-table', [UiController::class, 'storeDataRecord']);
    Route::put('/data-table/{id}', [UiController::class, 'updateDataRecord']);
    Route::delete('/data-table/{id}', [UiController::class, 'deleteDataRecord']);
    Route::post('/data-table/bulk-delete', [UiController::class, 'bulkDeleteDataRecords']);
    Route::get('/drag-items', [UiController::class, 'getDragItems']);
    Route::put('/drag-items/reorder', [UiController::class, 'reorderDragItems']);
    Route::get('/table-preferences', [UiController::class, 'getTablePreferences']);
    Route::put('/table-preferences', [UiController::class, 'updateTablePreferences']);
});
Route::prefix('ui')->group(function () {
    Route::get('/data-table', [UiController::class, 'getDataTable']);
    Route::post('/data-table', [UiController::class, 'storeDataRecord']);
    Route::put('/data-table/{id}', [UiController::class, 'updateDataRecord']);
    Route::delete('/data-table/{id}', [UiController::class, 'deleteDataRecord']);
    Route::post('/data-table/bulk-delete', [UiController::class, 'bulkDeleteDataRecords']);
    Route::get('/drag-items', [UiController::class, 'getDragItems']);
    Route::put('/drag-items/reorder', [UiController::class, 'reorderDragItems']);
    Route::get('/table-preferences', [UiController::class, 'getTablePreferences']);
    Route::put('/table-preferences', [UiController::class, 'updateTablePreferences']);
});

// Help Module APIs (Documentation, Changelog)
Route::prefix('v1/help')->group(function () {
    Route::get('/documentation', [HelpController::class, 'getDocumentation']);
    Route::get('/documentation/{slug}', [HelpController::class, 'getDocument']);
    Route::get('/changelog', [HelpController::class, 'getChangelog']);
    Route::get('/changelog/{id}', [HelpController::class, 'getChangelogItem']);
});
Route::prefix('help')->group(function () {
    Route::get('/documentation', [HelpController::class, 'getDocumentation']);
    Route::get('/documentation/{slug}', [HelpController::class, 'getDocument']);
    Route::get('/changelog', [HelpController::class, 'getChangelog']);
    Route::get('/changelog/{id}', [HelpController::class, 'getChangelogItem']);
});

// Helper function to register application routes under a given prefix
$registerAppRoutes = function () {
    // 1. CHAT MODULE
    Route::get('/chat/conversations', [ChatController::class, 'conversations']);
    Route::post('/chat/conversations', [ChatController::class, 'createConversation']);
    Route::get('/chat/conversations/{id}/messages', [ChatController::class, 'messages']);
    Route::post('/chat/conversations/{id}/messages', [ChatController::class, 'sendMessage']);
    Route::put('/chat/messages/{id}', [ChatController::class, 'editMessage']);
    Route::delete('/chat/messages/{id}', [ChatController::class, 'deleteMessage']);
    Route::post('/chat/messages/{id}/reaction', [ChatController::class, 'reaction']);
    Route::post('/chat/messages/{id}/pin', [ChatController::class, 'pin']);
    Route::post('/chat/messages/{id}/read', [ChatController::class, 'read']);

    // 2. CALLS MODULE
    Route::get('/calls', [CallController::class, 'index']);
    Route::post('/calls', [CallController::class, 'store']);
    Route::get('/calls/{id}', [CallController::class, 'show']);
    Route::put('/calls/{id}', [CallController::class, 'update']);

    // 3. CALENDAR MODULE
    Route::get('/calendar/events', [CalendarController::class, 'index']);
    Route::post('/calendar/events', [CalendarController::class, 'store']);
    Route::get('/calendar/events/{id}', [CalendarController::class, 'show']);
    Route::put('/calendar/events/{id}', [CalendarController::class, 'update']);
    Route::delete('/calendar/events/{id}', [CalendarController::class, 'destroy']);

    // 4. EMAIL MODULE
    Route::get('/emails', [EmailController::class, 'index']);
    Route::get('/emails/{id}', [EmailController::class, 'show']);
    Route::post('/emails', [EmailController::class, 'store']);
    Route::post('/emails/{id}/reply', [EmailController::class, 'reply']);
    Route::post('/emails/{id}/forward', [EmailController::class, 'forward']);
    Route::put('/emails/{id}/read', [EmailController::class, 'toggleRead']);
    Route::put('/emails/{id}/star', [EmailController::class, 'toggleStar']);
    Route::delete('/emails/{id}', [EmailController::class, 'destroy']);

    // 5. FILE MANAGER MODULE
    Route::get('/files', [FileController::class, 'index']);
    Route::post('/files/upload', [FileController::class, 'upload']);
    Route::post('/files/folders', [FileController::class, 'createFolder']);
    Route::put('/files/{id}', [FileController::class, 'update']);
    Route::delete('/files/{id}', [FileController::class, 'destroy']);
    Route::delete('/files/folders/{id}', [FileController::class, 'destroyFolder']);
    Route::get('/files/{id}/download', [FileController::class, 'download']);
    Route::post('/files/{id}/share', [FileController::class, 'share']);

    // 6. NOTES MODULE
    Route::get('/notes', [NoteController::class, 'index']);
    Route::post('/notes', [NoteController::class, 'store']);
    Route::get('/notes/{id}', [NoteController::class, 'show']);
    Route::put('/notes/{id}', [NoteController::class, 'update']);
    Route::delete('/notes/{id}', [NoteController::class, 'destroy']);
    Route::put('/notes/{id}/pin', [NoteController::class, 'togglePin']);
    Route::put('/notes/{id}/favorite', [NoteController::class, 'toggleFavorite']);
    Route::put('/notes/{id}/archive', [NoteController::class, 'toggleArchive']);

    // 7. TO DO / TASK MODULE
    Route::get('/tasks', [TaskController::class, 'index']);
    Route::post('/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{id}', [TaskController::class, 'show']);
    Route::put('/tasks/{id}', [TaskController::class, 'update']);
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
    Route::post('/tasks/{id}/comments', [TaskController::class, 'comments']);
    Route::post('/tasks/{taskId}/checklists/{checklistId}/toggle', [TaskController::class, 'toggleChecklist']);

    // 8. WORKFLOW & APPROVALS MODULE
    Route::get('/workflows', [WorkflowController::class, 'index']);
    Route::post('/workflows', [WorkflowController::class, 'store']);
    Route::put('/workflows/{id}', [WorkflowController::class, 'update']);
    Route::delete('/workflows/{id}', [WorkflowController::class, 'destroy']);
    Route::get('/workflow-requests', [WorkflowController::class, 'index']);
    Route::post('/workflow-requests', [WorkflowController::class, 'createRequest']);
    Route::post('/workflow-requests/{id}/approve', [WorkflowController::class, 'approve']);
    Route::post('/workflow-requests/{id}/reject', [WorkflowController::class, 'reject']);
    Route::post('/workflow-requests/{id}/request-changes', [WorkflowController::class, 'requestChanges']);
    Route::post('/workflow-requests/{id}/comments', [WorkflowController::class, 'addComment']);

    // NOTIFICATIONS MODULE
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // GLOBAL SEARCH
    Route::get('/search', [GlobalSearchController::class, 'search']);

    // LAYOUT PREFERENCES
    Route::get('/layouts/preferences', [LayoutPreferenceController::class, 'index']);
    Route::post('/layouts/preferences', [LayoutPreferenceController::class, 'store']);
    Route::put('/layouts/preferences', [LayoutPreferenceController::class, 'update']);
    Route::post('/layouts/reset', [LayoutPreferenceController::class, 'reset']);

    // ==========================================
    // INVENTORY MODULE ROUTES
    // ==========================================
    $inventoryGroup = function () {
        // Products
        Route::get('/products/export', [\App\Http\Controllers\Api\ProductController::class, 'export'])->middleware('permission:inventory.view');
        Route::get('/products', [\App\Http\Controllers\Api\ProductController::class, 'index'])->middleware('permission:inventory.view');
        Route::post('/products', [\App\Http\Controllers\Api\ProductController::class, 'store'])->middleware('permission:inventory.create');
        Route::get('/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'show'])->middleware('permission:inventory.view');
        Route::put('/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'update'])->middleware('permission:inventory.edit');
        Route::delete('/products/{id}', [\App\Http\Controllers\Api\ProductController::class, 'destroy'])->middleware('permission:inventory.delete');
        Route::post('/products/{id}/duplicate', [\App\Http\Controllers\Api\ProductController::class, 'duplicate'])->middleware('permission:inventory.create');
        Route::get('/products/{id}/stock-history', [\App\Http\Controllers\Api\ProductController::class, 'stockHistory'])->middleware('permission:inventory.view');

        // Categories
        Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
        Route::post('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'store']);
        Route::get('/categories/{id}', [\App\Http\Controllers\Api\CategoryController::class, 'show']);
        Route::put('/categories/{id}', [\App\Http\Controllers\Api\CategoryController::class, 'update']);
        Route::delete('/categories/{id}', [\App\Http\Controllers\Api\CategoryController::class, 'destroy']);

        // Brands
        Route::get('/brands', [\App\Http\Controllers\Api\BrandController::class, 'index']);
        Route::post('/brands', [\App\Http\Controllers\Api\BrandController::class, 'store']);
        Route::get('/brands/{id}', [\App\Http\Controllers\Api\BrandController::class, 'show']);
        Route::put('/brands/{id}', [\App\Http\Controllers\Api\BrandController::class, 'update']);
        Route::delete('/brands/{id}', [\App\Http\Controllers\Api\BrandController::class, 'destroy']);

        // Units
        Route::get('/units', [\App\Http\Controllers\Api\UnitController::class, 'index']);
        Route::post('/units', [\App\Http\Controllers\Api\UnitController::class, 'store']);
        Route::get('/units/{id}', [\App\Http\Controllers\Api\UnitController::class, 'show']);
        Route::put('/units/{id}', [\App\Http\Controllers\Api\UnitController::class, 'update']);
        Route::delete('/units/{id}', [\App\Http\Controllers\Api\UnitController::class, 'destroy']);

        // Suppliers
        Route::get('/suppliers', [\App\Http\Controllers\Api\SupplierController::class, 'index']);
        Route::post('/suppliers', [\App\Http\Controllers\Api\SupplierController::class, 'store']);
        Route::get('/suppliers/{id}', [\App\Http\Controllers\Api\SupplierController::class, 'show']);
        Route::put('/suppliers/{id}', [\App\Http\Controllers\Api\SupplierController::class, 'update']);
        Route::delete('/suppliers/{id}', [\App\Http\Controllers\Api\SupplierController::class, 'destroy']);

        // Warehouses
        Route::get('/warehouses', [\App\Http\Controllers\Api\WarehouseController::class, 'index']);
        Route::post('/warehouses', [\App\Http\Controllers\Api\WarehouseController::class, 'store']);
        Route::get('/warehouses/{id}', [\App\Http\Controllers\Api\WarehouseController::class, 'show']);
        Route::put('/warehouses/{id}', [\App\Http\Controllers\Api\WarehouseController::class, 'update']);
        Route::delete('/warehouses/{id}', [\App\Http\Controllers\Api\WarehouseController::class, 'destroy']);

        // Stock Overview & Operations
        Route::get('/stock', [\App\Http\Controllers\Api\StockController::class, 'index']);
        Route::get('/stock/movements', [\App\Http\Controllers\Api\StockController::class, 'movements']);
        Route::post('/stock/opening', [\App\Http\Controllers\Api\StockController::class, 'opening']);
        Route::post('/stock/receive', [\App\Http\Controllers\Api\StockController::class, 'receive']);
        Route::post('/stock/issue', [\App\Http\Controllers\Api\StockController::class, 'issue']);
        Route::get('/stock/{id}', [\App\Http\Controllers\Api\StockController::class, 'show']);

        // Stock Adjustments
        Route::get('/stock/adjustments', [\App\Http\Controllers\Api\StockAdjustmentController::class, 'index']);
        Route::post('/stock/adjustments', [\App\Http\Controllers\Api\StockAdjustmentController::class, 'store']);
        Route::get('/stock/adjustments/{id}', [\App\Http\Controllers\Api\StockAdjustmentController::class, 'show']);
        Route::put('/stock/adjustments/{id}', [\App\Http\Controllers\Api\StockAdjustmentController::class, 'update']);
        Route::post('/stock/adjustments/{id}/submit', [\App\Http\Controllers\Api\StockAdjustmentController::class, 'submit']);
        Route::post('/stock/adjustments/{id}/approve', [\App\Http\Controllers\Api\StockAdjustmentController::class, 'approve']);
        Route::post('/stock/adjustments/{id}/reject', [\App\Http\Controllers\Api\StockAdjustmentController::class, 'reject']);

        // Stock Transfers
        Route::get('/stock/transfers', [\App\Http\Controllers\Api\StockTransferController::class, 'index']);
        Route::post('/stock/transfers', [\App\Http\Controllers\Api\StockTransferController::class, 'store']);
        Route::get('/stock/transfers/{id}', [\App\Http\Controllers\Api\StockTransferController::class, 'show']);
        Route::post('/stock/transfers/{id}/submit', [\App\Http\Controllers\Api\StockTransferController::class, 'submit']);
        Route::post('/stock/transfers/{id}/approve', [\App\Http\Controllers\Api\StockTransferController::class, 'approve']);
        Route::post('/stock/transfers/{id}/dispatch', [\App\Http\Controllers\Api\StockTransferController::class, 'dispatch']);
        Route::post('/stock/transfers/{id}/receive', [\App\Http\Controllers\Api\StockTransferController::class, 'receive']);
        Route::post('/stock/transfers/{id}/cancel', [\App\Http\Controllers\Api\StockTransferController::class, 'cancel']);
    };

    Route::prefix('inventory')->middleware('permission:inventory.view')->group(function () use ($inventoryGroup) {
        Route::get('/dashboard', [\App\Http\Controllers\Api\InventoryController::class, 'dashboard']);
        Route::get('/summary', [\App\Http\Controllers\Api\InventoryController::class, 'summary']);
        Route::get('/movement', [\App\Http\Controllers\Api\InventoryController::class, 'movement']);
        Route::get('/value-trend', [\App\Http\Controllers\Api\InventoryController::class, 'valueTrend']);
        Route::get('/category-distribution', [\App\Http\Controllers\Api\InventoryController::class, 'categoryDistribution']);
        Route::get('/top-products', [\App\Http\Controllers\Api\InventoryController::class, 'topProducts']);
        Route::get('/low-stock', [\App\Http\Controllers\Api\InventoryController::class, 'lowStock']);
        Route::get('/out-of-stock', [\App\Http\Controllers\Api\InventoryController::class, 'outOfStock']);
        Route::get('/warehouses', [\App\Http\Controllers\Api\InventoryController::class, 'warehouses']);
        Route::get('/suppliers', [\App\Http\Controllers\Api\InventoryController::class, 'suppliers']);
        Route::get('/forecast', [\App\Http\Controllers\Api\InventoryController::class, 'forecast']);
        Route::get('/reorder-recommendations', [\App\Http\Controllers\Api\InventoryController::class, 'reorderRecommendations']);
        Route::get('/activities', [\App\Http\Controllers\Api\InventoryController::class, 'activities']);
        Route::get('/purchase-impact', [\App\Http\Controllers\Api\InventoryController::class, 'purchaseImpact']);
        Route::get('/sales-impact', [\App\Http\Controllers\Api\InventoryController::class, 'salesImpact']);
        Route::get('/reports', [\App\Http\Controllers\Api\InventoryController::class, 'reports']);
        Route::post('/export', [\App\Http\Controllers\Api\InventoryController::class, 'export']);
        Route::post('/add-stock', [\App\Http\Controllers\Api\InventoryController::class, 'addStock']);
        Route::post('/stock-adjustment', [\App\Http\Controllers\Api\InventoryController::class, 'adjustStock']);
        Route::post('/stock-transfer', [\App\Http\Controllers\Api\InventoryController::class, 'transferStock']);
        $inventoryGroup();
    });
    $inventoryGroup(); // Also allow direct calls like /api/v1/products, /api/v1/categories, etc.

    // ==========================================
    // ENTERPRISE SALES MODULE ROUTES
    // ==========================================
    $salesRoutes = function () {
        Route::get('/dashboard', [SalesController::class, 'dashboard']);
        Route::get('/analytics', [SalesController::class, 'analytics']);

        // Customers
        Route::get('/customers', [SalesController::class, 'customers'])->middleware('permission:sales.view');
        Route::post('/customers', [SalesController::class, 'storeCustomer'])->middleware('permission:sales.create');
        Route::get('/customers/{id}', [SalesController::class, 'showCustomer'])->middleware('permission:sales.view');
        Route::put('/customers/{id}', [SalesController::class, 'updateCustomer'])->middleware('permission:sales.edit');
        Route::delete('/customers/{id}', [SalesController::class, 'destroyCustomer'])->middleware('permission:sales.delete');

        // Sales Orders
        Route::get('/orders', [SalesController::class, 'orders']);
        Route::post('/orders', [SalesController::class, 'storeOrder']);
        Route::get('/orders/{id}', [SalesController::class, 'showOrder']);
        Route::put('/orders/{id}', [SalesController::class, 'updateOrder']);
        Route::post('/orders/{id}/confirm', [SalesController::class, 'confirmOrder']);
        Route::post('/orders/{id}/cancel', [SalesController::class, 'cancelOrder']);
        Route::post('/orders/{id}/convert-to-invoice', [SalesController::class, 'convertOrderToInvoice']);

        // Invoices
        Route::get('/invoices', [SalesController::class, 'invoices']);
        Route::post('/invoices', [SalesController::class, 'storeInvoice']);
        Route::get('/invoices/{id}', [SalesController::class, 'showInvoice']);
        Route::put('/invoices/{id}', [SalesController::class, 'updateInvoice']);
        Route::post('/invoices/{id}/send', [SalesController::class, 'sendInvoice']);
        Route::post('/invoices/{id}/payment', [SalesController::class, 'recordPayment']);

        // Recurring Invoices
        Route::get('/recurring-invoices', [SalesController::class, 'recurringInvoices']);
        Route::post('/recurring-invoices', [SalesController::class, 'storeRecurringInvoice']);
        Route::put('/recurring-invoices/{id}', [SalesController::class, 'updateRecurringInvoice']);

        // Invoice Templates
        Route::get('/invoice-templates', [SalesController::class, 'invoiceTemplates']);
        Route::post('/invoice-templates', [SalesController::class, 'storeInvoiceTemplate']);
        Route::put('/invoice-templates/{id}', [SalesController::class, 'updateInvoiceTemplate']);
        Route::delete('/invoice-templates/{id}', [SalesController::class, 'destroyInvoiceTemplate']);

        // Quotes
        Route::get('/quotes', [SalesController::class, 'quotes']);
        Route::post('/quotes', [SalesController::class, 'storeQuote']);
        Route::get('/quotes/{id}', [SalesController::class, 'showQuote']);
        Route::put('/quotes/{id}', [SalesController::class, 'updateQuote']);
        Route::post('/quotes/{id}/accept', [SalesController::class, 'acceptQuote']);
        Route::post('/quotes/{id}/convert', [SalesController::class, 'convertQuote']);

        // Credit Notes
        Route::get('/credit-notes', [SalesController::class, 'creditNotes']);
        Route::post('/credit-notes', [SalesController::class, 'storeCreditNote']);
        Route::get('/credit-notes/{id}', [SalesController::class, 'showCreditNote']);
        Route::post('/credit-notes/{id}/issue', [SalesController::class, 'issueCreditNote']);

        // Cash Sales
        Route::get('/cash-sales', [SalesController::class, 'cashSales']);
        Route::post('/cash-sales', [SalesController::class, 'storeCashSale']);

        // Refunds
        Route::get('/refunds', [SalesController::class, 'refunds']);
        Route::post('/refunds', [SalesController::class, 'storeRefund']);
        Route::post('/refunds/{id}/approve', [SalesController::class, 'approveRefund']);
        Route::post('/refunds/{id}/process', [SalesController::class, 'processRefund']);

        // Delivery Notes
        Route::get('/delivery-notes', [SalesController::class, 'deliveryNotes']);
        Route::post('/delivery-notes', [SalesController::class, 'storeDeliveryNote']);
        Route::get('/delivery-notes/{id}', [SalesController::class, 'showDeliveryNote']);
        Route::post('/delivery-notes/{id}/dispatch', [SalesController::class, 'dispatchDelivery']);
        Route::post('/delivery-notes/{id}/deliver', [SalesController::class, 'deliverDelivery']);
    };
    Route::prefix('sales')->middleware('permission:sales.view')->group($salesRoutes);

    // ==========================================
    // ENTERPRISE PURCHASE MODULE ROUTES
    // ==========================================
    $purchaseRoutes = function () {
        Route::get('/dashboard', [PurchaseController::class, 'dashboard']);
        Route::get('/analytics', [PurchaseController::class, 'analytics']);

        // Vendors
        Route::get('/vendors', [PurchaseController::class, 'vendors']);
        Route::post('/vendors', [PurchaseController::class, 'storeVendor']);
        Route::get('/vendors/{id}', [PurchaseController::class, 'showVendor']);
        Route::put('/vendors/{id}', [PurchaseController::class, 'updateVendor']);
        Route::delete('/vendors/{id}', [PurchaseController::class, 'destroyVendor']);

        // Purchase Orders
        Route::get('/orders', [PurchaseController::class, 'orders']);
        Route::post('/orders', [PurchaseController::class, 'storeOrder']);
        Route::get('/orders/{id}', [PurchaseController::class, 'showOrder']);
        Route::put('/orders/{id}', [PurchaseController::class, 'updateOrder']);
        Route::post('/orders/{id}/approve', [PurchaseController::class, 'approveOrder']);
        Route::post('/orders/{id}/receive', [PurchaseController::class, 'receiveOrder']);
        Route::post('/orders/{id}/cancel', [PurchaseController::class, 'cancelOrder']);

        // Purchases
        Route::get('/purchases', [PurchaseController::class, 'purchases']);
        Route::post('/purchases', [PurchaseController::class, 'storePurchase']);
        Route::get('/purchases/{id}', [PurchaseController::class, 'showPurchase']);
        Route::post('/purchases/{id}/payment', [PurchaseController::class, 'recordPayment']);

        // Purchase Returns
        Route::get('/returns', [PurchaseController::class, 'returns']);
        Route::post('/returns', [PurchaseController::class, 'storeReturn']);
        Route::post('/returns/{id}/approve', [PurchaseController::class, 'approveReturn']);
        Route::post('/returns/{id}/complete', [PurchaseController::class, 'completeReturn']);
    };
    Route::prefix('purchase')->middleware('permission:procurement.view')->group($purchaseRoutes);

    // ==========================================
    // ENTERPRISE FINANCE MODULE ROUTES
    // ==========================================
    $financeRoutes = function () {
        Route::get('/dashboard', [FinanceController::class, 'dashboard']);

        // Expenses
        Route::get('/expenses', [FinanceController::class, 'expenses'])->middleware('permission:finance.view');
        Route::post('/expenses', [FinanceController::class, 'storeExpense'])->middleware('permission:finance.create');
        Route::get('/expenses/{id}', [FinanceController::class, 'showExpense'])->middleware('permission:finance.view');
        Route::put('/expenses/{id}', [FinanceController::class, 'updateExpense'])->middleware('permission:finance.edit');
        Route::delete('/expenses/{id}', [FinanceController::class, 'destroyExpense'])->middleware('permission:finance.delete');
        Route::post('/expenses/{id}/approve', [FinanceController::class, 'approveExpense']);
        Route::post('/expenses/{id}/pay', [FinanceController::class, 'payExpense']);

        // Expense Categories
        Route::get('/expense-categories', [FinanceController::class, 'expenseCategories']);
        Route::post('/expense-categories', [FinanceController::class, 'storeExpenseCategory']);

        // Central Payments
        Route::get('/payments', [FinanceController::class, 'payments']);
        Route::post('/payments', [FinanceController::class, 'storePayment']);

        // Cashflow & Accounts
        Route::get('/cashflow', [FinanceController::class, 'cashflow']);
        Route::get('/accounts', [FinanceController::class, 'accounts']);
        Route::post('/accounts', [FinanceController::class, 'storeAccount']);

        // Budgeting
        Route::get('/budgets', [FinanceController::class, 'budgets']);
        Route::post('/budgets', [FinanceController::class, 'storeBudget']);

        // Taxes
        Route::get('/taxes', [FinanceController::class, 'taxes']);
        Route::post('/taxes', [FinanceController::class, 'storeTax']);

        // Reports
        Route::get('/reports', [FinanceController::class, 'reports']);
    };
    Route::prefix('finance')->middleware('permission:finance.view')->group($financeRoutes);

    // ==========================================
    // ENTERPRISE HRM MODULE ROUTES
    // ==========================================
    $hrmRoutes = function () {
        Route::get('/dashboard', [HrmController::class, 'dashboard']);

        // Employees
        Route::get('/employees', [HrmController::class, 'employees'])->middleware('permission:hrm.view');
        Route::post('/employees', [HrmController::class, 'storeEmployee'])->middleware('permission:hrm.create');
        Route::get('/employees/{id}', [HrmController::class, 'showEmployee'])->middleware('permission:hrm.view');
        Route::put('/employees/{id}', [HrmController::class, 'updateEmployee'])->middleware('permission:hrm.edit');
        Route::delete('/employees/{id}', [HrmController::class, 'destroyEmployee'])->middleware('permission:hrm.delete');

        // Departments & Designations
        Route::get('/departments', [HrmController::class, 'departments']);
        Route::post('/departments', [HrmController::class, 'storeDepartment']);
        Route::get('/designations', [HrmController::class, 'designations']);
        Route::post('/designations', [HrmController::class, 'storeDesignation']);

        // Attendance
        Route::get('/attendance', [HrmController::class, 'attendance']);
        Route::post('/attendance', [HrmController::class, 'markAttendance']);
        Route::post('/attendance/check-in', [HrmController::class, 'checkIn']);
        Route::post('/attendance/check-out', [HrmController::class, 'checkOut']);

        // Leave Management
        Route::get('/leave', [HrmController::class, 'leave']);
        Route::post('/leave', [HrmController::class, 'storeLeave']);
        Route::post('/leave/{id}/approve', [HrmController::class, 'approveLeave']);
        Route::post('/leave/{id}/reject', [HrmController::class, 'rejectLeave']);
        Route::get('/leave/balances', [HrmController::class, 'leaveBalances']);

        // Holidays
        Route::get('/holidays', [HrmController::class, 'holidays']);
        Route::post('/holidays', [HrmController::class, 'storeHoliday']);

        // Payroll
        Route::get('/payroll', [HrmController::class, 'payroll']);
        Route::post('/payroll/calculate', [HrmController::class, 'calculatePayroll']);
        Route::post('/payroll/{id}/pay', [HrmController::class, 'payPayroll']);

        // Recruitment
        Route::get('/recruitment/jobs', [HrmController::class, 'recruitmentJobs']);
        Route::post('/recruitment/jobs', [HrmController::class, 'storeJob']);
        Route::get('/recruitment/candidates', [HrmController::class, 'recruitmentCandidates']);
        Route::post('/recruitment/candidates', [HrmController::class, 'storeCandidate']);
        Route::post('/recruitment/candidates/{id}/stage', [HrmController::class, 'updateCandidateStage']);
        Route::post('/recruitment/interviews', [HrmController::class, 'scheduleInterview']);
        Route::post('/recruitment/offers', [HrmController::class, 'createOffer']);

        // Performance & Appraisal
        Route::get('/performance', [HrmController::class, 'performance']);
        Route::post('/performance/review', [HrmController::class, 'submitReview']);

        // Training & Development
        Route::get('/training', [HrmController::class, 'training']);
        Route::post('/training', [HrmController::class, 'storeTraining']);
        Route::post('/training/enroll', [HrmController::class, 'enrollTraining']);

        // HR Analytics
        Route::get('/analytics', [HrmController::class, 'analytics']);
    };
    Route::prefix('hrm')->middleware('permission:hrm.view')->group($hrmRoutes);

    // ==========================================
    // ENTERPRISE CRM MODULE ROUTES
    // ==========================================
    $crmRoutes = function () {
        Route::get('/dashboard', [CrmDashboardController::class, 'index']);
        Route::get('/search', [CrmSearchController::class, 'search']);

        // Contacts
        Route::get('/contacts/export', [CrmContactController::class, 'export']);
        Route::post('/contacts/import', [CrmContactController::class, 'import']);
        Route::get('/contacts', [CrmContactController::class, 'index']);
        Route::post('/contacts', [CrmContactController::class, 'store']);
        Route::get('/contacts/{id}', [CrmContactController::class, 'show']);
        Route::put('/contacts/{id}', [CrmContactController::class, 'update']);
        Route::delete('/contacts/{id}', [CrmContactController::class, 'destroy']);

        // Leads
        Route::get('/leads/export', [CrmLeadController::class, 'export']);
        Route::get('/leads', [CrmLeadController::class, 'index']);
        Route::post('/leads', [CrmLeadController::class, 'store']);
        Route::get('/leads/{id}', [CrmLeadController::class, 'show']);
        Route::put('/leads/{id}', [CrmLeadController::class, 'update']);
        Route::delete('/leads/{id}', [CrmLeadController::class, 'destroy']);
        Route::post('/leads/{id}/convert', [CrmLeadController::class, 'convert']);
        Route::post('/leads/{id}/score', [CrmLeadController::class, 'score']);

        // Lead Sources
        Route::get('/lead-sources', function () {
            $sources = \App\Models\CrmLeadSource::where('is_active', true)->get();
            return response()->json(['status' => 'success', 'data' => $sources]);
        });

        // Deals
        Route::get('/deals/export', [CrmDealController::class, 'export']);
        Route::get('/deals', [CrmDealController::class, 'index']);
        Route::post('/deals', [CrmDealController::class, 'store']);
        Route::get('/deals/{id}', [CrmDealController::class, 'show']);
        Route::put('/deals/{id}', [CrmDealController::class, 'update']);
        Route::delete('/deals/{id}', [CrmDealController::class, 'destroy']);
        Route::post('/deals/{id}/stage', [CrmDealController::class, 'updateStage']);
        Route::post('/deals/{id}/create-sales-order', [CrmDealController::class, 'createSalesOrder']);

        // Pipelines & Stages
        Route::get('/pipelines', [CrmPipelineController::class, 'index']);
        Route::post('/pipelines', [CrmPipelineController::class, 'store']);
        Route::put('/pipelines/{id}', [CrmPipelineController::class, 'update']);
        Route::delete('/pipelines/{id}', [CrmPipelineController::class, 'destroy']);
        Route::post('/pipelines/{id}/stages', [CrmPipelineController::class, 'addStage']);
        Route::put('/pipelines/{id}/stages/reorder', [CrmPipelineController::class, 'reorderStages']);
        Route::put('/pipelines/{id}/stages/{stageId}', [CrmPipelineController::class, 'updateStage']);
        Route::delete('/pipelines/{id}/stages/{stageId}', [CrmPipelineController::class, 'deleteStage']);

        // Campaigns
        Route::get('/campaigns', [CrmCampaignController::class, 'index']);
        Route::post('/campaigns', [CrmCampaignController::class, 'store']);
        Route::get('/campaigns/{id}', [CrmCampaignController::class, 'show']);
        Route::put('/campaigns/{id}', [CrmCampaignController::class, 'update']);
        Route::delete('/campaigns/{id}', [CrmCampaignController::class, 'destroy']);
        Route::post('/campaigns/{id}/launch', [CrmCampaignController::class, 'launch']);
        Route::post('/campaigns/{id}/pause', [CrmCampaignController::class, 'pause']);
        Route::post('/campaigns/{id}/audience', [CrmCampaignController::class, 'updateAudience']);

        // Feedback
        Route::get('/feedback/dashboard', [CrmFeedbackController::class, 'dashboard']);
        Route::get('/feedback', [CrmFeedbackController::class, 'index']);
        Route::post('/feedback', [CrmFeedbackController::class, 'store']);
        Route::get('/feedback/{id}', [CrmFeedbackController::class, 'show']);
        Route::put('/feedback/{id}', [CrmFeedbackController::class, 'update']);
        Route::delete('/feedback/{id}', [CrmFeedbackController::class, 'destroy']);
        Route::post('/feedback/{id}/resolve', [CrmFeedbackController::class, 'resolve']);

        // Analytics
        Route::get('/analytics', [CrmAnalyticsController::class, 'index']);
        Route::get('/analytics/customers', [CrmAnalyticsController::class, 'customers']);
        Route::post('/analytics/segments/evaluate', [CrmAnalyticsController::class, 'evaluateSegments']);

        // Centralized Activities
        Route::get('/activities', [CrmActivityController::class, 'index']);
        Route::post('/activities', [CrmActivityController::class, 'store']);
        Route::put('/activities/{id}', [CrmActivityController::class, 'update']);
        Route::delete('/activities/{id}', [CrmActivityController::class, 'destroy']);
        Route::post('/activities/calls', [CrmActivityController::class, 'logCall']);
        Route::post('/activities/meetings', [CrmActivityController::class, 'scheduleMeeting']);
        Route::post('/activities/emails', [CrmActivityController::class, 'logEmail']);
        Route::post('/activities/notes', [CrmActivityController::class, 'addNote']);

        // Tasks
        Route::get('/tasks', [CrmActivityController::class, 'listTasks']);
        Route::post('/tasks', [CrmActivityController::class, 'storeTask']);
        Route::put('/tasks/{id}', [CrmActivityController::class, 'updateTask']);
        Route::delete('/tasks/{id}', [CrmActivityController::class, 'deleteTask']);
    };
    Route::prefix('crm')->middleware('permission:crm.view')->group($crmRoutes);

    // ==========================================
    // ENTERPRISE POS MODULE ROUTES
    // ==========================================
    $posRoutes = function () {
        Route::get('/dashboard', [PosController::class, 'dashboard']);
        Route::get('/products', [PosController::class, 'products']);
        Route::post('/calculate', [PosController::class, 'calculate']);
        Route::get('/orders', [PosController::class, 'orders']);
        Route::post('/orders', [PosController::class, 'store']);
        Route::get('/orders/{id}', [PosController::class, 'show']);
        Route::post('/orders/{id}/checkout', [PosController::class, 'checkout']);
        Route::post('/orders/{id}/refund', [PosController::class, 'refund']);
        Route::post('/orders/{id}/cancel', [PosController::class, 'cancel']);
        Route::get('/barcodes', [PosController::class, 'barcodes']);
        Route::post('/barcodes/generate', [PosController::class, 'generateBarcode']);
        Route::post('/qr/generate', [PosController::class, 'generateQr']);
        Route::get('/settings', [PosController::class, 'getSettings']);
        Route::put('/settings', [PosController::class, 'updateSettings']);
        Route::get('/register/current', [PosController::class, 'currentRegisterSession']);
        Route::post('/register/open', [PosController::class, 'openRegisterSession']);
        Route::post('/register/{id}/close', [PosController::class, 'closeRegisterSession']);
    };
    Route::prefix('pos')->middleware('permission:pos.view')->group($posRoutes);

    // ==========================================
    // ENTERPRISE FIXED ASSETS MODULE ROUTES
    // ==========================================
    $assetRoutes = function () {
        Route::get('/dashboard', [AssetController::class, 'dashboard']);
        Route::get('/lookups', [AssetController::class, 'lookups']);
        Route::get('/analytics', [AssetController::class, 'analytics']);
        Route::get('/depreciation', [AssetController::class, 'depreciation']);
        Route::post('/depreciation/calculate', [AssetController::class, 'calculateDepreciation']);
        Route::get('/assignments', [AssetController::class, 'assignments']);
        Route::get('/maintenance', [AssetController::class, 'maintenance']);
        Route::post('/maintenance', [AssetController::class, 'storeMaintenance']);
        Route::put('/maintenance/{id}', [AssetController::class, 'updateMaintenance']);
        Route::get('/disposals', [AssetController::class, 'disposals']);
        Route::post('/disposals', [AssetController::class, 'storeDisposal']);
        Route::post('/disposals/{id}/approve', [AssetController::class, 'approveDisposal']);
        Route::get('/', [AssetController::class, 'index']);
        Route::post('/', [AssetController::class, 'store']);
        Route::get('/{id}', [AssetController::class, 'show']);
        Route::put('/{id}', [AssetController::class, 'update']);
        Route::delete('/{id}', [AssetController::class, 'destroy']);
        Route::post('/{id}/assign', [AssetController::class, 'assign']);
        Route::post('/{id}/return', [AssetController::class, 'returnAsset']);
    };
    Route::prefix('assets')->middleware('permission:assets.view')->group($assetRoutes);

    // ==========================================
    // ENTERPRISE DOCUMENT MANAGEMENT MODULE ROUTES
    // ==========================================
    $documentRoutes = function () {
        Route::get('/dashboard', [DocumentController::class, 'dashboard']);
        Route::get('/folders', [DocumentController::class, 'folders']);
        Route::get('/policies', [DocumentController::class, 'policies']);
        Route::post('/policies', [DocumentController::class, 'storePolicy']);
        Route::get('/compliance', [DocumentController::class, 'compliance']);
        Route::post('/compliance', [DocumentController::class, 'storeCompliance']);
        Route::get('/compliance/alerts', [DocumentController::class, 'complianceAlerts']);
        Route::get('/workflows', [DocumentController::class, 'workflows']);
        Route::post('/workflows', [DocumentController::class, 'storeWorkflow']);
        Route::get('/', [DocumentController::class, 'index']);
        Route::post('/', [DocumentController::class, 'store']);
        Route::get('/{id}', [DocumentController::class, 'show']);
        Route::put('/{id}', [DocumentController::class, 'update']);
        Route::delete('/{id}', [DocumentController::class, 'destroy']);
        Route::post('/{id}/upload-version', [DocumentController::class, 'uploadVersion']);
        Route::get('/{id}/versions', [DocumentController::class, 'versions']);
        Route::get('/{id}/download', [DocumentController::class, 'download']);
        Route::post('/{id}/share', [DocumentController::class, 'share']);
        Route::post('/{id}/approve', [DocumentController::class, 'approve']);
        Route::post('/{id}/reject', [DocumentController::class, 'reject']);
        Route::post('/{id}/request-changes', [DocumentController::class, 'requestChanges']);
    };
    Route::prefix('documents')->middleware('permission:documents.view')->group($documentRoutes);

    // ==========================================
    // MODULE 1: ENTERPRISE SUPPORT MANAGEMENT ROUTES
    // ==========================================
    $supportRoutes = function () {
        Route::get('/dashboard', [SupportController::class, 'dashboard']);
        Route::get('/contact-messages', [SupportController::class, 'contactMessages']);
        Route::post('/contact-messages', [SupportController::class, 'storeContactMessage']);
        Route::post('/contact-messages/{id}/reply', [SupportController::class, 'replyContactMessage']);
        Route::post('/contact-messages/{id}/convert-to-ticket', [SupportController::class, 'convertContactMessage']);
        Route::get('/tickets', [SupportController::class, 'tickets']);
        Route::post('/tickets', [SupportController::class, 'storeTicket']);
        Route::get('/tickets/{id}', [SupportController::class, 'showTicket']);
        Route::post('/tickets/{id}/reply', [SupportController::class, 'replyTicket']);
        Route::post('/tickets/{id}/assign', [SupportController::class, 'assignTicket']);
        Route::post('/tickets/{id}/status', [SupportController::class, 'updateTicketStatus']);
        Route::post('/tickets/{id}/merge', [SupportController::class, 'mergeTicket']);
        Route::post('/tickets/{id}/convert-to-kb', [SupportController::class, 'convertTicketToKb']);
        Route::get('/knowledge-base', [SupportController::class, 'knowledgeBase']);
        Route::post('/knowledge-base', [SupportController::class, 'storeKbArticle']);
        Route::get('/knowledge-base/{id}', [SupportController::class, 'showKbArticle']);
        Route::post('/knowledge-base/{id}/vote', [SupportController::class, 'voteKbArticle']);
        Route::get('/sla', [SupportController::class, 'slaPolicies']);
        Route::post('/sla', [SupportController::class, 'storeSlaPolicy']);
    };
    Route::prefix('support')->middleware('permission:support.view')->group($supportRoutes);

    // ==========================================
    // MODULE 2: ENTERPRISE MEMBERSHIP MANAGEMENT ROUTES
    // ==========================================
    $membershipRoutes = function () {
        Route::get('/dashboard', [MembershipController::class, 'dashboard']);
        Route::get('/plans', [MembershipController::class, 'plans']);
        Route::post('/plans', [MembershipController::class, 'storePlan']);
        Route::put('/plans/{id}', [MembershipController::class, 'updatePlan']);
        Route::get('/addons', [MembershipController::class, 'addons']);
        Route::post('/addons', [MembershipController::class, 'storeAddon']);
        Route::get('/members', [MembershipController::class, 'members']);
        Route::post('/members', [MembershipController::class, 'storeMember']);
        Route::get('/members/{id}', [MembershipController::class, 'showMember']);
        Route::post('/members/{id}/renew', [MembershipController::class, 'renewMember']);
        Route::get('/members/{id}/calculate-prorata', [MembershipController::class, 'calculateProrata']);
        Route::post('/members/{id}/switch-plan', [MembershipController::class, 'switchPlanMember']);
        Route::post('/members/{id}/cancel', [MembershipController::class, 'cancelMember']);
        Route::get('/transactions', [MembershipController::class, 'transactions']);
    };
    Route::prefix('membership')->middleware('permission:membership.view')->group($membershipRoutes);

    // ==========================================
    // MODULE 3: ENTERPRISE SYSTEM MANAGEMENT ROUTES
    // ==========================================
    $systemRoutes = function () {
        Route::get('/users', [SystemController::class, 'users']);
        Route::post('/users', [SystemController::class, 'storeUser']);
        Route::get('/users/{id}', [SystemController::class, 'showUser']);
        Route::put('/users/{id}', [SystemController::class, 'updateUser']);
        Route::post('/users/{id}/toggle-status', [SystemController::class, 'toggleUserStatus']);
        Route::post('/users/{id}/reset-password', [SystemController::class, 'resetUserPassword']);
        Route::get('/roles', [SystemController::class, 'roles']);
        Route::post('/roles', [SystemController::class, 'storeRole']);
        Route::get('/permissions', [SystemController::class, 'permissions']);
        Route::post('/roles/{id}/permissions', [SystemController::class, 'syncRolePermissions']);
        Route::get('/delete-requests', [SystemController::class, 'deleteRequests']);
        Route::post('/delete-requests', [SystemController::class, 'storeDeleteRequest']);
        Route::post('/delete-requests/{id}/approve', [SystemController::class, 'approveDeleteRequest']);
        Route::post('/delete-requests/{id}/reject', [SystemController::class, 'rejectDeleteRequest']);
        Route::get('/audit-logs', [SystemController::class, 'auditLogs']);

        // Settings nested under system as well
        Route::get('/settings', [SettingsController::class, 'index']);
        Route::post('/settings/general', [SettingsController::class, 'updateGeneral']);
        Route::post('/settings/security', [SettingsController::class, 'updateSecurity']);
        Route::post('/settings/notifications', [SettingsController::class, 'updateNotifications']);
        Route::post('/settings/smtp-test', [SettingsController::class, 'testSmtp']);
        Route::get('/integrations', [SettingsController::class, 'integrations']);
        Route::get('/api-keys', [SettingsController::class, 'apiKeys']);
        Route::post('/api-keys', [SettingsController::class, 'storeApiKey']);
        Route::delete('/api-keys/{id}', [SettingsController::class, 'revokeApiKey']);
        Route::get('/webhooks', [SettingsController::class, 'webhooks']);
        Route::post('/webhooks', [SettingsController::class, 'storeWebhook']);
        Route::delete('/webhooks/{id}', [SettingsController::class, 'deleteWebhook']);
        Route::get('/sessions', [SettingsController::class, 'sessions']);
    };
    Route::prefix('system')->middleware('permission:users.view')->group($systemRoutes);

    // Module 4 direct shortcut
    Route::prefix('settings')->middleware('permission:settings.view')->group(function () {
        Route::get('/', [SettingsController::class, 'index']);
        Route::post('/general', [SettingsController::class, 'updateGeneral']);
        Route::post('/security', [SettingsController::class, 'updateSecurity']);
        Route::post('/notifications', [SettingsController::class, 'updateNotifications']);
        Route::post('/smtp-test', [SettingsController::class, 'testSmtp']);
        Route::get('/integrations', [SettingsController::class, 'integrations']);
        Route::get('/api-keys', [SettingsController::class, 'apiKeys']);
        Route::post('/api-keys', [SettingsController::class, 'storeApiKey']);
        Route::delete('/api-keys/{id}', [SettingsController::class, 'revokeApiKey']);
        Route::get('/webhooks', [SettingsController::class, 'webhooks']);
        Route::post('/webhooks', [SettingsController::class, 'storeWebhook']);
        Route::delete('/webhooks/{id}', [SettingsController::class, 'deleteWebhook']);
        Route::get('/sessions', [SettingsController::class, 'sessions']);
    });
};

// Protected enterprise ERP API routes
Route::middleware('auth:sanctum')->group(function () use ($registerAppRoutes) {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Core Dashboard API Endpoints
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/hrm/dashboard', [HrmController::class, 'dashboard']);
    Route::get('/inventory/dashboard', [InventoryController::class, 'dashboard']);
    Route::get('/crm/dashboard', [CrmDashboardController::class, 'index']);
    Route::get('/pos/dashboard', [PosController::class, 'dashboard']);
    Route::get('/finance/dashboard', [FinanceController::class, 'dashboard']);
    Route::get('/sales/dashboard', [SalesController::class, 'dashboard']);
    Route::get('/purchase/dashboard', [PurchaseController::class, 'dashboard']);
    Route::get('/procurement/dashboard', [ProcurementController::class, 'dashboard']);
    Route::get('/projects/dashboard', [ProjectController::class, 'dashboard']);
    Route::get('/support/dashboard', [SupportController::class, 'dashboard']);

    // Applications & Inventory Module Routes (Direct)
    $registerAppRoutes();

    // Applications & Inventory Module Routes (Versioned v1 prefix)
    Route::prefix('v1')->group($registerAppRoutes);
});


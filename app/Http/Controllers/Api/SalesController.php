<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InvoiceTemplate;
use App\Models\RecurringInvoice;
use App\Services\CashSaleService;
use App\Services\CreditNoteService;
use App\Services\CustomerService;
use App\Services\DeliveryService;
use App\Services\InvoiceService;
use App\Services\RefundService;
use App\Services\SalesAnalyticsService;
use App\Services\SalesOrderService;
use App\Services\SalesQuoteService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    public function __construct(
        protected CustomerService $customerService,
        protected SalesOrderService $salesOrderService,
        protected InvoiceService $invoiceService,
        protected SalesQuoteService $salesQuoteService,
        protected CreditNoteService $creditNoteService,
        protected CashSaleService $cashSaleService,
        protected RefundService $refundService,
        protected DeliveryService $deliveryService,
        protected SalesAnalyticsService $salesAnalyticsService,
    ) {}

    // DASHBOARD & ANALYTICS
    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $data = $this->salesAnalyticsService->getDashboardMetrics($companyId);
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function analytics(Request $request): JsonResponse
    {
        return $this->dashboard($request);
    }

    // CUSTOMERS
    public function customers(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $perPage = (int) $request->query('per_page', 15);
        $customers = $this->customerService->getList($request->all(), $companyId, $perPage);
        return response()->json(['status' => 'success', 'data' => $customers]);
    }

    public function showCustomer(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $customer = $this->customerService->getDetails($id, $companyId);
        return response()->json(['status' => 'success', 'data' => $customer]);
    }

    public function storeCustomer(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $request->all();
        $data['company_id'] = $companyId;

        $customer = $this->customerService->create($data, $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Customer created successfully', 'data' => $customer], 201);
    }

    public function updateCustomer(Request $request, int $id): JsonResponse
    {
        $customer = $this->customerService->update($id, $request->all(), $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Customer updated successfully', 'data' => $customer]);
    }

    public function destroyCustomer(Request $request, int $id): JsonResponse
    {
        $this->customerService->delete($id, $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Customer deleted successfully']);
    }

    // SALES ORDERS
    public function orders(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $perPage = (int) $request->query('per_page', 15);
        $orders = $this->salesOrderService->getList($request->all(), $companyId, $perPage);
        return response()->json(['status' => 'success', 'data' => $orders]);
    }

    public function showOrder(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $order = $this->salesOrderService->getDetails($id, $companyId);
        return response()->json(['status' => 'success', 'data' => $order]);
    }

    public function storeOrder(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $orderData = $request->except('items');
        $orderData['company_id'] = $companyId;
        $orderData['salesperson_id'] = $orderData['salesperson_id'] ?? $request->user()?->id;

        $order = $this->salesOrderService->create($orderData, $request->items, $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Sales Order created successfully', 'data' => $order], 201);
    }

    public function confirmOrder(Request $request, int $id): JsonResponse
    {
        try {
            $order = $this->salesOrderService->confirm($id, $request->user()?->id);
            return response()->json(['status' => 'success', 'message' => 'Sales Order confirmed and stock reserved', 'data' => $order]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function cancelOrder(Request $request, int $id): JsonResponse
    {
        try {
            $order = $this->salesOrderService->cancel($id, $request->user()?->id);
            return response()->json(['status' => 'success', 'message' => 'Sales Order cancelled and stock released', 'data' => $order]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function convertOrderToInvoice(Request $request, int $id): JsonResponse
    {
        try {
            $invoice = $this->salesOrderService->convertToInvoice($id, $request->user()?->id);
            return response()->json(['status' => 'success', 'message' => 'Sales Order converted to Invoice', 'data' => $invoice]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    // INVOICES
    public function invoices(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $perPage = (int) $request->query('per_page', 15);
        $invoices = $this->invoiceService->getList($request->all(), $companyId, $perPage);
        return response()->json(['status' => 'success', 'data' => $invoices]);
    }

    public function showInvoice(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $invoice = $this->invoiceService->getDetails($id, $companyId);
        return response()->json(['status' => 'success', 'data' => $invoice]);
    }

    public function storeInvoice(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $request->except('items');
        $data['company_id'] = $companyId;

        $invoice = $this->invoiceService->create($data, $request->items, $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Invoice created successfully', 'data' => $invoice], 201);
    }

    public function recordInvoicePayment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
        ]);

        try {
            $payment = $this->invoiceService->recordPayment($id, $request->all(), $request->user()?->id);
            return response()->json(['status' => 'success', 'message' => 'Payment recorded successfully', 'data' => $payment]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function recordPayment(Request $request, int $id): JsonResponse
    {
        return $this->recordInvoicePayment($request, $id);
    }

    // RECURRING INVOICES
    public function recurringInvoices(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $perPage = (int) $request->query('per_page', 15);
        $query = RecurringInvoice::query()
            ->where('company_id', $companyId)
            ->with(['customer', 'template']);

        if ($request->search) {
            $s = $request->search;
            $query->whereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$s}%"));
        }
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        return response()->json(['status' => 'success', 'data' => $query->latest()->paginate($perPage)]);
    }

    public function storeRecurringInvoice(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'start_date' => 'required|date',
            'frequency' => 'required|in:weekly,monthly,quarterly,yearly',
            'amount' => 'required|numeric|min:0',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $request->all();
        $data['company_id'] = $companyId;
        $data['created_by'] = $request->user()?->id;
        $data['next_invoice_date'] = $data['start_date'];
        $data['status'] = $data['status'] ?? 'active';

        $rec = RecurringInvoice::create($data);
        return response()->json(['status' => 'success', 'message' => 'Recurring invoice created', 'data' => $rec], 201);
    }

    public function updateRecurringInvoice(Request $request, int $id): JsonResponse
    {
        $rec = RecurringInvoice::findOrFail($id);
        $rec->update($request->all());
        return response()->json(['status' => 'success', 'message' => 'Recurring invoice updated', 'data' => $rec]);
    }

    // INVOICE TEMPLATES
    public function invoiceTemplates(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $templates = InvoiceTemplate::where('company_id', $companyId)->latest()->get();
        return response()->json(['status' => 'success', 'data' => $templates]);
    }

    public function storeInvoiceTemplate(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $request->all();
        $data['company_id'] = $companyId;

        $template = InvoiceTemplate::create($data);
        return response()->json(['status' => 'success', 'message' => 'Invoice template created', 'data' => $template], 201);
    }

    public function updateInvoiceTemplate(Request $request, int $id): JsonResponse
    {
        $template = InvoiceTemplate::findOrFail($id);
        $template->update($request->all());
        return response()->json(['status' => 'success', 'message' => 'Invoice template updated', 'data' => $template]);
    }

    public function destroyInvoiceTemplate(Request $request, int $id): JsonResponse
    {
        $template = InvoiceTemplate::findOrFail($id);
        $template->delete();
        return response()->json(['status' => 'success', 'message' => 'Invoice template deleted']);
    }

    public function invoicePdf(Request $request, int $id): JsonResponse
    {
        $pdfData = $this->invoiceService->getPdfData($id);
        return response()->json(['status' => 'success', 'data' => $pdfData]);
    }

    // QUOTES
    public function quotes(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $perPage = (int) $request->query('per_page', 15);
        $quotes = $this->salesQuoteService->getList($request->all(), $companyId, $perPage);
        return response()->json(['status' => 'success', 'data' => $quotes]);
    }

    public function showQuote(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $quote = $this->salesQuoteService->getDetails($id, $companyId);
        return response()->json(['status' => 'success', 'data' => $quote]);
    }

    public function storeQuote(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'valid_until' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $request->except('items');
        $data['company_id'] = $companyId;
        $data['quote_date'] = $data['quote_date'] ?? date('Y-m-d');

        $quote = $this->salesQuoteService->create($data, $request->items, $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Sales Quote created successfully', 'data' => $quote], 201);
    }

    public function acceptQuote(Request $request, int $id): JsonResponse
    {
        $quote = $this->salesQuoteService->accept($id, $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Quote marked as Accepted', 'data' => $quote]);
    }

    public function convertQuoteToOrder(Request $request, int $id): JsonResponse
    {
        try {
            $warehouseId = $request->warehouse_id ?? 1;
            $order = $this->salesQuoteService->convertToSalesOrder($id, $warehouseId, $request->user()?->id);
            return response()->json(['status' => 'success', 'message' => 'Quote successfully converted to Sales Order', 'data' => $order]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    // CREDIT NOTES
    public function creditNotes(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $perPage = (int) $request->query('per_page', 15);
        $notes = $this->creditNoteService->getList($request->all(), $companyId, $perPage);
        return response()->json(['status' => 'success', 'data' => $notes]);
    }

    public function storeCreditNote(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $request->except('items');
        $data['company_id'] = $companyId;
        $data['date'] = $data['date'] ?? date('Y-m-d');

        $note = $this->creditNoteService->create($data, $request->items, $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Credit Note created successfully', 'data' => $note], 201);
    }

    public function issueCreditNote(Request $request, int $id): JsonResponse
    {
        try {
            $note = $this->creditNoteService->issue($id, $request->user()?->id);
            return response()->json(['status' => 'success', 'message' => 'Credit Note issued successfully', 'data' => $note]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    // CASH SALES
    public function cashSales(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $perPage = (int) $request->query('per_page', 15);
        $sales = $this->cashSaleService->getList($request->all(), $companyId, $perPage);
        return response()->json(['status' => 'success', 'data' => $sales]);
    }

    public function storeCashSale(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'paid_amount' => 'required|numeric|min:0',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $request->except('items');
        $data['company_id'] = $companyId;

        $sale = $this->cashSaleService->processSale($data, $request->items, $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Cash Sale completed successfully', 'data' => $sale], 201);
    }

    // REFUNDS
    public function refunds(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $perPage = (int) $request->query('per_page', 15);
        $refunds = $this->refundService->getList($request->all(), $companyId, $perPage);
        return response()->json(['status' => 'success', 'data' => $refunds]);
    }

    public function storeRefund(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'refund_amount' => 'required|numeric|min:0.01',
            'refund_method' => 'required|string',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $request->all();
        $data['company_id'] = $companyId;
        $data['refund_date'] = $data['refund_date'] ?? date('Y-m-d');

        $refund = $this->refundService->create($data, $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Refund requested successfully', 'data' => $refund], 201);
    }

    public function approveRefund(Request $request, int $id): JsonResponse
    {
        $refund = $this->refundService->approve($id, $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Refund approved', 'data' => $refund]);
    }

    public function processRefund(Request $request, int $id): JsonResponse
    {
        try {
            $refund = $this->refundService->process($id, $request->user()?->id);
            return response()->json(['status' => 'success', 'message' => 'Refund processed successfully', 'data' => $refund]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    // DELIVERY NOTES
    public function deliveryNotes(Request $request): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $perPage = (int) $request->query('per_page', 15);
        $notes = $this->deliveryService->getList($request->all(), $companyId, $perPage);
        return response()->json(['status' => 'success', 'data' => $notes]);
    }

    public function showDeliveryNote(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : null;
        $note = $this->deliveryService->getDetails($id, $companyId);
        return response()->json(['status' => 'success', 'data' => $note]);
    }

    public function storeDeliveryNote(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'delivery_address' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.delivered_quantity' => 'required|numeric|min:1',
        ]);

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
        $data = $request->except('items');
        $data['company_id'] = $companyId;
        $data['delivery_date'] = $data['delivery_date'] ?? date('Y-m-d');

        $note = $this->deliveryService->create($data, $request->items, $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Delivery Note created successfully', 'data' => $note], 201);
    }

    public function dispatchDeliveryNote(Request $request, int $id): JsonResponse
    {
        $note = $this->deliveryService->dispatch($id, $request->user()?->id);
        return response()->json(['status' => 'success', 'message' => 'Delivery dispatched', 'data' => $note]);
    }

    public function deliverDeliveryNote(Request $request, int $id): JsonResponse
    {
        try {
            $note = $this->deliveryService->deliver($id, $request->user()?->id);
            return response()->json(['status' => 'success', 'message' => 'Delivery marked as Delivered and stock deducted', 'data' => $note]);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }
}

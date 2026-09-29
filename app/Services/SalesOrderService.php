<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function getList(array $filters = [], ?int $companyId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = SalesOrder::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['customer', 'warehouse', 'salesperson', 'items.product']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('order_date', [$filters['start_date'], $filters['end_date']]);
        }

        return $query->latest('order_date')->paginate($perPage);
    }

    public function getDetails(int $id, ?int $companyId = null): SalesOrder
    {
        $query = SalesOrder::query()
            ->with([
                'customer',
                'warehouse',
                'salesperson',
                'creator',
                'items.product',
                'items.unit',
                'invoices',
                'deliveryNotes.items.product',
            ]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->findOrFail($id);
    }

    public function create(array $orderData, array $itemsData, ?int $userId = null): SalesOrder
    {
        return DB::transaction(function () use ($orderData, $itemsData, $userId) {
            if (empty($orderData['order_number'])) {
                $count = SalesOrder::where('company_id', $orderData['company_id'] ?? 1)->count() + 1;
                $orderData['order_number'] = 'SO-' . date('Y') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
            }

            $orderData['created_by'] = $userId;
            $orderData['status'] = $orderData['status'] ?? 'draft';
            $orderData['payment_status'] = $orderData['payment_status'] ?? 'unpaid';

            // Calculate totals
            $subtotal = 0;
            $totalTax = 0;
            $totalDiscount = 0;

            foreach ($itemsData as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $price = (float) ($item['unit_price'] ?? 0);
                $disc = (float) ($item['discount'] ?? 0);
                $tax = (float) ($item['tax'] ?? 0);

                $lineSubtotal = ($qty * $price) - $disc + $tax;
                $subtotal += ($qty * $price);
                $totalDiscount += $disc;
                $totalTax += $tax;
            }

            $shipping = (float) ($orderData['shipping'] ?? 0);
            $grandTotal = $subtotal - $totalDiscount + $totalTax + $shipping;

            $orderData['subtotal'] = $subtotal;
            $orderData['discount'] = $totalDiscount;
            $orderData['tax'] = $totalTax;
            $orderData['shipping'] = $shipping;
            $orderData['total'] = $grandTotal;
            $orderData['grand_total'] = $grandTotal;
            $orderData['paid_amount'] = 0;
            $orderData['due_amount'] = $grandTotal;

            $salesOrder = SalesOrder::create($orderData);

            foreach ($itemsData as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $price = (float) ($item['unit_price'] ?? 0);
                $disc = (float) ($item['discount'] ?? 0);
                $tax = (float) ($item['tax'] ?? 0);
                $lineSubtotal = ($qty * $price) - $disc + $tax;

                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'description' => $item['description'] ?? null,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'discount' => $disc,
                    'tax' => $tax,
                    'subtotal' => $lineSubtotal,
                    'total' => $lineSubtotal,
                ]);
            }

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $salesOrder->company_id,
                'action' => 'sales_order.created',
                'module' => 'sales',
                'record_id' => $salesOrder->id,
                'new_values' => ['order_number' => $salesOrder->order_number, 'total' => $grandTotal],
            ]);

            return $salesOrder->load(['customer', 'items.product']);
        });
    }

    /**
     * Confirm Sales Order: Reserves inventory in the warehouse.
     */
    public function confirm(int $id, ?int $userId = null): SalesOrder
    {
        return DB::transaction(function () use ($id, $userId) {
            $salesOrder = SalesOrder::with(['items', 'warehouse'])->findOrFail($id);

            if ($salesOrder->status !== 'draft' && $salesOrder->status !== 'pending') {
                throw new Exception("Only draft or pending orders can be confirmed.");
            }

            if (!$salesOrder->warehouse_id) {
                throw new Exception("Please assign a warehouse to reserve inventory for this sales order.");
            }

            // Reserve inventory for each item
            foreach ($salesOrder->items as $item) {
                $this->stockService->reserveStock(
                    $item->product_id,
                    $salesOrder->warehouse_id,
                    $item->quantity
                );
            }

            $salesOrder->status = 'confirmed';
            $salesOrder->save();

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $salesOrder->company_id,
                'action' => 'sales_order.confirmed',
                'module' => 'sales',
                'record_id' => $salesOrder->id,
                'new_values' => ['status' => 'confirmed', 'stock_reserved' => true],
            ]);

            return $salesOrder;
        });
    }

    /**
     * Cancel Sales Order: Releases reserved inventory if order was confirmed.
     */
    public function cancel(int $id, ?int $userId = null): SalesOrder
    {
        return DB::transaction(function () use ($id, $userId) {
            $salesOrder = SalesOrder::with('items')->findOrFail($id);

            if ($salesOrder->status === 'delivered' || $salesOrder->status === 'completed') {
                throw new Exception("Delivered orders cannot be cancelled directly. Use refunds or credit notes.");
            }

            // If it was confirmed/processing, release reserved stock
            if (in_array($salesOrder->status, ['confirmed', 'processing', 'partially_delivered']) && $salesOrder->warehouse_id) {
                foreach ($salesOrder->items as $item) {
                    $this->stockService->releaseStock(
                        $item->product_id,
                        $salesOrder->warehouse_id,
                        $item->quantity
                    );
                }
            }

            $salesOrder->status = 'cancelled';
            $salesOrder->save();

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $salesOrder->company_id,
                'action' => 'sales_order.cancelled',
                'module' => 'sales',
                'record_id' => $salesOrder->id,
            ]);

            return $salesOrder;
        });
    }

    /**
     * Convert Sales Order directly into a full Invoice.
     */
    public function convertToInvoice(int $id, ?int $userId = null): Invoice
    {
        return DB::transaction(function () use ($id, $userId) {
            $salesOrder = SalesOrder::with('items')->findOrFail($id);

            $count = Invoice::where('company_id', $salesOrder->company_id)->count() + 1;
            $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);

            $invoice = Invoice::create([
                'company_id' => $salesOrder->company_id,
                'customer_id' => $salesOrder->customer_id,
                'sales_order_id' => $salesOrder->id,
                'warehouse_id' => $salesOrder->warehouse_id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => Carbon::now()->toDateString(),
                'issue_date' => Carbon::now()->toDateString(),
                'due_date' => Carbon::now()->addDays(30)->toDateString(),
                'subtotal' => $salesOrder->subtotal,
                'discount' => $salesOrder->discount,
                'tax' => $salesOrder->tax,
                'shipping' => $salesOrder->shipping,
                'round_off' => 0,
                'total' => $salesOrder->grand_total,
                'grand_total' => $salesOrder->grand_total,
                'amount_paid' => 0,
                'paid_amount' => 0,
                'due_amount' => $salesOrder->grand_total,
                'payment_status' => 'unpaid',
                'status' => 'sent',
                'notes' => "Generated from Sales Order #{$salesOrder->order_number}",
                'created_by' => $userId,
            ]);

            foreach ($salesOrder->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'unit_id' => $item->unit_id,
                    'description' => $item->description ?? ($item->product?->name ?? 'Sales Order Item #' . $item->product_id),
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'tax' => $item->tax,
                    'subtotal' => $item->subtotal,
                    'total' => $item->total ?? $item->subtotal,
                ]);
            }

            // Update customer balance (receivable)
            $customer = $salesOrder->customer;
            if ($customer) {
                $customer->increment('balance', $invoice->grand_total);
            }

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $salesOrder->company_id,
                'action' => 'sales_order.converted_to_invoice',
                'module' => 'sales',
                'record_id' => $salesOrder->id,
                'new_values' => ['invoice_id' => $invoice->id, 'invoice_number' => $invoiceNumber],
            ]);

            return $invoice->load(['customer', 'items.product']);
        });
    }
}

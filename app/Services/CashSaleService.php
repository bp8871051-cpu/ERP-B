<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CashSale;
use App\Models\CashSaleItem;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CashSaleService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function getList(array $filters = [], ?int $companyId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = CashSale::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['customer', 'warehouse', 'items.product', 'invoice']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('sale_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['payment_method']) && $filters['payment_method'] !== 'all') {
            $query->where('payment_method', $filters['payment_method']);
        }

        return $query->latest('sale_date')->paginate($perPage);
    }

    /**
     * Complete POS-Style quick Cash Sale in a single atomic DB transaction.
     */
    public function processSale(array $data, array $items, ?int $userId = null): CashSale
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            $warehouseId = $data['warehouse_id'] ?? 1;
            $companyId = $data['company_id'] ?? 1;

            $subtotal = 0;
            $discount = 0;
            $tax = 0;

            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $price = (float) ($item['unit_price'] ?? 0);
                $disc = (float) ($item['discount'] ?? 0);
                $tx = (float) ($item['tax'] ?? 0);

                $subtotal += ($qty * $price);
                $discount += $disc;
                $tax += $tx;
            }

            $grandTotal = $subtotal - $discount + $tax;
            $paidAmount = (float) ($data['paid_amount'] ?? $grandTotal);
            $changeAmount = max(0, $paidAmount - $grandTotal);

            $count = CashSale::where('company_id', $companyId)->count() + 1;
            $saleNumber = 'CS-' . date('Y') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);

            // 1. Create CashSale
            $cashSale = CashSale::create([
                'company_id' => $companyId,
                'customer_id' => $data['customer_id'] ?? null,
                'warehouse_id' => $warehouseId,
                'sale_number' => $saleNumber,
                'sale_date' => Carbon::now()->toDateString(),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'grand_total' => $grandTotal,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'status' => 'completed',
                'created_by' => $userId,
            ]);

            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $price = (float) ($item['unit_price'] ?? 0);
                $disc = (float) ($item['discount'] ?? 0);
                $tx = (float) ($item['tax'] ?? 0);
                $lineSubtotal = ($qty * $price) - $disc + $tx;

                CashSaleItem::create([
                    'cash_sale_id' => $cashSale->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'discount' => $disc,
                    'tax' => $tx,
                    'subtotal' => $lineSubtotal,
                ]);

                // 2. Deduct stock immediately & create movement
                $this->stockService->decreaseStock(
                    $item['product_id'],
                    $warehouseId,
                    $qty,
                    $saleNumber,
                    'sale',
                    "Cash Sale #{$saleNumber}",
                    $userId,
                    $companyId
                );
            }

            // 3. Create paid invoice for customer/accounting
            if ($cashSale->customer_id) {
                $invCount = Invoice::where('company_id', $companyId)->count() + 1;
                $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad((string) $invCount, 5, '0', STR_PAD_LEFT);

                $invoice = Invoice::create([
                    'company_id' => $companyId,
                    'customer_id' => $cashSale->customer_id,
                    'warehouse_id' => $warehouseId,
                    'invoice_number' => $invoiceNumber,
                    'invoice_date' => Carbon::now()->toDateString(),
                    'issue_date' => Carbon::now()->toDateString(),
                    'due_date' => Carbon::now()->toDateString(),
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax' => $tax,
                    'shipping' => 0,
                    'round_off' => 0,
                    'total' => $grandTotal,
                    'grand_total' => $grandTotal,
                    'amount_paid' => $grandTotal,
                    'paid_amount' => $grandTotal,
                    'due_amount' => 0,
                    'payment_status' => 'paid',
                    'status' => 'paid',
                    'notes' => "Quick Cash Sale #{$saleNumber}",
                    'created_by' => $userId,
                ]);

                $cashSale->invoice_id = $invoice->id;
                $cashSale->save();
            }

            // 4. Create financial transaction
            Transaction::create([
                'account_id' => 1,
                'type' => 'income',
                'amount' => $grandTotal,
                'category' => 'Direct Cash Sale',
                'reference' => $saleNumber,
                'description' => "Cash Sale #{$saleNumber} paid via " . strtoupper($cashSale->payment_method),
                'transaction_date' => Carbon::now()->toDateString(),
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $companyId,
                'action' => 'cash_sale.completed',
                'module' => 'sales',
                'record_id' => $cashSale->id,
                'new_values' => ['sale_number' => $saleNumber, 'grand_total' => $grandTotal],
            ]);

            return $cashSale->load(['customer', 'items.product', 'warehouse']);
        });
    }
}

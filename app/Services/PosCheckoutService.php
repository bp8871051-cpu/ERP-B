<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosPayment;
use App\Models\PosPaymentTransaction;
use App\Models\PosReceipt;
use App\Models\PosRegisterSession;
use App\Models\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosCheckoutService
{
    protected StockService $stockService;
    protected PosReceiptService $receiptService;

    public function __construct(StockService $stockService, PosReceiptService $receiptService)
    {
        $this->stockService = $stockService;
        $this->receiptService = $receiptService;
    }

    /**
     * Calculate cart totals securely on the backend.
     */
    public function calculate(array $items, float $orderDiscount = 0, ?int $companyId = null): array
    {
        $subtotal = 0;
        $totalItemDiscount = 0;
        $totalTax = 0;
        $processedItems = [];

        foreach ($items as $item) {
            $productId = (int) $item['product_id'];
            $qty = max(1, (int) $item['quantity']);
            $product = Product::find($productId);

            if (!$product) {
                throw new Exception("Product #{$productId} not found.");
            }

            // Always take unit price from product unless an explicit verified discount/price is given
            $unitPrice = (float) $product->selling_price;
            $lineSubtotal = round($unitPrice * $qty, 2);

            $itemDiscount = isset($item['discount']) ? min($lineSubtotal, (float) $item['discount']) : 0;
            $taxRate = (float) ($product->tax_rate ?? 0);
            $taxable = $lineSubtotal - $itemDiscount;
            $lineTax = round(($taxable * $taxRate) / 100, 2);
            $lineTotal = round($taxable + $lineTax, 2);

            $subtotal += $lineSubtotal;
            $totalItemDiscount += $itemDiscount;
            $totalTax += $lineTax;

            $processedItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'discount_amount' => $itemDiscount,
                'tax_amount' => $lineTax,
                'subtotal' => $lineSubtotal,
                'total_price' => $lineTotal,
                'available_stock' => $product->available_stock,
            ];
        }

        $totalDiscount = $totalItemDiscount + max(0, $orderDiscount);
        $taxableTotal = max(0, $subtotal - $totalDiscount);
        $rawGrandTotal = $taxableTotal + $totalTax;

        // Round off to nearest whole or 2 decimal places
        $roundOff = round(round($rawGrandTotal) - $rawGrandTotal, 2);
        $finalAmount = round($rawGrandTotal + $roundOff, 2);

        return [
            'items' => $processedItems,
            'subtotal' => round($subtotal, 2),
            'discount' => round($totalDiscount, 2),
            'tax' => round($totalTax, 2),
            'grand_total' => $finalAmount,
            'round_off' => $roundOff,
            'final_amount' => $finalAmount,
        ];
    }

    /**
     * Execute atomic checkout transaction.
     */
    public function checkout(array $data, int $userId, int $companyId): array
    {
        return DB::transaction(function () use ($data, $userId, $companyId) {
            $itemsData = $data['items'] ?? [];
            if (empty($itemsData)) {
                throw new Exception("Cart is empty. Please add products to checkout.");
            }

            // 1. Calculate backend prices & verify stock
            $orderDiscount = (float) ($data['discount'] ?? 0);
            $calculation = $this->calculate($itemsData, $orderDiscount, $companyId);
            $warehouseId = (int) ($data['warehouse_id'] ?? 1);

            // Verify stock for all items
            foreach ($calculation['items'] as $item) {
                $product = Product::find($item['product_id']);
                if ($product && $product->track_inventory) {
                    if ($product->available_stock < $item['quantity']) {
                        throw new Exception("Product '{$product->name}' has insufficient stock (Available: {$product->available_stock}, Requested: {$item['quantity']}).");
                    }
                }
            }

            // 2. Identify or create customer
            $customerId = $data['customer_id'] ?? null;
            if (!$customerId && !empty($data['customer_phone'])) {
                // Find by phone or create walk-in customer
                $customer = Customer::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'phone' => $data['customer_phone'],
                    ],
                    [
                        'name' => $data['customer_name'] ?: 'Walk-in Customer (' . $data['customer_phone'] . ')',
                        'email' => $data['customer_email'] ?? null,
                        'customer_code' => 'CUST-' . strtoupper(Str::random(6)),
                    ]
                );
                $customerId = $customer->id;
            } elseif (!$customerId) {
                // Find or create default walk-in customer
                $customer = Customer::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'name' => 'Walk-in Customer',
                    ],
                    [
                        'customer_code' => 'WALK-IN',
                        'phone' => '0000000000',
                    ]
                );
                $customerId = $customer->id;
            }

            // 3. Validate payments (Cash, Card, UPI, Mixed, etc.)
            $payments = $data['payments'] ?? [];
            if (empty($payments) && isset($data['payment_method'])) {
                $payments = [
                    [
                        'payment_method' => $data['payment_method'],
                        'amount' => $calculation['final_amount'],
                        'reference_no' => $data['reference_no'] ?? null,
                    ]
                ];
            }

            $totalPaid = 0;
            foreach ($payments as $p) {
                $totalPaid += (float) ($p['amount'] ?? 0);
            }

            if ($totalPaid < $calculation['final_amount'] && ($data['allow_partial'] ?? false) === false) {
                $diff = round($calculation['final_amount'] - $totalPaid, 2);
                throw new Exception("Total payment amount (₹{$totalPaid}) is less than grand total (₹{$calculation['final_amount']}). Short by ₹{$diff}.");
            }

            $changeAmount = max(0, round($totalPaid - $calculation['final_amount'], 2));

            // 4. Generate POS Order Number
            $year = Carbon::now()->format('Y');
            $latestOrder = PosOrder::where('company_id', $companyId)->latest('id')->first();
            $nextSeq = $latestOrder ? ($latestOrder->id + 1) : 1001;
            $orderNumber = sprintf('POS-%s-%06d', $year, $nextSeq);

            // 5. Active register session
            $activeSession = PosRegisterSession::where('company_id', $companyId)
                ->where('status', 'open')
                ->latest()
                ->first();

            // 6. Create POS Order record
            $posOrder = PosOrder::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'register_session_id' => $activeSession?->id,
                'cashier_id' => $userId,
                'customer_id' => $customerId,
                'order_number' => $orderNumber,
                'subtotal' => $calculation['subtotal'],
                'discount_amount' => $calculation['discount'],
                'round_off' => $calculation['round_off'],
                'tax_amount' => $calculation['tax'],
                'total_amount' => $calculation['final_amount'],
                'paid_amount' => $totalPaid,
                'change_amount' => $changeAmount,
                'payment_method' => count($payments) > 1 ? 'mixed' : ($payments[0]['payment_method'] ?? 'cash'),
                'payment_status' => $totalPaid >= $calculation['final_amount'] ? 'paid' : 'partially_paid',
                'order_status' => 'completed',
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            // 7. Save Order Items
            foreach ($calculation['items'] as $item) {
                PosOrderItem::create([
                    'pos_order_id' => $posOrder->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'sku' => $item['sku'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_amount' => $item['discount_amount'],
                    'tax_amount' => $item['tax_amount'],
                    'subtotal' => $item['subtotal'],
                    'total_price' => $item['total_price'],
                ]);

                // 8. Deduct Inventory via StockService
                $this->stockService->decreaseStock(
                    $item['product_id'],
                    $warehouseId,
                    $item['quantity'],
                    $orderNumber,
                    'pos_sale',
                    "POS Order {$orderNumber} sale to Customer #{$customerId}",
                    $userId,
                    $companyId,
                    false
                );
            }

            // 9. Record POS Payment tenders
            foreach ($payments as $payment) {
                PosPayment::create([
                    'company_id' => $companyId,
                    'pos_order_id' => $posOrder->id,
                    'payment_method' => $payment['payment_method'] ?? 'cash',
                    'amount' => (float) ($payment['amount'] ?? 0),
                    'currency' => 'INR',
                    'reference_no' => $payment['reference_no'] ?? ('REF-' . strtoupper(Str::random(8))),
                    'card_last4' => $payment['card_last4'] ?? null,
                    'card_type' => $payment['card_type'] ?? null,
                    'upi_id' => $payment['upi_id'] ?? null,
                    'status' => 'completed',
                    'notes' => $payment['notes'] ?? null,
                ]);

                // Also record in pos_payment_transactions for audit
                PosPaymentTransaction::create([
                    'company_id' => $companyId,
                    'pos_order_id' => $posOrder->id,
                    'gateway' => 'pos_terminal',
                    'transaction_id' => $payment['reference_no'] ?? ('TXN-' . strtoupper(Str::random(10))),
                    'amount' => (float) ($payment['amount'] ?? 0),
                    'status' => 'success',
                    'payload' => $payment,
                ]);
            }

            // 10. Automatically create ERP Sales Invoice for accounting integration
            $invoiceNumber = 'INV-' . Carbon::now()->format('Y') . '-' . sprintf('%06d', $nextSeq);
            $invoice = Invoice::create([
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'warehouse_id' => $warehouseId,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => Carbon::today(),
                'issue_date' => Carbon::today(),
                'due_date' => Carbon::today(),
                'subtotal' => $calculation['subtotal'],
                'discount' => $calculation['discount'],
                'tax' => $calculation['tax'],
                'round_off' => $calculation['round_off'],
                'total' => $calculation['final_amount'],
                'grand_total' => $calculation['final_amount'],
                'paid_amount' => $totalPaid,
                'amount_paid' => $totalPaid,
                'due_amount' => max(0, $calculation['final_amount'] - $totalPaid),
                'payment_status' => $totalPaid >= $calculation['final_amount'] ? 'paid' : 'partially_paid',
                'status' => 'paid',
                'notes' => "Auto-generated from POS Order {$orderNumber}",
                'created_by' => $userId,
            ]);

            foreach ($calculation['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount_amount'],
                    'tax' => $item['tax_amount'],
                    'total' => $item['total_price'],
                ]);
            }

            // Link invoice to POS order
            $posOrder->update(['invoice_id' => $invoice->id]);

            // Update session sales if register is open
            if ($activeSession) {
                $activeSession->increment('total_sales', $calculation['final_amount']);
                $activeSession->increment('total_transactions', 1);
            }

            // 11. Generate Receipt
            $receiptData = $this->receiptService->generateReceiptData($posOrder);
            $receipt = PosReceipt::create([
                'company_id' => $companyId,
                'pos_order_id' => $posOrder->id,
                'receipt_number' => 'RCP-' . $posOrder->order_number,
                'receipt_payload' => $receiptData,
                'print_count' => 1,
            ]);

            return [
                'order' => $posOrder->load(['items.product', 'payments', 'customer', 'cashier', 'receipt']),
                'invoice' => $invoice,
                'receipt' => $receiptData,
            ];
        });
    }
}

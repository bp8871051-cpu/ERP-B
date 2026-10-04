<?php

namespace App\Services;

use App\Models\PosOrder;
use App\Models\PosOrderItem;
use App\Models\PosRefund;
use App\Models\PosRefundItem;
use App\Models\PosTransaction;
use App\Models\Product;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosRefundService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Process POS Refund (Full, Partial, or Product-level).
     */
    public function processRefund(int $orderId, array $data, int $userId, int $companyId): array
    {
        return DB::transaction(function () use ($orderId, $data, $userId, $companyId) {
            $order = PosOrder::with('items')->where('company_id', $companyId)->findOrFail($orderId);

            if ($order->order_status === 'refunded') {
                throw new Exception("This order has already been fully refunded.");
            }

            $refundType = $data['refund_type'] ?? 'full'; // full, partial, product
            $reason = $data['reason'] ?? 'Customer return';
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $itemsToRefund = $data['items'] ?? [];

            $totalRefundAmount = 0;
            $refundSubtotal = 0;
            $refundTax = 0;
            $createdRefundItems = [];

            if ($refundType === 'full' || empty($itemsToRefund)) {
                $totalRefundAmount = (float) $order->total_amount;
                $refundSubtotal = (float) $order->subtotal;
                $refundTax = (float) $order->tax_amount;

                // Restock all items
                foreach ($order->items as $item) {
                    $itemsToRefund[] = [
                        'order_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'refund_amount' => $item->total_price,
                        'restock' => true,
                    ];
                }
            } else {
                // Product-level / Partial refund calculation
                foreach ($itemsToRefund as $refItem) {
                    $orderItem = PosOrderItem::where('pos_order_id', $order->id)
                        ->findOrFail($refItem['order_item_id']);

                    $qty = min($orderItem->quantity, max(1, (int) $refItem['quantity']));
                    $lineRefund = round($qty * (float) $orderItem->unit_price, 2);
                    $totalRefundAmount += $lineRefund;
                    $refundSubtotal += $lineRefund;

                    $itemsToRefundProcessed[] = [
                        'order_item_id' => $orderItem->id,
                        'product_id' => $orderItem->product_id,
                        'quantity' => $qty,
                        'unit_price' => (float) $orderItem->unit_price,
                        'refund_amount' => $lineRefund,
                        'restock' => $refItem['restock'] ?? true,
                    ];
                }
                $itemsToRefund = $itemsToRefundProcessed;
            }

            // Generate Refund Number
            $year = Carbon::now()->format('Y');
            $refundCount = PosRefund::where('company_id', $companyId)->count() + 1;
            $refundNumber = sprintf('REF-%s-%06d', $year, $refundCount);

            // Create POS Refund
            $refund = PosRefund::create([
                'company_id' => $companyId,
                'pos_order_id' => $order->id,
                'customer_id' => $order->customer_id,
                'processed_by' => $userId,
                'refund_number' => $refundNumber,
                'refund_amount' => $totalRefundAmount,
                'refund_method' => $paymentMethod,
                'reason' => $reason,
                'status' => 'completed',
            ]);

            // Save refund items & restock inventory
            foreach ($itemsToRefund as $item) {
                PosRefundItem::create([
                    'pos_refund_id' => $refund->id,
                    'pos_order_item_id' => $item['order_item_id'],
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'refund_amount' => $item['refund_amount'],
                ]);

                if ($item['restock'] ?? true) {
                    $this->stockService->increaseStock(
                        $item['product_id'],
                        $order->warehouse_id ?: 1,
                        $item['quantity'],
                        $item['unit_price'],
                        $refundNumber,
                        'pos_return',
                        "Restock from POS Refund {$refundNumber} for Order {$order->order_number}",
                        $userId,
                        $companyId
                    );
                }
            }

            // Create refund transaction
            PosTransaction::create([
                'pos_order_id' => $order->id,
                'amount' => -$totalRefundAmount,
                'payment_type' => $paymentMethod,
                'reference_no' => 'REFUND-' . $refundNumber,
            ]);

            // Update order status
            $totalRefunded = (float) PosRefund::where('pos_order_id', $order->id)->sum('refund_amount');
            if ($totalRefunded >= (float) $order->total_amount) {
                $order->update([
                    'order_status' => 'refunded',
                    'payment_status' => 'refunded',
                ]);
            } else {
                $order->update([
                    'order_status' => 'partially_refunded',
                    'payment_status' => 'partially_paid',
                ]);
            }

            return [
                'refund' => $refund->load('items.product'),
                'order' => $order->fresh(['refunds.items', 'items']),
            ];
        });
    }
}

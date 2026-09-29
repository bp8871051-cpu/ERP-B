<?php

namespace App\Services;

use App\Models\CrmAuditLog;
use App\Models\CrmDeal;
use App\Models\CrmDealItem;
use App\Models\CrmDealStageHistory;
use App\Models\CrmPipelineStage;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DealService
{
    /**
     * Create a new deal and calculate items
     */
    public function createDeal(array $data, ?int $userId = null): CrmDeal
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        if (!empty($data['items'])) {
            unset($data['items']);
        }

        $deal = CrmDeal::create(array_merge($data, [
            'owner_id' => $data['owner_id'] ?? $userId,
        ]));

        return $this->calculateAndSaveDeal($deal, $items, $data);
    }

    /**
     * Calculate and save deal items and overall deal value + expected revenue
     */
    public function calculateAndSaveDeal(CrmDeal $deal, array $items = [], ?array $dealAttributes = []): CrmDeal
    {
        return DB::transaction(function () use ($deal, $items, $dealAttributes) {
            $totalValue = 0.0;

            if (!empty($items)) {
                // Delete existing items and recalculate
                $deal->items()->delete();

                foreach ($items as $item) {
                    $qty = max(0.01, (float) ($item['quantity'] ?? 1));
                    $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));

                    $discountPercent = (float) ($item['discount_percentage'] ?? 0);
                    $discount = $discountPercent > 0
                        ? round(($qty * $unitPrice * $discountPercent) / 100, 2)
                        : max(0, (float) ($item['discount'] ?? 0));

                    $taxRate = max(0, (float) ($item['tax_rate'] ?? $item['tax_percentage'] ?? 0));

                    $subtotal = ($qty * $unitPrice) - $discount;
                    $taxAmount = round(($subtotal * $taxRate) / 100, 2);
                    $lineTotal = round($subtotal + $taxAmount, 2);

                    $totalValue += $lineTotal;

                    // Fetch product name if available
                    $productName = $item['product_name'] ?? 'Custom Product / Service';
                    if (!empty($item['product_id'])) {
                        $product = Product::find($item['product_id']);
                        if ($product) {
                            $productName = $product->name;
                        }
                    }

                    CrmDealItem::create([
                        'deal_id' => $deal->id,
                        'product_id' => $item['product_id'] ?? null,
                        'product_name' => $productName,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'discount' => $discount,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $taxAmount,
                        'total' => $lineTotal,
                    ]);
                }
            } else {
                $totalValue = (float) ($dealAttributes['value'] ?? $deal->value ?? 0);
            }

            // Probability from stage if stage given
            $stage = null;
            if (!empty($dealAttributes['stage_id'])) {
                $stage = CrmPipelineStage::find($dealAttributes['stage_id']);
            } elseif ($deal->stage_id) {
                $stage = CrmPipelineStage::find($deal->stage_id);
            }

            $probability = isset($dealAttributes['probability'])
                ? (int) $dealAttributes['probability']
                : ($stage ? (int) $stage->probability : $deal->probability);

            $expectedRevenue = round(($totalValue * $probability) / 100, 2);

            $updateData = array_merge($dealAttributes, [
                'value' => $totalValue,
                'probability' => $probability,
                'expected_revenue' => $expectedRevenue,
            ]);

            // Handle Won / Lost status flags
            if ($stage) {
                if ($stage->is_won) {
                    $updateData['status'] = 'won';
                    $updateData['won_at'] = now();
                    $updateData['lost_at'] = null;
                } elseif ($stage->is_lost) {
                    $updateData['status'] = 'lost';
                    $updateData['lost_at'] = now();
                } else {
                    $updateData['status'] = 'open';
                }
            }

            $deal->update($updateData);

            return $deal->fresh(['items', 'stage', 'customer', 'contact', 'owner', 'stageHistory']);
        });
    }

    /**
     * Transition deal to a new stage (e.g. from Kanban drag-and-drop or details page)
     */
    public function updateStage(CrmDeal $deal, int $newStageId, mixed $userIdOrNotes = null, ?string $notes = null): CrmDeal
    {
        $userId = is_numeric($userIdOrNotes) ? (int) $userIdOrNotes : null;
        if (is_string($userIdOrNotes) && $notes === null) {
            $notes = $userIdOrNotes;
        }

        $newStage = CrmPipelineStage::findOrFail($newStageId);
        $oldStageId = $deal->stage_id;

        if ($oldStageId === $newStageId) {
            return $deal;
        }

        return DB::transaction(function () use ($deal, $newStage, $oldStageId, $userId, $notes) {
            $oldValues = [
                'stage_id' => $oldStageId,
                'status' => $deal->status,
                'probability' => $deal->probability,
            ];

            $probability = (int) $newStage->probability;
            $expectedRevenue = round(($deal->value * $probability) / 100, 2);

            $status = 'open';
            $wonAt = $deal->won_at;
            $lostAt = $deal->lost_at;

            if ($newStage->is_won) {
                $status = 'won';
                $wonAt = now();
                $lostAt = null;
            } elseif ($newStage->is_lost) {
                $status = 'lost';
                $lostAt = now();
            }

            $deal->update([
                'stage_id' => $newStage->id,
                'probability' => $probability,
                'expected_revenue' => $expectedRevenue,
                'status' => $status,
                'won_at' => $wonAt,
                'lost_at' => $lostAt,
            ]);

            CrmDealStageHistory::create([
                'deal_id' => $deal->id,
                'from_stage_id' => $oldStageId,
                'to_stage_id' => $newStage->id,
                'changed_by' => $userId,
                'notes' => $notes ?? "Moved stage to {$newStage->name}",
            ]);

            CrmAuditLog::log(
                $newStage->is_won ? 'Deal Won' : ($newStage->is_lost ? 'Deal Lost' : 'Pipeline Stage Changed'),
                CrmDeal::class,
                $deal->id,
                $oldValues,
                [
                    'stage_id' => $newStage->id,
                    'stage_name' => $newStage->name,
                    'status' => $status,
                    'probability' => $probability,
                ],
                $deal->company_id,
                $userId
            );

            return $deal->fresh(['stage', 'customer', 'contact', 'items', 'stageHistory']);
        });
    }

    /**
     * Convert Won Deal to existing Sales Order module seamlessly
     */
    public function createSalesOrderFromDeal(CrmDeal $deal, ?int $userId = null): SalesOrder
    {
        if ($deal->sales_order_id) {
            $existingOrder = SalesOrder::find($deal->sales_order_id);
            if ($existingOrder) {
                return $existingOrder;
            }
        }

        if (!$deal->customer_id) {
            throw ValidationException::withMessages([
                'customer' => ['Deal must be associated with an existing Customer to generate a Sales Order.'],
            ]);
        }

        return DB::transaction(function () use ($deal, $userId) {
            $orderNumber = 'SO-' . strtoupper(uniqid());
            $items = $deal->items()->get();

            $subtotal = 0.0;
            $totalTax = 0.0;
            $totalDiscount = 0.0;

            foreach ($items as $item) {
                $lineSub = ($item->quantity * $item->unit_price);
                $subtotal += $lineSub;
                $totalTax += $item->tax_amount;
                $totalDiscount += $item->discount;
            }

            if ($items->isEmpty()) {
                $subtotal = (float) $deal->value;
            }

            $grandTotal = round($subtotal - $totalDiscount + $totalTax, 2);

            $salesOrder = SalesOrder::create([
                'company_id' => $deal->company_id,
                'customer_id' => $deal->customer_id,
                'order_number' => $orderNumber,
                'order_date' => now()->toDateString(),
                'expected_delivery_date' => $deal->expected_close_date ?? now()->addDays(14)->toDateString(),
                'subtotal' => $subtotal,
                'tax' => $totalTax,
                'discount' => $totalDiscount,
                'shipping' => 0.0,
                'total' => $grandTotal,
                'grand_total' => $grandTotal,
                'paid_amount' => 0.0,
                'due_amount' => $grandTotal,
                'payment_status' => 'unpaid',
                'status' => 'draft',
                'notes' => "Generated from CRM Deal #{$deal->id} ({$deal->name})",
                'created_by' => $userId,
            ]);

            // Copy items to SalesOrderItem
            foreach ($items as $item) {
                $productId = $item->product_id;
                if (!$productId) {
                    $existingProduct = Product::where('company_id', $deal->company_id)->first() ?? Product::first();
                    $productId = $existingProduct?->id;
                }

                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $productId,
                    'description' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'tax' => $item->tax_amount,
                    'subtotal' => round($item->quantity * $item->unit_price, 2),
                    'total' => $item->total,
                ]);
            }

            // Link sales order back to deal
            $deal->update([
                'sales_order_id' => $salesOrder->id,
                'status' => 'won',
                'won_at' => $deal->won_at ?? now(),
            ]);

            CrmAuditLog::log(
                'Sales Order Created from Deal',
                CrmDeal::class,
                $deal->id,
                null,
                ['sales_order_id' => $salesOrder->id, 'order_number' => $orderNumber],
                $deal->company_id,
                $userId
            );

            return $salesOrder;
        });
    }
}

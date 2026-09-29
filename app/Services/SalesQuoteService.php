<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesQuote;
use App\Models\SalesQuoteItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SalesQuoteService
{
    public function getList(array $filters = [], ?int $companyId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = SalesQuote::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['customer', 'salesperson', 'items.product']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('quote_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        return $query->latest('quote_date')->paginate($perPage);
    }

    public function getDetails(int $id, ?int $companyId = null): SalesQuote
    {
        $query = SalesQuote::query()->with(['customer', 'salesperson', 'creator', 'items.product', 'items.unit']);
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        return $query->findOrFail($id);
    }

    public function create(array $data, array $items, ?int $userId = null): SalesQuote
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            if (empty($data['quote_number'])) {
                $count = SalesQuote::where('company_id', $data['company_id'] ?? 1)->count() + 1;
                $data['quote_number'] = 'QT-' . date('Y') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
            }

            $subtotal = 0;
            $totalTax = 0;
            $totalDiscount = 0;

            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $price = (float) ($item['unit_price'] ?? 0);
                $disc = (float) ($item['discount'] ?? 0);
                $tax = (float) ($item['tax'] ?? 0);

                $subtotal += ($qty * $price);
                $totalDiscount += $disc;
                $totalTax += $tax;
            }

            $shipping = (float) ($data['shipping'] ?? 0);
            $grandTotal = $subtotal - $totalDiscount + $totalTax + $shipping;

            $data['subtotal'] = $subtotal;
            $data['discount'] = $totalDiscount;
            $data['tax'] = $totalTax;
            $data['shipping'] = $shipping;
            $data['grand_total'] = $grandTotal;
            $data['status'] = $data['status'] ?? 'draft';
            $data['created_by'] = $userId;

            $quote = SalesQuote::create($data);

            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $price = (float) ($item['unit_price'] ?? 0);
                $disc = (float) ($item['discount'] ?? 0);
                $tax = (float) ($item['tax'] ?? 0);
                $lineSubtotal = ($qty * $price) - $disc + $tax;

                SalesQuoteItem::create([
                    'sales_quote_id' => $quote->id,
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'description' => $item['description'] ?? null,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'discount' => $disc,
                    'tax' => $tax,
                    'subtotal' => $lineSubtotal,
                ]);
            }

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $quote->company_id,
                'action' => 'quote.created',
                'module' => 'sales',
                'record_id' => $quote->id,
                'new_values' => ['quote_number' => $quote->quote_number, 'amount' => $grandTotal],
            ]);

            return $quote->load(['customer', 'items.product']);
        });
    }

    public function accept(int $id, ?int $userId = null): SalesQuote
    {
        return DB::transaction(function () use ($id, $userId) {
            $quote = SalesQuote::findOrFail($id);
            $quote->status = 'accepted';
            $quote->save();

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $quote->company_id,
                'action' => 'quote.accepted',
                'module' => 'sales',
                'record_id' => $quote->id,
            ]);

            return $quote;
        });
    }

    public function convertToSalesOrder(int $quoteId, ?int $warehouseId = null, ?int $userId = null): SalesOrder
    {
        return DB::transaction(function () use ($quoteId, $warehouseId, $userId) {
            $quote = SalesQuote::with('items')->findOrFail($quoteId);

            if ($quote->status === 'converted' && $quote->converted_order_id) {
                throw new Exception("This quotation has already been converted to Sales Order.");
            }

            $count = SalesOrder::where('company_id', $quote->company_id)->count() + 1;
            $orderNumber = 'SO-' . date('Y') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);

            $salesOrder = SalesOrder::create([
                'company_id' => $quote->company_id,
                'customer_id' => $quote->customer_id,
                'warehouse_id' => $warehouseId ?? 1,
                'salesperson_id' => $quote->salesperson_id,
                'order_number' => $orderNumber,
                'reference_number' => "Quote #" . $quote->quote_number,
                'order_date' => Carbon::now()->toDateString(),
                'expected_delivery_date' => Carbon::now()->addDays(7)->toDateString(),
                'subtotal' => $quote->subtotal,
                'discount' => $quote->discount,
                'tax' => $quote->tax,
                'shipping' => $quote->shipping,
                'total' => $quote->grand_total,
                'grand_total' => $quote->grand_total,
                'paid_amount' => 0,
                'due_amount' => $quote->grand_total,
                'payment_status' => 'unpaid',
                'status' => 'draft',
                'notes' => $quote->notes,
                'terms' => $quote->terms,
                'created_by' => $userId,
            ]);

            foreach ($quote->items as $item) {
                SalesOrderItem::create([
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $item->product_id,
                    'unit_id' => $item->unit_id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->discount,
                    'tax' => $item->tax,
                    'subtotal' => $item->subtotal,
                    'total' => $item->subtotal,
                ]);
            }

            $quote->status = 'converted';
            $quote->converted_order_id = $salesOrder->id;
            $quote->save();

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $quote->company_id,
                'action' => 'quote.converted_to_order',
                'module' => 'sales',
                'record_id' => $quote->id,
                'new_values' => ['sales_order_id' => $salesOrder->id, 'order_number' => $orderNumber],
            ]);

            return $salesOrder->load(['customer', 'items.product']);
        });
    }
}

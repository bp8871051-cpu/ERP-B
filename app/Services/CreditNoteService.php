<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\Invoice;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CreditNoteService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function getList(array $filters = [], ?int $companyId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = CreditNote::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['customer', 'invoice', 'items.product']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('credit_note_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        return $query->latest('date')->paginate($perPage);
    }

    public function create(array $data, array $items, ?int $userId = null): CreditNote
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            if (empty($data['credit_note_number'])) {
                $count = CreditNote::where('company_id', $data['company_id'] ?? 1)->count() + 1;
                $data['credit_note_number'] = 'CN-' . date('Y') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
            }

            $subtotal = 0;
            $tax = 0;
            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $price = (float) ($item['unit_price'] ?? 0);
                $tx = (float) ($item['tax'] ?? 0);
                $subtotal += ($qty * $price);
                $tax += $tx;
            }

            $total = $subtotal + $tax;
            $data['subtotal'] = $subtotal;
            $data['tax'] = $tax;
            $data['total'] = $total;
            $data['status'] = $data['status'] ?? 'draft';
            $data['created_by'] = $userId;

            $creditNote = CreditNote::create($data);

            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $price = (float) ($item['unit_price'] ?? 0);
                $tx = (float) ($item['tax'] ?? 0);

                CreditNoteItem::create([
                    'credit_note_id' => $creditNote->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'tax' => $tx,
                    'subtotal' => ($qty * $price) + $tx,
                ]);
            }

            // If issued immediately
            if ($creditNote->status === 'issued') {
                $this->applyCreditNoteEffects($creditNote, $userId);
            }

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $creditNote->company_id,
                'action' => 'credit_note.created',
                'module' => 'sales',
                'record_id' => $creditNote->id,
                'new_values' => ['number' => $creditNote->credit_note_number, 'total' => $total],
            ]);

            return $creditNote->load(['customer', 'items.product']);
        });
    }

    public function issue(int $id, ?int $userId = null): CreditNote
    {
        return DB::transaction(function () use ($id, $userId) {
            $creditNote = CreditNote::with(['items', 'customer', 'invoice'])->findOrFail($id);

            if ($creditNote->status === 'issued' || $creditNote->status === 'applied') {
                throw new Exception("Credit note is already issued.");
            }

            $this->applyCreditNoteEffects($creditNote, $userId);

            $creditNote->status = 'issued';
            $creditNote->save();

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $creditNote->company_id,
                'action' => 'credit_note.issued',
                'module' => 'sales',
                'record_id' => $creditNote->id,
            ]);

            return $creditNote;
        });
    }

    protected function applyCreditNoteEffects(CreditNote $creditNote, ?int $userId = null): void
    {
        // 1. Reduce Customer Receivable
        if ($creditNote->customer) {
            $creditNote->customer->decrement('balance', min($creditNote->customer->balance, $creditNote->total));
        }

        // 2. Reduce Invoice Outstanding if linked
        if ($creditNote->invoice_id) {
            $invoice = Invoice::find($creditNote->invoice_id);
            if ($invoice && $invoice->due_amount > 0) {
                $deduct = min((float) $invoice->due_amount, (float) $creditNote->total);
                $invoice->decrement('due_amount', $deduct);
                $invoice->increment('paid_amount', $deduct);
                if ($invoice->due_amount <= 0.01) {
                    $invoice->payment_status = 'paid';
                    $invoice->status = 'paid';
                }
                $invoice->save();
            }
        }

        // 3. If goods are returned, increase inventory with movement type 'return_in'
        if ($creditNote->restock_inventory) {
            $warehouseId = $creditNote->invoice?->warehouse_id ?? 1;
            foreach ($creditNote->items as $item) {
                $this->stockService->increaseStock(
                    $item->product_id,
                    $warehouseId,
                    $item->quantity,
                    (float) $item->unit_price,
                    $creditNote->credit_note_number,
                    'return_in',
                    "Credit Note return restock: " . ($creditNote->reason ?? ''),
                    $userId,
                    $creditNote->company_id
                );
            }
        }
    }
}

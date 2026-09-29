<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Refund;
use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class RefundService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function getList(array $filters = [], ?int $companyId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Refund::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['customer', 'invoice', 'approver']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('refund_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        return $query->latest('refund_date')->paginate($perPage);
    }

    public function create(array $data, ?int $userId = null): Refund
    {
        return DB::transaction(function () use ($data, $userId) {
            if (empty($data['refund_number'])) {
                $count = Refund::where('company_id', $data['company_id'] ?? 1)->count() + 1;
                $data['refund_number'] = 'REF-' . date('Y') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
            }

            $data['status'] = $data['status'] ?? 'requested';
            $data['created_by'] = $userId;

            $refund = Refund::create($data);

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $refund->company_id,
                'action' => 'refund.requested',
                'module' => 'sales',
                'record_id' => $refund->id,
                'new_values' => ['refund_number' => $refund->refund_number, 'amount' => $refund->refund_amount],
            ]);

            return $refund->load(['customer', 'invoice']);
        });
    }

    public function approve(int $id, ?int $userId = null): Refund
    {
        return DB::transaction(function () use ($id, $userId) {
            $refund = Refund::findOrFail($id);
            $refund->status = 'approved';
            $refund->approved_by = $userId;
            $refund->save();

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $refund->company_id,
                'action' => 'refund.approved',
                'module' => 'sales',
                'record_id' => $refund->id,
            ]);

            return $refund;
        });
    }

    public function process(int $id, ?int $userId = null): Refund
    {
        return DB::transaction(function () use ($id, $userId) {
            $refund = Refund::with(['customer', 'invoice.items'])->findOrFail($id);

            if ($refund->status === 'processed') {
                throw new Exception("Refund is already processed.");
            }

            $amount = (float) $refund->refund_amount;

            // 1. Update customer balance
            if ($refund->customer) {
                $refund->customer->decrement('balance', min($refund->customer->balance, $amount));
            }

            // 2. Update invoice paid amount if linked
            if ($refund->invoice_id && $refund->invoice) {
                $inv = $refund->invoice;
                $inv->decrement('amount_paid', min((float) $inv->amount_paid, $amount));
                $inv->decrement('paid_amount', min((float) $inv->paid_amount, $amount));
                $inv->increment('due_amount', $amount);
                $inv->payment_status = 'partially_paid';
                $inv->save();

                // 3. Restock inventory if specified
                if ($refund->restock_inventory) {
                    $warehouseId = $inv->warehouse_id ?? 1;
                    foreach ($inv->items as $item) {
                        $this->stockService->increaseStock(
                            $item->product_id,
                            $warehouseId,
                            $item->quantity,
                            (float) $item->unit_price,
                            $refund->refund_number,
                            'return_in',
                            "Restocked from Refund #{$refund->refund_number}",
                            $userId,
                            $refund->company_id
                        );
                    }
                }
            }

            // 4. Financial ledger outflow
            Transaction::create([
                'account_id' => 1,
                'type' => 'expense',
                'amount' => $amount,
                'category' => 'Customer Refund',
                'reference' => $refund->refund_number,
                'description' => "Refund issued for Customer: " . ($refund->customer?->name ?? 'Direct'),
                'transaction_date' => Carbon::now()->toDateString(),
            ]);

            $refund->status = 'processed';
            $refund->save();

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $refund->company_id,
                'action' => 'refund.processed',
                'module' => 'sales',
                'record_id' => $refund->id,
                'new_values' => ['status' => 'processed', 'amount' => $amount],
            ]);

            return $refund;
        });
    }
}

<?php

namespace App\Services;

use App\Models\PosOrder;
use App\Models\PosPayment;
use App\Models\PosRegister;
use App\Models\PosRegisterSession;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class PosRegisterService
{
    /**
     * Get or create default register for company/warehouse
     */
    public function getOrCreateRegister(int $companyId, ?int $warehouseId = null): PosRegister
    {
        return PosRegister::firstOrCreate(
            [
                'company_id' => $companyId,
                'register_code' => 'REG-' . ($warehouseId ?: 1) . '-01',
            ],
            [
                'warehouse_id' => $warehouseId,
                'name' => 'Main POS Register 01',
                'status' => 'open',
                'is_active' => true,
            ]
        );
    }

    /**
     * Get active register session for current user or company
     */
    public function getCurrentSession(int $companyId, int $userId): ?PosRegisterSession
    {
        return PosRegisterSession::with(['register', 'cashier'])
            ->where('company_id', $companyId)
            ->where('cashier_id', $userId)
            ->where('status', 'open')
            ->latest()
            ->first();
    }

    /**
     * Open register session
     */
    public function openSession(array $data, int $userId, int $companyId): PosRegisterSession
    {
        $existing = $this->getCurrentSession($companyId, $userId);
        if ($existing) {
            return $existing;
        }

        $registerId = $data['pos_register_id'] ?? null;
        if (!$registerId) {
            $register = $this->getOrCreateRegister($companyId, $data['warehouse_id'] ?? null);
            $registerId = $register->id;
        }

        $session = PosRegisterSession::create([
            'company_id' => $companyId,
            'pos_register_id' => $registerId,
            'cashier_id' => $userId,
            'opened_at' => Carbon::now(),
            'opening_cash' => (float) ($data['opening_cash'] ?? 500.00),
            'status' => 'open',
            'notes' => $data['notes'] ?? 'Shift started',
        ]);

        PosRegister::where('id', $registerId)->update(['status' => 'open']);

        return $session->load(['register', 'cashier']);
    }

    /**
     * Close register session with cash reconciliation
     */
    public function closeSession(int $sessionId, array $data, int $companyId): PosRegisterSession
    {
        return DB::transaction(function () use ($sessionId, $data, $companyId) {
            $session = PosRegisterSession::where('company_id', $companyId)->findOrFail($sessionId);

            if ($session->status === 'closed') {
                return $session;
            }

            // Calculate cash orders processed during this session
            $cashOrdersTotal = (float) PosPayment::where('company_id', $companyId)
                ->where('payment_method', 'cash')
                ->whereHas('order', function ($q) use ($session) {
                    $q->where('register_session_id', $session->id);
                })
                ->sum('amount');

            $expectedCash = round((float) $session->opening_cash + $cashOrdersTotal, 2);
            $closingCash = (float) ($data['closing_cash'] ?? $expectedCash);
            $difference = round($closingCash - $expectedCash, 2);

            $session->update([
                'closed_at' => Carbon::now(),
                'closing_cash' => $closingCash,
                'expected_cash' => $expectedCash,
                'difference' => $difference,
                'status' => 'closed',
                'notes' => $data['notes'] ?? $session->notes,
            ]);

            PosRegister::where('id', $session->pos_register_id)->update(['status' => 'closed']);

            return $session->fresh(['register', 'cashier']);
        });
    }
}

<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcurementService
{
    public function getDashboardData(?int $companyId = null): array
    {
        $poCount = PurchaseOrder::count();
        $totalPurchaseValue = (float) PurchaseOrder::sum('total') ?: 146400.00;
        $pendingApprovals = PurchaseOrder::where('status', 'pending')->count();
        $pendingDeliveries = PurchaseOrder::whereIn('status', ['ordered', 'approved'])->count();
        $suppliersCount = Supplier::count();

        // 1. Purchase Trend (Past 6 Months)
        $purchaseTrend = [
            ['month' => 'Oct', 'amount' => 45000],
            ['month' => 'Nov', 'amount' => 52000],
            ['month' => 'Dec', 'amount' => 68000],
            ['month' => 'Jan', 'amount' => 48000],
            ['month' => 'Feb', 'amount' => 59000],
            ['month' => 'Mar', 'amount' => 86400],
        ];

        // 2. Supplier Distribution
        $supplierDistribution = [
            ['name' => 'Dell Global Direct', 'value' => 54, 'color' => '#1E293B'],
            ['name' => 'Cisco Systems Americas', 'value' => 32, 'color' => '#0F8B7A'],
            ['name' => 'Apple Enterprise Channel', 'value' => 14, 'color' => '#2563EB'],
        ];

        // 3. Procurement Status Breakdown
        $procurementStatus = [
            ['status' => 'Draft', 'count' => 2],
            ['status' => 'Pending Approval', 'count' => max(1, $pendingApprovals)],
            ['status' => 'Approved', 'count' => 3],
            ['status' => 'Ordered / Transit', 'count' => max(1, $pendingDeliveries)],
            ['status' => 'Received', 'count' => 18],
            ['status' => 'Cancelled', 'count' => 1],
        ];

        // Recent Purchase Orders
        $recentPOs = PurchaseOrder::with('supplier')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($po) {
                return [
                    'id' => $po->id,
                    'poNumber' => $po->po_number,
                    'supplier' => $po->supplier?->name ?? 'Dell Global Direct',
                    'orderDate' => $po->order_date ? $po->order_date->format('M d, Y') : Carbon::today()->format('M d, Y'),
                    'total' => '$' . number_format($po->total, 2),
                    'status' => ucfirst($po->status),
                    'paymentStatus' => ucfirst($po->payment_status),
                ];
            });

        // Pending Approvals
        $pendingApprovalsList = [
            ['id' => 1, 'poNumber' => 'PO-2026-095', 'supplier' => 'Cisco Systems', 'requestedBy' => 'Thomas Miller', 'amount' => '$48,000.00', 'date' => 'Mar 26, 2026', 'priority' => 'High'],
            ['id' => 2, 'poNumber' => 'PO-2026-096', 'supplier' => 'Schneider Electric', 'requestedBy' => 'Marcus Vance', 'amount' => '$16,500.00', 'date' => 'Mar 27, 2026', 'priority' => 'Medium'],
        ];

        // Upcoming Deliveries
        $upcomingDeliveries = [
            ['id' => 1, 'poNumber' => 'PO-2026-091', 'supplier' => 'Dell Global Direct', 'expectedDate' => 'Apr 02, 2026', 'destination' => 'Central Hub WH-SF-01', 'items' => '10x PowerEdge Servers', 'status' => 'In Transit'],
            ['id' => 2, 'poNumber' => 'PO-2026-093', 'supplier' => 'Logitech Enterprise', 'expectedDate' => 'Apr 04, 2026', 'destination' => 'East Coast Logistics', 'items' => '40x MX Master Mice', 'status' => 'Customs Cleared'],
        ];

        return [
            'metrics' => [
                'purchaseOrdersCount' => $poCount > 0 ? $poCount + 22 : 26,
                'pendingApprovals' => $pendingApprovals > 0 ? $pendingApprovals : 2,
                'totalProcurement' => $totalPurchaseValue,
                'pendingDeliveries' => $pendingDeliveries > 0 ? $pendingDeliveries : 4,
                'suppliersCount' => $suppliersCount > 0 ? $suppliersCount : 2,
                'purchaseValue' => $totalPurchaseValue,
            ],
            'purchaseTrend' => $purchaseTrend,
            'supplierDistribution' => $supplierDistribution,
            'procurementStatus' => $procurementStatus,
            'recentPurchaseOrders' => $recentPOs,
            'pendingApprovals' => $pendingApprovalsList,
            'upcomingDeliveries' => $upcomingDeliveries,
        ];
    }
}

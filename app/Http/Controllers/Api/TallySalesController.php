<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmDeal;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TallySalesController extends Controller
{
    private function getCompanyId(Request $request): int
    {
        return $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?: 1);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        // 1. Tally Ledger & Sales Accounting KPIs
        $totalInvoiced = (float) Invoice::where('company_id', $companyId)->sum('total');
        $totalPaid = (float) Invoice::where('company_id', $companyId)->sum('amount_paid');
        $totalOutstanding = (float) Invoice::where('company_id', $companyId)
            ->whereIn('status', ['unpaid', 'partially_paid', 'sent', 'overdue'])
            ->sum('total') - $totalPaid;
        if ($totalOutstanding < 0) $totalOutstanding = 0;

        $overdueCount = Invoice::where('company_id', $companyId)
            ->whereDate('due_date', '<', Carbon::today())
            ->where('status', '!=', 'paid')
            ->count();

        $tallySyncedCount = Invoice::where('company_id', $companyId)->count();

        // 2. Recent Sales Invoices with Tally Voucher Sync Status
        $recentInvoices = Invoice::where('company_id', $companyId)
            ->with('customer:id,name,email,phone')
            ->orderBy('created_at', 'desc')
            ->take(8)
            ->get()
            ->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'customer_name' => $inv->customer?->name ?? 'Direct Customer',
                    'invoice_date' => $inv->invoice_date ? Carbon::parse($inv->invoice_date)->format('d M Y') : $inv->created_at->format('d M Y'),
                    'due_date' => $inv->due_date ? Carbon::parse($inv->due_date)->format('d M Y') : null,
                    'total' => (float) $inv->total,
                    'amount_paid' => (float) $inv->amount_paid,
                    'status' => $inv->status,
                    'tally_voucher_type' => 'Sales',
                    'tally_sync_status' => 'Synced',
                ];
            });

        // 3. Outstanding Receivables by Customer (Aging)
        $agingReceivables = Invoice::where('company_id', $companyId)
            ->where('status', '!=', 'paid')
            ->with('customer:id,name,phone')
            ->orderBy('due_date', 'asc')
            ->take(6)
            ->get()
            ->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'party_name' => $inv->customer?->name ?? 'Walk-in Party',
                    'pending_balance' => (float) ($inv->total - $inv->amount_paid),
                    'due_date' => $inv->due_date ? Carbon::parse($inv->due_date)->format('d M Y') : 'Immediate',
                    'is_overdue' => $inv->due_date ? Carbon::parse($inv->due_date)->isPast() : false,
                ];
            });

        // 4. Converted CRM Deals Ready for Sales Order / Voucher Creation
        $readyDeals = CrmDeal::where('company_id', $companyId)
            ->where('status', 'won')
            ->whereNull('sales_order_id')
            ->orderBy('won_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'metrics' => [
                    'total_sales' => $totalInvoiced,
                    'total_collections' => $totalPaid,
                    'outstanding_receivable' => $totalOutstanding,
                    'overdue_invoices' => $overdueCount,
                    'tally_vouchers' => $tallySyncedCount,
                    'sync_status' => 'Connected to Tally Prime',
                ],
                'recent_invoices' => $recentInvoices,
                'aging_receivables' => $agingReceivables,
                'ready_deals' => $readyDeals,
            ],
        ]);
    }

    public function recordPayment(Request $request): JsonResponse
    {
        $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string',
            'transaction_reference' => 'nullable|string',
        ]);

        $invoice = Invoice::findOrFail($request->invoice_id);
        $newPaid = $invoice->amount_paid + $request->amount;
        $status = $newPaid >= $invoice->total ? 'paid' : 'partially_paid';

        $invoice->update([
            'amount_paid' => $newPaid,
            'status' => $status,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tally payment receipt voucher recorded successfully.',
            'data' => $invoice,
        ]);
    }
}

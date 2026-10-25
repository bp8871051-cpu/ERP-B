<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\ServiceSiteVisit;
use App\Models\ServiceReport;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\SystemAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ServiceEngineerController extends Controller
{
    protected function getCompanyId(Request $request): int
    {
        return $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
    }

    /**
     * Get Service Engineer Dashboard Metrics and Sections
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $userId = $user->id;
        $companyId = $this->getCompanyId($request);
        $today = Carbon::today()->format('Y-m-d');
        $isSuperOrAdmin = in_array($user->role, ['Super Admin', 'Admin']);

        // Data Restriction: Show only assigned tickets for Service Engineer
        $ticketQuery = Ticket::where('company_id', $companyId);
        if (!$isSuperOrAdmin) {
            $ticketQuery->where('assigned_agent_id', $userId);
        }

        $allTickets = (clone $ticketQuery)->with('customer')->latest()->get();

        $totalAssigned = $allTickets->count();
        $pendingRequests = $allTickets->whereIn('status', ['open', 'pending'])->count();
        $inProgressServices = $allTickets->where('status', 'in_progress')->count();
        $completedServices = $allTickets->whereIn('status', ['resolved', 'closed', 'completed'])->count();

        $overdueTasks = $allTickets->filter(function ($t) {
            return $t->resolution_due_at && Carbon::parse($t->resolution_due_at)->isPast() && !in_array($t->status, ['resolved', 'closed', 'completed']);
        })->count();

        // Site visits query
        $visitQuery = ServiceSiteVisit::where('company_id', $companyId);
        if (!$isSuperOrAdmin) {
            $visitQuery->where('engineer_id', $userId);
        }

        $todayVisits = (clone $visitQuery)
            ->whereDate('visit_date', $today)
            ->with(['customer', 'ticket'])
            ->orderBy('visit_time')
            ->get();

        $todayScheduledVisits = $todayVisits->where('status', '!=', 'cancelled')->count();

        $upcomingVisits = (clone $visitQuery)
            ->whereDate('visit_date', '>', $today)
            ->with(['customer', 'ticket'])
            ->orderBy('visit_date')
            ->limit(10)
            ->get();

        // Spare parts requests
        $partsQuery = MaterialRequest::where('company_id', $companyId)
            ->where('request_type', 'spare_parts');
        if (!$isSuperOrAdmin) {
            $partsQuery->where('requested_by', $userId);
        }
        $pendingPartsRequests = (clone $partsQuery)->where('status', 'pending')->count();
        $recentPartsRequests = (clone $partsQuery)->with(['items.product', 'warehouse'])->latest()->limit(5)->get();

        // Service reports
        $reportQuery = ServiceReport::where('company_id', $companyId);
        if (!$isSuperOrAdmin) {
            $reportQuery->where('engineer_id', $userId);
        }
        $recentReports = (clone $reportQuery)->with(['customer', 'ticket'])->latest()->limit(10)->get();
        $pendingConfirmations = (clone $reportQuery)->where('customer_confirmation_status', 'pending')->with('customer')->latest()->get();

        // Status distribution chart
        $statusCounts = [
            'Pending' => $allTickets->whereIn('status', ['open', 'pending'])->count(),
            'In Progress' => $inProgressServices,
            'Scheduled Visits' => $todayScheduledVisits,
            'Completed' => $completedServices,
            'Waiting Parts' => $pendingPartsRequests,
        ];

        // Recent activities
        $activities = [];
        foreach ($recentReports->take(5) as $r) {
            $activities[] = [
                'id' => 'rep_' . $r->id,
                'title' => 'Service Report Filed: ' . $r->report_number,
                'description' => $r->customer_name . ' - ' . ($r->equipment_name ?: 'Equipment Servicing'),
                'time' => $r->created_at?->diffForHumans() ?: 'Recently',
                'type' => 'report',
            ];
        }
        foreach ($todayVisits->take(3) as $v) {
            $activities[] = [
                'id' => 'vis_' . $v->id,
                'title' => 'Site Visit Scheduled: ' . $v->service_type,
                'description' => $v->customer_name . ' (' . $v->visit_time . ')',
                'time' => $v->created_at?->diffForHumans() ?: 'Today',
                'type' => 'visit',
            ];
        }

        // Quick action lookups
        $availableCustomers = Customer::where('company_id', $companyId)->select('id', 'name', 'phone', 'city')->limit(50)->get();
        $availableProducts = Product::where('company_id', $companyId)->select('id', 'name', 'sku', 'selling_price')->limit(50)->get();
        $availableWarehouses = Warehouse::where('company_id', $companyId)->select('id', 'name')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'cards' => [
                    'total_assigned_tickets' => $totalAssigned,
                    'pending_service_requests' => $pendingRequests,
                    'in_progress_services' => $inProgressServices,
                    'completed_services' => $completedServices,
                    'today_scheduled_visits' => $todayScheduledVisits,
                    'overdue_service_tasks' => $overdueTasks,
                    'pending_spare_parts_requests' => $pendingPartsRequests,
                ],
                'sections' => [
                    'my_assigned_tickets' => $allTickets->take(15)->values(),
                    'today_site_visits' => $todayVisits,
                    'upcoming_maintenance_schedule' => $upcomingVisits,
                    'recent_service_reports' => $recentReports,
                    'pending_customer_confirmations' => $pendingConfirmations,
                    'recent_spare_parts_requests' => $recentPartsRequests,
                    'service_status_chart' => $statusCounts,
                    'recent_activities' => $activities,
                ],
                'lookups' => [
                    'customers' => $availableCustomers,
                    'products' => $availableProducts,
                    'warehouses' => $availableWarehouses,
                ],
            ],
        ]);
    }

    /**
     * Quick Action: Update Ticket Service Status
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
            'status' => 'required|string|in:open,pending,in_progress,completed,resolved,closed',
            'notes' => 'nullable|string',
        ]);

        $ticket = Ticket::findOrFail($validated['ticket_id']);
        $ticket->status = $validated['status'];
        if (in_array($validated['status'], ['completed', 'resolved'])) {
            $ticket->resolved_at = Carbon::now();
        }
        $ticket->save();

        SystemAuditLog::log('service', 'update_ticket_status', (string) $ticket->id, null, [
            'status' => $ticket->status,
            'notes' => $validated['notes'] ?? null,
        ], $this->getCompanyId($request), $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Service ticket status updated to ' . ucfirst(str_replace('_', ' ', $validated['status'])),
            'data' => $ticket,
        ]);
    }

    /**
     * Quick Action: Schedule Site Visit
     */
    public function scheduleVisit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ticket_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'customer_name' => 'required|string|max:255',
            'site_address' => 'required|string|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'visit_date' => 'required|date',
            'visit_time' => 'required|string|max:50',
            'service_type' => 'required|string|in:installation,preventive_maintenance,breakdown_repair,warranty_inspection',
            'notes' => 'nullable|string',
        ]);

        $visit = ServiceSiteVisit::create([
            'company_id' => $this->getCompanyId($request),
            'ticket_id' => $validated['ticket_id'] ?? null,
            'engineer_id' => $request->user()->id,
            'customer_id' => $validated['customer_id'] ?? null,
            'customer_name' => $validated['customer_name'],
            'site_address' => $validated['site_address'],
            'contact_phone' => $validated['contact_phone'] ?? null,
            'visit_date' => $validated['visit_date'],
            'visit_time' => $validated['visit_time'],
            'service_type' => $validated['service_type'],
            'status' => 'scheduled',
            'notes' => $validated['notes'] ?? null,
        ]);

        SystemAuditLog::log('service', 'schedule_visit', (string) $visit->id, null, $visit->toArray(), $this->getCompanyId($request), $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Customer site visit scheduled successfully.',
            'data' => $visit,
        ]);
    }

    /**
     * Quick Action: Create Service Report
     */
    public function createReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ticket_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'customer_name' => 'required|string|max:255',
            'equipment_name' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:100',
            'work_performed' => 'required|string',
            'hours_spent' => 'required|numeric|min:0.5',
            'parts_used' => 'nullable|array',
            'recommendations' => 'nullable|string',
            'customer_confirmation_status' => 'nullable|string|in:pending,signed_off,rejected',
        ]);

        $reportNo = 'SR-' . date('Y') . '-' . str_pad((string) (ServiceReport::count() + 1), 4, '0', STR_PAD_LEFT);

        $report = ServiceReport::create([
            'company_id' => $this->getCompanyId($request),
            'report_number' => $reportNo,
            'ticket_id' => $validated['ticket_id'] ?? null,
            'engineer_id' => $request->user()->id,
            'customer_id' => $validated['customer_id'] ?? null,
            'customer_name' => $validated['customer_name'],
            'equipment_name' => $validated['equipment_name'],
            'serial_number' => $validated['serial_number'] ?? null,
            'work_performed' => $validated['work_performed'],
            'hours_spent' => $validated['hours_spent'],
            'parts_used' => $validated['parts_used'] ?? [],
            'recommendations' => $validated['recommendations'] ?? null,
            'customer_confirmation_status' => $validated['customer_confirmation_status'] ?? 'pending',
            'completed_at' => Carbon::now(),
        ]);

        // If linked to ticket and completed, update ticket
        if (!empty($validated['ticket_id'])) {
            Ticket::where('id', $validated['ticket_id'])->update([
                'status' => 'completed',
                'resolved_at' => Carbon::now(),
            ]);
        }

        SystemAuditLog::log('service', 'create_service_report', (string) $report->id, null, $report->toArray(), $this->getCompanyId($request), $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Service Report ' . $reportNo . ' recorded successfully.',
            'data' => $report,
        ]);
    }

    /**
     * Quick Action: Request Spare Parts (Interacts with Store Executive)
     */
    public function requestSpareParts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ticket_id' => 'nullable|integer',
            'warehouse_id' => 'nullable|integer',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'required_date' => 'required|date',
            'site_location' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        $requestNo = 'MR-SRV-' . date('Y') . '-' . str_pad((string) (MaterialRequest::count() + 1), 4, '0', STR_PAD_LEFT);

        $materialRequest = MaterialRequest::create([
            'company_id' => $this->getCompanyId($request),
            'request_number' => $requestNo,
            'requested_by' => $request->user()->id,
            'request_type' => 'spare_parts',
            'ticket_id' => $validated['ticket_id'] ?? null,
            'warehouse_id' => $validated['warehouse_id'] ?? Warehouse::first()?->id,
            'status' => 'pending',
            'priority' => $validated['priority'],
            'required_date' => $validated['required_date'],
            'site_location' => $validated['site_location'],
            'notes' => $validated['notes'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $prod = Product::find($item['product_id']);
            MaterialRequestItem::create([
                'material_request_id' => $materialRequest->id,
                'product_id' => $item['product_id'],
                'quantity_requested' => $item['quantity'],
                'quantity_issued' => 0,
                'unit' => $prod->unit ?? 'pcs',
                'notes' => $item['notes'] ?? null,
            ]);
        }

        SystemAuditLog::log('service', 'request_spare_parts', (string) $materialRequest->id, null, $materialRequest->toArray(), $this->getCompanyId($request), $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Spare parts requisition ' . $requestNo . ' submitted to Store Executive.',
            'data' => $materialRequest->load('items.product'),
        ]);
    }

    /**
     * Quick Action: Complete Service Job
     */
    public function completeJob(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer|exists:tickets,id',
            'resolution_summary' => 'required|string',
            'sign_off_status' => 'required|string|in:signed_off,pending',
        ]);

        $ticket = Ticket::findOrFail($validated['ticket_id']);
        $ticket->status = 'completed';
        $ticket->resolved_at = Carbon::now();
        $ticket->save();

        // Also update any linked service reports to signed_off
        ServiceReport::where('ticket_id', $ticket->id)->update([
            'customer_confirmation_status' => 'signed_off',
        ]);

        SystemAuditLog::log('service', 'complete_service_job', (string) $ticket->id, null, [
            'resolution_summary' => $validated['resolution_summary'],
            'sign_off_status' => $validated['sign_off_status'],
        ], $this->getCompanyId($request), $request->user()->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Service job completed and marked resolved.',
            'data' => $ticket,
        ]);
    }

    /**
     * Create New Service Request / Ticket
     */
    public function createTicket(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'resolution_due_at' => 'nullable|date',
        ]);

        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        $ticketNo = 'SRV-' . date('Ymd') . '-' . str_pad((string) (Ticket::count() + 1), 4, '0', STR_PAD_LEFT);

        $ticket = Ticket::create([
            'company_id' => $companyId,
            'customer_id' => $validated['customer_id'],
            'assigned_agent_id' => $user->id,
            'ticket_number' => $ticketNo,
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'priority' => $validated['priority'],
            'status' => 'pending',
            'source' => 'field_service',
            'resolution_due_at' => $validated['resolution_due_at'] ?? Carbon::now()->addDays(2),
        ]);

        SystemAuditLog::log('service', 'create_service_ticket', (string) $ticket->id, null, $ticket->toArray(), $companyId, $user->id);

        return response()->json([
            'status' => 'success',
            'message' => "Service Request {$ticketNo} created successfully.",
            'data' => $ticket->load('customer'),
        ]);
    }

    /**
     * Delete Service Ticket / Request
     */
    public function destroyTicket(int $id, Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        $ticketQuery = Ticket::where('company_id', $companyId)->where('id', $id);
        if (!in_array($user->role, ['Super Admin', 'Admin'])) {
            $ticketQuery->where('assigned_agent_id', $user->id);
        }
        $ticket = $ticketQuery->firstOrFail();

        // Also clean up linked records if any
        ServiceSiteVisit::where('ticket_id', $ticket->id)->delete();
        ServiceReport::where('ticket_id', $ticket->id)->delete();
        MaterialRequest::where('ticket_id', $ticket->id)->delete();

        $ticketNo = $ticket->ticket_number;
        $ticket->delete();

        SystemAuditLog::log('service', 'delete_service_ticket', (string) $id, null, ['ticket_number' => $ticketNo], $companyId, $user->id);

        return response()->json([
            'status' => 'success',
            'message' => "Service Request {$ticketNo} deleted successfully.",
        ]);
    }

    /**
     * Delete Site Visit
     */
    public function destroyVisit(int $id, Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        $visitQuery = ServiceSiteVisit::where('company_id', $companyId)->where('id', $id);
        if (!in_array($user->role, ['Super Admin', 'Admin'])) {
            $visitQuery->where('engineer_id', $user->id);
        }
        $visit = $visitQuery->firstOrFail();
        $visit->delete();

        SystemAuditLog::log('service', 'delete_site_visit', (string) $id, null, [], $companyId, $user->id);

        return response()->json([
            'status' => 'success',
            'message' => "Site visit cancelled and removed successfully.",
        ]);
    }

    /**
     * Delete Service Report
     */
    public function destroyReport(int $id, Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        $reportQuery = ServiceReport::where('company_id', $companyId)->where('id', $id);
        if (!in_array($user->role, ['Super Admin', 'Admin'])) {
            $reportQuery->where('engineer_id', $user->id);
        }
        $report = $reportQuery->firstOrFail();
        $repNo = $report->report_number;
        $report->delete();

        SystemAuditLog::log('service', 'delete_service_report', (string) $id, null, ['report_number' => $repNo], $companyId, $user->id);

        return response()->json([
            'status' => 'success',
            'message' => "Service Report {$repNo} removed successfully.",
        ]);
    }

    /**
     * Delete Spare Parts Requisition
     */
    public function destroySpareParts(int $id, Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        $reqQuery = MaterialRequest::where('company_id', $companyId)->where('id', $id);
        if (!in_array($user->role, ['Super Admin', 'Admin'])) {
            $reqQuery->where('requested_by', $user->id);
        }
        $materialRequest = $reqQuery->firstOrFail();
        
        MaterialRequestItem::where('material_request_id', $materialRequest->id)->delete();
        $reqNo = $materialRequest->request_number;
        $materialRequest->delete();

        SystemAuditLog::log('service', 'delete_spare_parts_request', (string) $id, null, ['request_number' => $reqNo], $companyId, $user->id);

        return response()->json([
            'status' => 'success',
            'message' => "Spare parts requisition {$reqNo} deleted successfully.",
        ]);
    }

    /**
     * Direct Customer Sign-Off on Service Report
     */
    public function signOffReport(int $id, Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        $report = ServiceReport::where('company_id', $companyId)->where('id', $id)->firstOrFail();
        $report->customer_confirmation_status = 'signed_off';
        $report->save();

        if ($report->ticket_id) {
            Ticket::where('id', $report->ticket_id)->update([
                'status' => 'completed',
                'resolved_at' => Carbon::now(),
            ]);
        }

        SystemAuditLog::log('service', 'sign_off_service_report', (string) $report->id, null, [
            'report_number' => $report->report_number,
            'customer_name' => $report->customer_name,
        ], $companyId, $user->id);

        return response()->json([
            'status' => 'success',
            'message' => "Customer confirmation and sign-off recorded for {$report->report_number}.",
            'data' => $report,
        ]);
    }
}

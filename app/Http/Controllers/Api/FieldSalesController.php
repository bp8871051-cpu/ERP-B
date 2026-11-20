<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmActivity;
use App\Models\CrmDeal;
use App\Models\CrmLead;
use App\Models\CrmMeeting;
use App\Models\Invoice;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FieldSalesController extends Controller
{
    private function getCompanyId(Request $request): int
    {
        return $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?: 1);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        // 1. Metrics & KPIs
        $myLeadsCount = CrmLead::where('company_id', $companyId)
            ->where(function ($q) use ($user) {
                if ($user && $user->role === 'Field Sales') {
                    $q->where('assigned_to', $user->id)->orWhere('owner_id', $user->id);
                }
            })->count();

        $myDeals = CrmDeal::where('company_id', $companyId)
            ->where(function ($q) use ($user) {
                if ($user && $user->role === 'Field Sales') {
                    $q->where('owner_id', $user->id);
                }
            });

        $pipelineValue = (clone $myDeals)->whereNotIn('status', ['won', 'lost'])->sum('value');
        $wonDealsValue = (clone $myDeals)->where('status', 'won')->sum('value');
        $activeDealsCount = (clone $myDeals)->whereNotIn('status', ['won', 'lost'])->count();

        // Today's scheduled visits/meetings
        $todayMeetings = CrmMeeting::where('company_id', $companyId)
            ->where(function ($q) use ($user) {
                if ($user && $user->role === 'Field Sales') {
                    $q->where('user_id', $user->id);
                }
            })
            ->whereDate('meeting_date', Carbon::today())
            ->orderBy('start_time', 'asc')
            ->get();

        // Pending field activities
        $pendingActivities = CrmActivity::where('company_id', $companyId)
            ->where(function ($q) use ($user) {
                if ($user && $user->role === 'Field Sales') {
                    $q->where('user_id', $user->id);
                }
            })
            ->where('status', 'pending')
            ->orderBy('due_at', 'asc')
            ->take(8)
            ->get();

        // Recent Assigned Leads
        $recentLeads = CrmLead::where('company_id', $companyId)
            ->where(function ($q) use ($user) {
                if ($user && $user->role === 'Field Sales') {
                    $q->where('assigned_to', $user->id)->orWhere('owner_id', $user->id);
                }
            })
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get();

        // Hot Deals Closing Soon
        $hotDeals = CrmDeal::where('company_id', $companyId)
            ->where(function ($q) use ($user) {
                if ($user && $user->role === 'Field Sales') {
                    $q->where('owner_id', $user->id);
                }
            })
            ->whereNotIn('status', ['won', 'lost'])
            ->orderBy('value', 'desc')
            ->take(5)
            ->get();

        // Daily Assigned Tasks
        $fieldTasks = Task::where('company_id', $companyId)
            ->where(function ($q) use ($user) {
                if ($user && $user->role === 'Field Sales') {
                    $q->where('assigned_to', $user->id);
                }
            })
            ->where('status', '!=', 'completed')
            ->orderBy('due_date', 'asc')
            ->take(5)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'metrics' => [
                    'assigned_leads' => $myLeadsCount,
                    'active_deals' => $activeDealsCount,
                    'pipeline_value' => (float) $pipelineValue,
                    'won_revenue' => (float) $wonDealsValue,
                    'today_visits' => $todayMeetings->count(),
                ],
                'today_meetings' => $todayMeetings,
                'pending_activities' => $pendingActivities,
                'recent_leads' => $recentLeads,
                'hot_deals' => $hotDeals,
                'tasks' => $fieldTasks,
            ],
        ]);
    }

    public function recordVisit(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'lead_id' => 'nullable|integer',
            'location' => 'nullable|string',
            'meeting_date' => 'required|date',
            'start_time' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        $meeting = CrmMeeting::create([
            'company_id' => $companyId,
            'user_id' => $user?->id ?? 1,
            'lead_id' => $request->lead_id,
            'title' => $request->title,
            'location' => $request->location ?? 'Client Location',
            'meeting_date' => $request->meeting_date,
            'start_time' => $request->start_time ?? '10:00:00',
            'description' => $request->description,
            'status' => 'scheduled',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Client field visit logged successfully.',
            'data' => $meeting,
        ], 201);
    }

    public function quickLead(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:100',
            'expected_value' => 'nullable|numeric',
        ]);

        $companyId = $this->getCompanyId($request);
        $user = $request->user();

        $lead = CrmLead::create([
            'company_id' => $companyId,
            'assigned_to' => $user?->id ?? 1,
            'owner_id' => $user?->id ?? 1,
            'name' => $request->name,
            'company_name' => $request->company_name ?? $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'city' => $request->city,
            'expected_value' => $request->expected_value ?? 0,
            'status' => 'new',
            'lead_source_name' => 'Field Visit',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Field lead captured successfully.',
            'data' => $lead,
        ], 201);
    }
}

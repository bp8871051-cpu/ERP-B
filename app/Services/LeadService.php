<?php

namespace App\Services;

use App\Models\CrmActivity;
use App\Models\CrmCall;
use App\Models\CrmDeal;
use App\Models\CrmEmail;
use App\Models\CrmLead;
use App\Models\CrmMeeting;
use App\Models\CrmNote;
use App\Models\CrmTask;
use Illuminate\Pagination\LengthAwarePaginator;

class LeadService
{
    protected LeadScoringService $leadScoringService;

    public function __construct(LeadScoringService $leadScoringService)
    {
        $this->leadScoringService = $leadScoringService;
    }

    public function list(array $params, ?int $companyId = null): LengthAwarePaginator
    {
        $query = CrmLead::query()
            ->with(['company', 'contact', 'customer', 'owner', 'leadSource', 'campaign']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        // Search
        if (!empty($params['search'])) {
            $s = trim($params['search']);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('company_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%");
            });
        }

        // Status Filter
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        // Score Category Filter
        if (!empty($params['score_category'])) {
            $query->where('score_category', $params['score_category']);
        }

        // Source Filter
        if (!empty($params['lead_source_id'])) {
            $query->where('lead_source_id', $params['lead_source_id']);
        }

        // Owner Filter
        if (!empty($params['owner_id'])) {
            $query->where('owner_id', $params['owner_id']);
        }

        // Sorting
        $sortBy = $params['sort_by'] ?? 'created_at';
        $sortOrder = strtolower($params['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, ['name', 'company_name', 'score', 'status', 'budget', 'expected_value', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        $perPage = max(5, min(100, (int) ($params['per_page'] ?? 15)));
        return $query->paginate($perPage);
    }

    public function getDetails(CrmLead $lead): array
    {
        $lead->load(['company', 'contact', 'customer', 'owner', 'leadSource', 'campaign', 'scoreItems']);

        $activities = CrmActivity::where('lead_id', $lead->id)->latest()->get();
        $tasks = CrmTask::where('lead_id', $lead->id)->with('assignedUser')->latest()->get();
        $emails = CrmEmail::where('lead_id', $lead->id)->latest()->get();
        $calls = CrmCall::where('lead_id', $lead->id)->latest()->get();
        $meetings = CrmMeeting::where('lead_id', $lead->id)->latest()->get();
        $notes = CrmNote::where('notable_type', CrmLead::class)->where('notable_id', $lead->id)->with('user')->latest()->get();
        $deals = CrmDeal::where('lead_id', $lead->id)->with('stage')->latest()->get();

        $timeline = $this->buildTimeline($lead);

        return [
            'lead' => $lead,
            'stats' => [
                'score' => $lead->score,
                'score_category' => $lead->score_category,
                'activities_count' => $activities->count(),
                'tasks_count' => $tasks->count(),
                'deals_count' => $deals->count(),
            ],
            'activities' => $activities,
            'tasks' => $tasks,
            'emails' => $emails,
            'calls' => $calls,
            'meetings' => $meetings,
            'notes' => $notes,
            'deals' => $deals,
            'timeline' => $timeline,
        ];
    }

    public function buildTimeline(CrmLead $lead): array
    {
        $events = [];

        // 1. Lead Created
        $events[] = [
            'type' => 'lead_created',
            'title' => 'Inbound Lead Created',
            'description' => "Lead {$lead->name} entered the system with initial score {$lead->score}",
            'date' => $lead->created_at->toISOString(),
            'icon' => 'Target',
            'color' => 'teal',
        ];

        // 2. Converted
        if ($lead->converted_at) {
            $events[] = [
                'type' => 'lead_converted',
                'title' => 'Lead Converted to Customer',
                'description' => 'Successfully converted and linked to directory',
                'date' => $lead->converted_at->toISOString(),
                'icon' => 'CheckCircle',
                'color' => 'emerald',
            ];
        }

        // 3. Calls
        $calls = CrmCall::where('lead_id', $lead->id)->get();
        foreach ($calls as $c) {
            $events[] = [
                'type' => 'call_logged',
                'title' => ucfirst($c->call_type) . " Call ({$c->outcome})",
                'description' => $c->notes ?? "Call duration: {$c->duration_seconds}s",
                'date' => ($c->call_time ?? $c->created_at)->toISOString(),
                'icon' => 'PhoneCall',
                'color' => 'indigo',
            ];
        }

        // 4. Meetings
        $meetings = CrmMeeting::where('lead_id', $lead->id)->get();
        foreach ($meetings as $m) {
            $events[] = [
                'type' => 'meeting_held',
                'title' => "Meeting: {$m->title}",
                'description' => "Status: {$m->status} | {$m->location}",
                'date' => $m->created_at->toISOString(),
                'icon' => 'Calendar',
                'color' => 'amber',
            ];
        }

        // 5. Emails
        $emails = CrmEmail::where('lead_id', $lead->id)->get();
        foreach ($emails as $e) {
            $events[] = [
                'type' => 'email_sent',
                'title' => "Email: {$e->subject}",
                'description' => "To: {$e->to_email}",
                'date' => ($e->sent_at ?? $e->created_at)->toISOString(),
                'icon' => 'Mail',
                'color' => 'purple',
            ];
        }

        // 6. Notes
        $notes = CrmNote::where('notable_type', CrmLead::class)->where('notable_id', $lead->id)->get();
        foreach ($notes as $n) {
            $events[] = [
                'type' => 'note_added',
                'title' => $n->title ?? 'Note Recorded',
                'description' => $n->content,
                'date' => $n->created_at->toISOString(),
                'icon' => 'FileText',
                'color' => 'slate',
            ];
        }

        usort($events, fn($a, $b) => strcmp($b['date'], $a['date']));

        return $events;
    }
}

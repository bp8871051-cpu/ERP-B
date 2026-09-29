<?php

namespace App\Services;

use App\Models\CrmActivity;
use App\Models\CrmAuditLog;
use App\Models\CrmCall;
use App\Models\CrmContact;
use App\Models\CrmDeal;
use App\Models\CrmEmail;
use App\Models\CrmLead;
use App\Models\CrmMeeting;
use App\Models\CrmNote;
use App\Models\CrmTask;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\SalesOrder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ContactService
{
    public function list(array $params, ?int $companyId = null): LengthAwarePaginator
    {
        $query = CrmContact::query()
            ->with(['company', 'customer', 'owner', 'leadSource']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        // Search
        if (!empty($params['search'])) {
            $s = trim($params['search']);
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('company_name', 'like', "%{$s}%");
            });
        }

        // Status Filter
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
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

        if (in_array($sortBy, ['first_name', 'company_name', 'email', 'status', 'created_at', 'last_contacted_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->latest();
        }

        $perPage = max(5, min(100, (int) ($params['per_page'] ?? 15)));
        return $query->paginate($perPage);
    }

    public function getDetails(CrmContact $contact): array
    {
        $contact->load(['company', 'customer', 'owner', 'leadSource']);

        // Deals
        $deals = CrmDeal::where('contact_id', $contact->id)->with('stage')->latest()->get();
        $totalDeals = $deals->count();
        $totalRevenue = (float) $deals->where('status', 'won')->sum('value');

        // Activities
        $activities = CrmActivity::where('contact_id', $contact->id)->latest()->get();
        $tasks = CrmTask::where('contact_id', $contact->id)->with('assignedUser')->latest()->get();
        $emails = CrmEmail::where('contact_id', $contact->id)->latest()->get();
        $calls = CrmCall::where('contact_id', $contact->id)->latest()->get();
        $meetings = CrmMeeting::where('contact_id', $contact->id)->latest()->get();
        $notes = CrmNote::where('notable_type', CrmContact::class)->where('notable_id', $contact->id)->with('user')->latest()->get();

        // Linked Invoices & Sales Orders via Customer
        $invoices = [];
        $salesOrders = [];
        if ($contact->customer_id) {
            $invoices = Invoice::where('customer_id', $contact->customer_id)->latest()->take(10)->get();
            $salesOrders = SalesOrder::where('customer_id', $contact->customer_id)->latest()->take(10)->get();
        }

        // Construct Timeline
        $timeline = $this->buildTimeline($contact);

        $lastActivity = $activities->first() ?? $calls->first() ?? $emails->first();
        $nextActivity = CrmActivity::where('contact_id', $contact->id)
            ->where('status', 'pending')
            ->where('due_at', '>=', now())
            ->orderBy('due_at')
            ->first();

        return [
            'contact' => $contact,
            'stats' => [
                'total_deals' => $totalDeals,
                'total_revenue' => $totalRevenue,
                'open_deals_value' => (float) $deals->where('status', 'open')->sum('value'),
                'activities_count' => $activities->count(),
            ],
            'last_activity' => $lastActivity,
            'next_activity' => $nextActivity,
            'deals' => $deals,
            'activities' => $activities,
            'tasks' => $tasks,
            'emails' => $emails,
            'calls' => $calls,
            'meetings' => $meetings,
            'notes' => $notes,
            'invoices' => $invoices,
            'sales_orders' => $salesOrders,
            'timeline' => $timeline,
        ];
    }

    public function buildTimeline(CrmContact $contact): array
    {
        $events = [];

        // 1. Contact Created
        $events[] = [
            'type' => 'contact_created',
            'title' => 'Contact Created',
            'description' => "{$contact->full_name} was registered in the directory",
            'date' => $contact->created_at->toISOString(),
            'icon' => 'UserPlus',
            'color' => 'teal',
        ];

        // 2. Deals
        $deals = CrmDeal::where('contact_id', $contact->id)->get();
        foreach ($deals as $deal) {
            $events[] = [
                'type' => 'deal_created',
                'title' => "Deal Created: {$deal->name}",
                'description' => "Contract value: {$deal->currency} " . number_format($deal->value, 2),
                'date' => $deal->created_at->toISOString(),
                'icon' => 'Briefcase',
                'color' => 'blue',
            ];
            if ($deal->won_at) {
                $events[] = [
                    'type' => 'deal_won',
                    'title' => "Deal Won: {$deal->name}",
                    'description' => "Successfully closed deal for {$deal->currency} " . number_format($deal->value, 2),
                    'date' => $deal->won_at->toISOString(),
                    'icon' => 'Award',
                    'color' => 'emerald',
                ];
            }
        }

        // 3. Calls
        $calls = CrmCall::where('contact_id', $contact->id)->get();
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

        // 4. Emails
        $emails = CrmEmail::where('contact_id', $contact->id)->get();
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

        // 5. Meetings
        $meetings = CrmMeeting::where('contact_id', $contact->id)->get();
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

        // 6. Notes
        $notes = CrmNote::where('notable_type', CrmContact::class)->where('notable_id', $contact->id)->get();
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

        // Sort descending by date
        usort($events, fn($a, $b) => strcmp($b['date'], $a['date']));

        return $events;
    }
}

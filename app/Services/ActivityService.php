<?php

namespace App\Services;

use App\Models\CrmActivity;
use App\Models\CrmAuditLog;
use App\Models\CrmCall;
use App\Models\CrmEmail;
use App\Models\CrmMeeting;
use App\Models\CrmNote;
use App\Models\CrmTask;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityService
{
    public function listActivities(array $params, ?int $companyId = null): LengthAwarePaginator
    {
        $query = CrmActivity::query()
            ->with(['user', 'contact', 'lead', 'deal', 'customer']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (!empty($params['contact_id'])) {
            $query->where('contact_id', $params['contact_id']);
        }

        if (!empty($params['lead_id'])) {
            $query->where('lead_id', $params['lead_id']);
        }

        if (!empty($params['deal_id'])) {
            $query->where('deal_id', $params['deal_id']);
        }

        $perPage = max(5, min(100, (int) ($params['per_page'] ?? 15)));
        return $query->latest()->paginate($perPage);
    }

    public function createActivity(array $data, ?int $userId = null): CrmActivity
    {
        $activity = CrmActivity::create([
            'company_id' => $data['company_id'] ?? (auth()->user()?->company_id ?? 1),
            'user_id' => $data['user_id'] ?? $userId ?? auth()->id(),
            'type' => $data['type'],
            'subject' => $data['subject'],
            'description' => $data['description'] ?? null,
            'contact_id' => $data['contact_id'] ?? null,
            'lead_id' => $data['lead_id'] ?? null,
            'deal_id' => $data['deal_id'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'completed_at' => $data['completed_at'] ?? null,
            'status' => $data['status'] ?? 'pending',
        ]);

        CrmAuditLog::log(
            'Activity Created',
            CrmActivity::class,
            $activity->id,
            null,
            ['type' => $activity->type, 'subject' => $activity->subject],
            $activity->company_id,
            $userId
        );

        return $activity;
    }

    public function logCall(array $data, ?int $userId = null): CrmCall
    {
        $call = CrmCall::create([
            'company_id' => $data['company_id'] ?? (auth()->user()?->company_id ?? 1),
            'user_id' => $userId ?? auth()->id(),
            'contact_id' => $data['contact_id'] ?? null,
            'lead_id' => $data['lead_id'] ?? null,
            'deal_id' => $data['deal_id'] ?? null,
            'call_type' => $data['call_type'] ?? 'outgoing',
            'phone_number' => $data['phone_number'] ?? null,
            'duration_seconds' => (int) ($data['duration_seconds'] ?? 0),
            'call_time' => $data['call_time'] ?? now(),
            'outcome' => $data['outcome'] ?? 'connected',
            'notes' => $data['notes'] ?? null,
        ]);

        // Automatically create a corresponding CrmActivity record
        $this->createActivity([
            'company_id' => $call->company_id,
            'user_id' => $call->user_id,
            'type' => 'call',
            'subject' => ucfirst($call->call_type) . " Call - " . ($call->outcome ?? 'Completed'),
            'description' => $call->notes,
            'contact_id' => $call->contact_id,
            'lead_id' => $call->lead_id,
            'deal_id' => $call->deal_id,
            'status' => 'completed',
            'completed_at' => $call->call_time ?? now(),
        ], $userId);

        return $call;
    }

    public function scheduleMeeting(array $data, ?int $userId = null): CrmMeeting
    {
        $meeting = CrmMeeting::create([
            'company_id' => $data['company_id'] ?? (auth()->user()?->company_id ?? 1),
            'user_id' => $userId ?? auth()->id(),
            'contact_id' => $data['contact_id'] ?? null,
            'lead_id' => $data['lead_id'] ?? null,
            'deal_id' => $data['deal_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'meeting_date' => $data['meeting_date'],
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'location' => $data['location'] ?? 'Virtual Meet',
            'meeting_link' => $data['meeting_link'] ?? null,
            'participants' => $data['participants'] ?? [],
            'status' => $data['status'] ?? 'scheduled',
        ]);

        $this->createActivity([
            'company_id' => $meeting->company_id,
            'user_id' => $meeting->user_id,
            'type' => 'meeting',
            'subject' => "Meeting: " . $meeting->title,
            'description' => $meeting->description,
            'contact_id' => $meeting->contact_id,
            'lead_id' => $meeting->lead_id,
            'deal_id' => $meeting->deal_id,
            'due_at' => $meeting->meeting_date . ($meeting->start_time ? " {$meeting->start_time}" : " 10:00:00"),
            'status' => 'pending',
        ], $userId);

        return $meeting;
    }

    public function logEmail(array $data, ?int $userId = null): CrmEmail
    {
        $email = CrmEmail::create([
            'company_id' => $data['company_id'] ?? (auth()->user()?->company_id ?? 1),
            'user_id' => $userId ?? auth()->id(),
            'contact_id' => $data['contact_id'] ?? null,
            'lead_id' => $data['lead_id'] ?? null,
            'deal_id' => $data['deal_id'] ?? null,
            'from_email' => $data['from_email'] ?? (auth()->user()?->email ?? 'sales@falconerp.com'),
            'to_email' => $data['to_email'],
            'cc_email' => $data['cc_email'] ?? null,
            'bcc_email' => $data['bcc_email'] ?? null,
            'subject' => $data['subject'],
            'body_html' => $data['body_html'] ?? $data['body'] ?? '',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->createActivity([
            'company_id' => $email->company_id,
            'user_id' => $email->user_id,
            'type' => 'email',
            'subject' => "Email sent: " . $email->subject,
            'description' => "To: {$email->to_email}",
            'contact_id' => $email->contact_id,
            'lead_id' => $email->lead_id,
            'deal_id' => $email->deal_id,
            'status' => 'completed',
            'completed_at' => now(),
        ], $userId);

        return $email;
    }

    public function addNote(string $notableType, int $notableId, string $content, ?string $title = null, ?int $userId = null): CrmNote
    {
        return CrmNote::create([
            'company_id' => auth()->user()?->company_id ?? 1,
            'user_id' => $userId ?? auth()->id() ?? 1,
            'notable_type' => $notableType,
            'notable_id' => $notableId,
            'title' => $title,
            'content' => $content,
        ]);
    }
}

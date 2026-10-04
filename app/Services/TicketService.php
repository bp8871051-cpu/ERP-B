<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\SystemAuditLog;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketMessage;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketService
{
    protected TicketSlaService $slaService;

    public function __construct(TicketSlaService $slaService)
    {
        $this->slaService = $slaService;
    }

    /**
     * Get paginated tickets with enterprise filters
     */
    public function getTickets(array $filters = [], int $companyId = 1): LengthAwarePaginator
    {
        $query = Ticket::where('company_id', $companyId)
            ->with(['customer', 'agent', 'category', 'department', 'slaPolicy']);

        // Search
        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('ticket_number', 'like', "%{$s}%")
                    ->orWhere('subject', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhereHas('customer', function ($cq) use ($s) {
                        $cq->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%");
                    });
            });
        }

        // Status Filter
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Priority Filter
        if (!empty($filters['priority']) && $filters['priority'] !== 'all') {
            $query->where('priority', $filters['priority']);
        }

        // Category Filter
        if (!empty($filters['category_id']) && $filters['category_id'] !== 'all') {
            $query->where('category_id', $filters['category_id']);
        }

        // Agent Filter
        if (!empty($filters['assigned_agent_id']) && $filters['assigned_agent_id'] !== 'all') {
            $query->where('assigned_agent_id', $filters['assigned_agent_id']);
        }

        // Department Filter
        if (!empty($filters['department_id']) && $filters['department_id'] !== 'all') {
            $query->where('department_id', $filters['department_id']);
        }

        // SLA status filter
        if (!empty($filters['sla_status']) && $filters['sla_status'] !== 'all') {
            $query->where('sla_status', $filters['sla_status']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = min(100, max(5, (int) ($filters['per_page'] ?? 15)));
        return $query->paginate($perPage);
    }

    /**
     * Create a new ticket
     */
    public function createTicket(array $data, int $companyId = 1, ?int $userId = null): Ticket
    {
        return DB::transaction(function () use ($data, $companyId, $userId) {
            // Generate unique ticket number
            $ticketNumber = 'TCK-' . date('Y') . '-' . str_pad((string) (Ticket::where('company_id', $companyId)->count() + 1), 4, '0', STR_PAD_LEFT);

            // Handle Customer
            $customerId = $data['customer_id'] ?? null;
            if (!$customerId && !empty($data['customer_email'])) {
                $customer = Customer::firstOrCreate(
                    ['company_id' => $companyId, 'email' => $data['customer_email']],
                    [
                        'name' => $data['customer_name'] ?? 'Guest Customer',
                        'phone' => $data['customer_phone'] ?? null,
                        'status' => 'active',
                    ]
                );
                $customerId = $customer->id;
            }

            $ticket = Ticket::create([
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'assigned_agent_id' => $data['assigned_agent_id'] ?? $userId,
                'department_id' => $data['department_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'ticket_number' => $ticketNumber,
                'subject' => $data['subject'],
                'description' => $data['description'] ?? '',
                'priority' => $data['priority'] ?? 'medium',
                'status' => $data['status'] ?? 'open',
                'source' => $data['source'] ?? 'web',
                'tags' => $data['tags'] ?? [],
            ]);

            // Apply SLA
            $this->slaService->applySlaToTicket($ticket, $data['sla_policy_id'] ?? null);

            // Create Initial Message
            TicketMessage::create([
                'company_id' => $companyId,
                'ticket_id' => $ticket->id,
                'user_id' => $userId,
                'sender_type' => 'customer',
                'message_type' => 'reply',
                'message' => $data['description'] ?? 'Ticket created.',
                'attachment_name' => $data['attachment_name'] ?? null,
                'attachment_url' => $data['attachment_url'] ?? null,
                'is_internal' => false,
            ]);

            SystemAuditLog::log('support', 'create_ticket', (string) $ticket->id, null, $ticket->toArray(), $companyId, $userId);

            return $ticket->load(['customer', 'agent', 'category', 'department', 'slaPolicy']);
        });
    }

    /**
     * Get ticket details with complete conversation and related data
     */
    public function getTicketDetails(int $id, int $companyId = 1): Ticket
    {
        $ticket = Ticket::where('company_id', $companyId)
            ->with([
                'customer',
                'agent',
                'category',
                'department',
                'slaPolicy',
                'messages.user',
                'slaLogs',
            ])
            ->findOrFail($id);

        // Check and update real-time SLA breach if resolution deadline has passed and ticket is still open
        if ($ticket->resolution_due_at && Carbon::now()->isAfter($ticket->resolution_due_at) && !in_array($ticket->status, ['resolved', 'closed'])) {
            if ($ticket->sla_status !== 'breached') {
                $ticket->sla_status = 'breached';
                $ticket->save();
            }
        }

        return $ticket;
    }

    /**
     * Reply to a ticket or add internal note
     */
    public function addReply(int $ticketId, array $data, int $companyId = 1, ?int $userId = null): TicketMessage
    {
        $ticket = Ticket::where('company_id', $companyId)->findOrFail($ticketId);

        $isInternal = !empty($data['is_internal']);
        $messageType = $isInternal ? 'internal_note' : 'reply';

        $message = TicketMessage::create([
            'company_id' => $companyId,
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'sender_type' => $data['sender_type'] ?? 'agent',
            'message_type' => $messageType,
            'message' => $data['message'],
            'attachment_name' => $data['attachment_name'] ?? null,
            'attachment_url' => $data['attachment_url'] ?? null,
            'is_internal' => $isInternal,
        ]);

        // If it's a public agent reply, trigger first response SLA
        if (!$isInternal && ($data['sender_type'] ?? 'agent') === 'agent') {
            $this->slaService->recordFirstResponse($ticket);

            // If status was 'open' or 'new', change to 'in_progress' or requested status
            if (!empty($data['status'])) {
                $ticket->status = $data['status'];
            } elseif ($ticket->status === 'open') {
                $ticket->status = 'in_progress';
            }
            $ticket->save();
        }

        SystemAuditLog::log('support', $isInternal ? 'add_internal_note' : 'reply_ticket', (string) $ticket->id, null, ['message_id' => $message->id], $companyId, $userId);

        return $message->load('user');
    }

    /**
     * Assign ticket to an agent
     */
    public function assignTicket(int $ticketId, int $agentId, int $companyId = 1, ?int $userId = null): Ticket
    {
        $ticket = Ticket::where('company_id', $companyId)->findOrFail($ticketId);
        $agent = User::where('company_id', $companyId)->findOrFail($agentId);

        $oldAgentId = $ticket->assigned_agent_id;
        $ticket->assigned_agent_id = $agent->id;
        $ticket->save();

        TicketMessage::create([
            'company_id' => $companyId,
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'sender_type' => 'system',
            'message_type' => 'assignment',
            'message' => "Ticket reassigned to agent: {$agent->name}",
            'is_internal' => true,
        ]);

        SystemAuditLog::log('support', 'assign_ticket', (string) $ticket->id, ['assigned_agent_id' => $oldAgentId], ['assigned_agent_id' => $agent->id], $companyId, $userId);

        return $ticket->load(['agent', 'customer']);
    }

    /**
     * Update ticket status (e.g. resolve, close, in_progress, waiting_for_customer)
     */
    public function updateStatus(int $ticketId, string $status, int $companyId = 1, ?int $userId = null): Ticket
    {
        $ticket = Ticket::where('company_id', $companyId)->findOrFail($ticketId);
        $oldStatus = $ticket->status;
        $ticket->status = $status;

        if ($status === 'resolved') {
            $this->slaService->recordResolution($ticket);
        } elseif ($status === 'closed') {
            $ticket->closed_at = Carbon::now();
        } elseif ($status === 'waiting_for_customer') {
            $this->slaService->pauseSla($ticket, 'Waiting for client feedback');
        } elseif ($oldStatus === 'waiting_for_customer' && $status !== 'waiting_for_customer') {
            $this->slaService->resumeSla($ticket);
        }

        $ticket->save();

        TicketMessage::create([
            'company_id' => $companyId,
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'sender_type' => 'system',
            'message_type' => 'status_change',
            'message' => "Ticket status updated from '{$oldStatus}' to '{$status}'",
            'is_internal' => false,
        ]);

        SystemAuditLog::log('support', 'update_ticket_status', (string) $ticket->id, ['status' => $oldStatus], ['status' => $status], $companyId, $userId);

        return $ticket->load(['customer', 'agent', 'slaPolicy']);
    }

    /**
     * Merge ticket into another ticket
     */
    public function mergeTickets(int $sourceTicketId, int $targetTicketId, int $companyId = 1, ?int $userId = null): Ticket
    {
        $source = Ticket::where('company_id', $companyId)->findOrFail($sourceTicketId);
        $target = Ticket::where('company_id', $companyId)->findOrFail($targetTicketId);

        // Move all messages from source to target
        TicketMessage::where('ticket_id', $source->id)->update(['ticket_id' => $target->id]);

        $source->status = 'closed';
        $source->merged_into_ticket_id = $target->id;
        $source->save();

        TicketMessage::create([
            'company_id' => $companyId,
            'ticket_id' => $target->id,
            'user_id' => $userId,
            'sender_type' => 'system',
            'message_type' => 'internal_note',
            'message' => "Merged conversation from Ticket #{$source->ticket_number} ('{$source->subject}').",
            'is_internal' => true,
        ]);

        SystemAuditLog::log('support', 'merge_tickets', (string) $target->id, null, ['source_ticket_id' => $source->id], $companyId, $userId);

        return $target->load(['customer', 'agent', 'messages']);
    }

    /**
     * Convert ticket resolution into a Knowledge Base article
     */
    public function convertToKnowledgeBase(int $ticketId, array $data, int $companyId = 1, ?int $userId = null): KnowledgeBaseArticle
    {
        $ticket = Ticket::where('company_id', $companyId)->findOrFail($ticketId);

        $category = KnowledgeBaseCategory::where('company_id', $companyId)->find($data['category_id'] ?? null)
            ?? KnowledgeBaseCategory::firstOrCreate(
                ['company_id' => $companyId, 'slug' => 'technical-support'],
                ['name' => 'Technical Support', 'icon' => 'Wrench']
            );

        $title = $data['title'] ?? 'Resolution: ' . $ticket->subject;
        $slug = Str::slug($title) . '-' . rand(100, 999);

        $article = KnowledgeBaseArticle::create([
            'company_id' => $companyId,
            'category_id' => $category->id,
            'author_id' => $userId,
            'title' => $title,
            'slug' => $slug,
            'description' => $data['description'] ?? 'Article created from Ticket #' . $ticket->ticket_number,
            'content' => $data['content'] ?? ($ticket->description . "\n\n### Solution\n" . ($ticket->messages->last()?->message ?? 'No details provided.')),
            'status' => 'published',
            'visibility' => 'public',
            'tags' => $ticket->tags ?? ['support-resolution'],
            'published_at' => Carbon::now(),
        ]);

        SystemAuditLog::log('support', 'ticket_to_kb_article', (string) $ticket->id, null, ['article_id' => $article->id], $companyId, $userId);

        return $article;
    }
}

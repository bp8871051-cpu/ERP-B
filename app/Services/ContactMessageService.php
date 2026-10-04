<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SupportContactMessage;
use App\Models\SystemAuditLog;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ContactMessageService
{
    protected TicketService $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    /**
     * Get paginated contact messages
     */
    public function getMessages(array $filters = [], int $companyId = 1): LengthAwarePaginator
    {
        $query = SupportContactMessage::where('company_id', $companyId)
            ->with(['assignedAgent', 'ticket']);

        if (!empty($filters['search'])) {
            $s = trim($filters['search']);
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%")
                    ->orWhere('subject', 'like', "%{$s}%")
                    ->orWhere('message', 'like', "%{$s}%");
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['source']) && $filters['source'] !== 'all') {
            $query->where('source', $filters['source']);
        }

        $perPage = min(100, max(5, (int) ($filters['per_page'] ?? 15)));
        return $query->latest()->paginate($perPage);
    }

    /**
     * Store new contact message
     */
    public function storeMessage(array $data, int $companyId = 1): SupportContactMessage
    {
        $message = SupportContactMessage::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'source' => $data['source'] ?? 'website',
            'status' => 'new',
            'assigned_to_user_id' => $data['assigned_to_user_id'] ?? null,
        ]);

        SystemAuditLog::log('support', 'create_contact_message', (string) $message->id, null, $message->toArray(), $companyId);

        return $message;
    }

    /**
     * Reply to contact message
     */
    public function replyMessage(int $id, string $replyContent, int $companyId = 1, ?int $userId = null): SupportContactMessage
    {
        $msg = SupportContactMessage::where('company_id', $companyId)->findOrFail($id);
        $msg->reply_message = $replyContent;
        $msg->replied_at = Carbon::now();
        $msg->status = 'replied';
        $msg->save();

        SystemAuditLog::log('support', 'reply_contact_message', (string) $msg->id, null, ['reply_preview' => substr($replyContent, 0, 100)], $companyId, $userId);

        return $msg;
    }

    /**
     * Convert contact message to ticket without creating duplicate customers
     */
    public function convertToTicket(int $id, array $ticketOptions = [], int $companyId = 1, ?int $userId = null): Ticket
    {
        return DB::transaction(function () use ($id, $ticketOptions, $companyId, $userId) {
            $msg = SupportContactMessage::where('company_id', $companyId)->findOrFail($id);

            // Re-use existing customer if email matches, or create one cleanly
            $customer = Customer::where('company_id', $companyId)
                ->where('email', $msg->email)
                ->first();

            if (!$customer) {
                $customer = Customer::create([
                    'company_id' => $companyId,
                    'name' => $msg->name,
                    'email' => $msg->email,
                    'phone' => $msg->phone,
                    'status' => 'active',
                ]);
            }

            // Create ticket via TicketService
            $ticket = $this->ticketService->createTicket([
                'customer_id' => $customer->id,
                'assigned_agent_id' => $ticketOptions['assigned_agent_id'] ?? $msg->assigned_to_user_id ?? $userId,
                'category_id' => $ticketOptions['category_id'] ?? null,
                'sla_policy_id' => $ticketOptions['sla_policy_id'] ?? null,
                'subject' => $msg->subject,
                'description' => "Message received from: {$msg->name} ({$msg->email})\nSource: {$msg->source}\n\n" . $msg->message,
                'priority' => $ticketOptions['priority'] ?? 'medium',
                'source' => $msg->source,
                'tags' => ['contact-form-conversion'],
            ], $companyId, $userId);

            // Update contact message state
            $msg->status = 'converted';
            $msg->ticket_id = $ticket->id;
            $msg->save();

            SystemAuditLog::log('support', 'convert_message_to_ticket', (string) $msg->id, null, ['ticket_id' => $ticket->id], $companyId, $userId);

            return $ticket;
        });
    }

    /**
     * Update message status (archive, mark read, spam)
     */
    public function updateStatus(int $id, string $status, int $companyId = 1): SupportContactMessage
    {
        $msg = SupportContactMessage::where('company_id', $companyId)->findOrFail($id);
        $msg->status = $status;
        $msg->save();
        return $msg;
    }

    /**
     * Delete contact message
     */
    public function deleteMessage(int $id, int $companyId = 1): bool
    {
        $msg = SupportContactMessage::where('company_id', $companyId)->findOrFail($id);
        return $msg->delete();
    }
}

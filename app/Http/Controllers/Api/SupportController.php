<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportSlaPolicy;
use App\Services\ContactMessageService;
use App\Services\KnowledgeBaseService;
use App\Services\SupportService;
use App\Services\TicketService;
use App\Services\TicketSlaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    protected SupportService $supportService;
    protected TicketService $ticketService;
    protected TicketSlaService $slaService;
    protected ContactMessageService $contactService;
    protected KnowledgeBaseService $kbService;

    public function __construct(
        SupportService $supportService,
        TicketService $ticketService,
        TicketSlaService $slaService,
        ContactMessageService $contactService,
        KnowledgeBaseService $kbService
    ) {
        $this->supportService = $supportService;
        $this->ticketService = $ticketService;
        $this->slaService = $slaService;
        $this->contactService = $contactService;
        $this->kbService = $kbService;
    }

    protected function getCompanyId(Request $request): int
    {
        return $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : 1;
    }

    public function dashboard(Request $request): JsonResponse
    {
        $data = $this->supportService->getDashboardData($this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    // --- Contact Messages ---
    public function contactMessages(Request $request): JsonResponse
    {
        $messages = $this->contactService->getMessages($request->all(), $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $messages]);
    }

    public function storeContactMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'source' => 'nullable|string',
        ]);

        $message = $this->contactService->storeMessage($validated, $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $message, 'message' => 'Message submitted successfully.']);
    }

    public function replyContactMessage(Request $request, int $id): JsonResponse
    {
        $request->validate(['reply_message' => 'required|string']);
        $msg = $this->contactService->replyMessage($id, $request->input('reply_message'), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $msg, 'message' => 'Reply dispatched to contact.']);
    }

    public function convertContactMessage(Request $request, int $id): JsonResponse
    {
        $ticket = $this->contactService->convertToTicket($id, $request->all(), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $ticket, 'message' => 'Message converted to support ticket #' . $ticket->ticket_number]);
    }

    // --- Tickets ---
    public function tickets(Request $request): JsonResponse
    {
        $tickets = $this->ticketService->getTickets($request->all(), $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $tickets]);
    }

    public function storeTicket(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'nullable|string',
            'customer_id' => 'nullable|integer',
            'customer_email' => 'nullable|email',
            'customer_name' => 'nullable|string',
            'category_id' => 'nullable|integer',
            'department_id' => 'nullable|integer',
            'assigned_agent_id' => 'nullable|integer',
            'sla_policy_id' => 'nullable|integer',
            'tags' => 'nullable|array',
        ]);

        $ticket = $this->ticketService->createTicket($validated, $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $ticket, 'message' => 'Ticket created successfully.']);
    }

    public function showTicket(Request $request, int $id): JsonResponse
    {
        $ticket = $this->ticketService->getTicketDetails($id, $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $ticket]);
    }

    public function replyTicket(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'is_internal' => 'nullable|boolean',
            'sender_type' => 'nullable|string',
            'status' => 'nullable|string',
            'attachment_name' => 'nullable|string',
            'attachment_url' => 'nullable|string',
        ]);

        $msg = $this->ticketService->addReply($id, $validated, $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $msg, 'message' => 'Reply posted successfully.']);
    }

    public function assignTicket(Request $request, int $id): JsonResponse
    {
        $request->validate(['agent_id' => 'required|integer']);
        $ticket = $this->ticketService->assignTicket($id, (int) $request->input('agent_id'), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $ticket, 'message' => 'Ticket reassigned successfully.']);
    }

    public function updateTicketStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['status' => 'required|string']);
        $ticket = $this->ticketService->updateStatus($id, $request->input('status'), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $ticket, 'message' => 'Ticket status updated to ' . $request->input('status')]);
    }

    public function mergeTicket(Request $request, int $id): JsonResponse
    {
        $request->validate(['target_ticket_id' => 'required|integer']);
        $target = $this->ticketService->mergeTickets($id, (int) $request->input('target_ticket_id'), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $target, 'message' => 'Ticket conversation merged successfully.']);
    }

    public function convertTicketToKb(Request $request, int $id): JsonResponse
    {
        $article = $this->ticketService->convertToKnowledgeBase($id, $request->all(), $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $article, 'message' => 'Resolution published to Knowledge Base.']);
    }

    // --- Knowledge Base ---
    public function knowledgeBase(Request $request): JsonResponse
    {
        $articles = $this->kbService->getArticles($request->all(), $this->getCompanyId($request));
        $categories = $this->kbService->getCategories($this->getCompanyId($request));
        return response()->json([
            'status' => 'success',
            'data' => [
                'articles' => $articles,
                'categories' => $categories,
            ],
        ]);
    }

    public function showKbArticle(Request $request, int $id): JsonResponse
    {
        $article = $this->kbService->getArticle($id, $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $article]);
    }

    public function voteKbArticle(Request $request, int $id): JsonResponse
    {
        $isHelpful = filter_var($request->input('is_helpful', true), FILTER_VALIDATE_BOOLEAN);
        $article = $this->kbService->voteArticle($id, $isHelpful, $this->getCompanyId($request));
        return response()->json(['status' => 'success', 'data' => $article]);
    }

    public function storeKbArticle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|integer',
            'content' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'visibility' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        $article = $this->kbService->storeArticle($validated, $this->getCompanyId($request), $request->user()?->id);
        return response()->json(['status' => 'success', 'data' => $article, 'message' => 'Article published successfully.']);
    }

    // --- SLA Management ---
    public function slaPolicies(Request $request): JsonResponse
    {
        $policies = SupportSlaPolicy::where('company_id', $this->getCompanyId($request))->get();
        return response()->json(['status' => 'success', 'data' => $policies]);
    }

    public function storeSlaPolicy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|string|in:urgent,high,medium,low',
            'first_response_target_minutes' => 'required|integer|min:1',
            'resolution_target_minutes' => 'required|integer|min:1',
            'business_hours' => 'nullable|boolean',
        ]);

        $policy = SupportSlaPolicy::create(array_merge($validated, ['company_id' => $this->getCompanyId($request)]));
        return response()->json(['status' => 'success', 'data' => $policy, 'message' => 'SLA Policy created.']);
    }
}

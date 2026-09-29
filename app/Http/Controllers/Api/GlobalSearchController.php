<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Conversation;
use App\Models\Email;
use App\Models\FileItem;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use App\Models\WorkflowRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->query('q', ''));
        if (strlen($query) < 2) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'tasks' => [],
                    'chat' => [],
                    'emails' => [],
                    'files' => [],
                    'notes' => [],
                    'events' => [],
                    'workflows' => [],
                    'contacts' => [],
                ],
            ]);
        }

        $user = $request->user();
        $companyId = $user->company_id ?: 1;

        // 1. Tasks
        $tasks = Task::where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('category', 'like', "%{$query}%");
            })
            ->take(5)
            ->get(['id', 'title', 'priority', 'status', 'category'])
            ->map(fn($t) => [
                'id' => $t->id,
                'title' => $t->title,
                'subtitle' => ucfirst($t->status) . ' • ' . ucfirst($t->priority),
                'category' => 'Tasks',
                'link' => '/app/applications/todo',
            ]);

        // 2. Chat Conversations
        $conversations = Conversation::where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhereHas('messages', fn($mq) => $mq->where('body', 'like', "%{$query}%"));
            })
            ->take(5)
            ->get(['id', 'title', 'type'])
            ->map(fn($c) => [
                'id' => $c->id,
                'title' => $c->title ?: 'Direct Chat',
                'subtitle' => ucfirst($c->type) . ' Conversation',
                'category' => 'Chat',
                'link' => '/app/applications/chat',
            ]);

        // 3. Emails
        $emails = Email::where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('subject', 'like', "%{$query}%")
                    ->orWhere('from_name', 'like', "%{$query}%")
                    ->orWhere('body_text', 'like', "%{$query}%");
            })
            ->take(5)
            ->get(['id', 'subject', 'from_name', 'folder'])
            ->map(fn($e) => [
                'id' => $e->id,
                'title' => $e->subject,
                'subtitle' => 'From: ' . $e->from_name . ' • ' . ucfirst($e->folder),
                'category' => 'Email',
                'link' => '/app/applications/email',
            ]);

        // 4. Files
        $files = FileItem::where('company_id', $companyId)
            ->where('name', 'like', "%{$query}%")
            ->take(5)
            ->get(['id', 'name', 'file_type', 'file_size'])
            ->map(fn($f) => [
                'id' => $f->id,
                'title' => $f->name,
                'subtitle' => strtoupper($f->file_type) . ' • ' . round($f->file_size / 1024) . ' KB',
                'category' => 'Files',
                'link' => '/app/applications/files',
            ]);

        // 5. Notes
        $notes = Note::where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%");
            })
            ->take(5)
            ->get(['id', 'title', 'content'])
            ->map(fn($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'subtitle' => substr(strip_tags($n->content ?: ''), 0, 60) . '...',
                'category' => 'Notes',
                'link' => '/app/applications/notes',
            ]);

        // 6. Calendar Events
        $events = CalendarEvent::where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhere('location', 'like', "%{$query}%");
            })
            ->take(5)
            ->get(['id', 'title', 'start_time', 'location'])
            ->map(fn($ev) => [
                'id' => $ev->id,
                'title' => $ev->title,
                'subtitle' => $ev->start_time->format('M d, Y h:i A') . ($ev->location ? ' (' . $ev->location . ')' : ''),
                'category' => 'Calendar',
                'link' => '/app/applications/calendar',
            ]);

        // 7. Workflow Requests
        $workflows = WorkflowRequest::where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('reference_number', 'like', "%{$query}%")
                    ->orWhere('module', 'like', "%{$query}%");
            })
            ->take(5)
            ->get(['id', 'reference_number', 'title', 'status', 'module'])
            ->map(fn($w) => [
                'id' => $w->id,
                'title' => $w->reference_number . ' - ' . $w->title,
                'subtitle' => $w->module . ' • ' . ucfirst($w->status),
                'category' => 'Workflows',
                'link' => '/app/applications/workflows',
            ]);

        // 8. Contacts
        $contacts = User::where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('role', 'like', "%{$query}%");
            })
            ->take(5)
            ->get(['id', 'name', 'email', 'role', 'phone'])
            ->map(fn($u) => [
                'id' => $u->id,
                'title' => $u->name,
                'subtitle' => $u->role . ' • ' . $u->email,
                'category' => 'Contacts',
                'link' => '/app/applications/calls',
            ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'tasks' => $tasks,
                'chat' => $conversations,
                'emails' => $emails,
                'files' => $files,
                'notes' => $notes,
                'events' => $events,
                'workflows' => $workflows,
                'contacts' => $contacts,
            ],
        ]);
    }
}

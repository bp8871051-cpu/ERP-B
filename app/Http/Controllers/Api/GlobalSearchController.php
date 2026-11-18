<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use App\Models\Conversation;
use App\Models\Task;
use App\Models\User;
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
                    'events' => [],
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

        // 3. Calendar Events
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

        // 4. Contacts / Team
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
                'link' => '/app/system/users',
            ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'tasks' => $tasks,
                'chat' => $conversations,
                'events' => $events,
                'contacts' => $contacts,
            ],
        ]);
    }
}


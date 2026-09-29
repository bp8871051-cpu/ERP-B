<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskComment;
use App\Models\TaskLabel;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function getTasks(User $user, ?string $view = 'all', ?string $status = null, ?string $priority = null, ?string $search = null)
    {
        $companyId = $user->company_id ?: 1;

        $query = Task::where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->with([
                'assignedUser:id,name,email,avatar,role',
                'reporter:id,name,email,avatar',
                'checklists',
                'comments.user:id,name,avatar',
                'labels',
                'project:id,name',
            ]);

        // Views
        if ($view === 'my_tasks') {
            $query->where('assigned_to', $user->id);
        } elseif ($view === 'today') {
            $query->whereDate('due_date', Carbon::today());
        } elseif ($view === 'upcoming') {
            $query->whereDate('due_date', '>', Carbon::today());
        } elseif ($view === 'completed') {
            $query->where('status', 'completed');
        } elseif ($view === 'overdue') {
            $query->whereDate('due_date', '<', Carbon::today())->where('status', '!=', 'completed');
        }

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($priority && $priority !== 'all') {
            $query->where('priority', $priority);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $allTasks = $query->orderBy('order', 'asc')->orderBy('due_date', 'asc')->get()->map(function ($t) {
            $checklistTotal = $t->checklists->count();
            $checklistDone = $t->checklists->where('is_completed', true)->count();
            $isOverdue = $t->due_date && $t->due_date->isPast() && $t->status !== 'completed';

            return [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'priority' => $t->priority,
                'status' => $t->status,
                'category' => $t->category ?: 'General',
                'project' => $t->project ? ['id' => $t->project->id, 'name' => $t->project->name] : null,
                'startDate' => $t->start_date ? $t->start_date->format('Y-m-d') : null,
                'dueDate' => $t->due_date ? $t->due_date->format('Y-m-d') : null,
                'dueDateFormatted' => $t->due_date ? $t->due_date->format('M d, Y') : null,
                'isOverdue' => $isOverdue,
                'assignee' => $t->assignedUser ? [
                    'id' => $t->assignedUser->id,
                    'name' => $t->assignedUser->name,
                    'avatar' => $t->assignedUser->avatar,
                    'role' => $t->assignedUser->role,
                ] : null,
                'reporter' => $t->reporter ? [
                    'id' => $t->reporter->id,
                    'name' => $t->reporter->name,
                    'avatar' => $t->reporter->avatar,
                ] : null,
                'checklists' => $t->checklists->map(fn($c) => [
                    'id' => $c->id,
                    'title' => $c->title,
                    'isCompleted' => (bool) $c->is_completed,
                ]),
                'checklistProgress' => [
                    'total' => $checklistTotal,
                    'completed' => $checklistDone,
                    'percent' => $checklistTotal > 0 ? round(($checklistDone / $checklistTotal) * 100) : 0,
                ],
                'commentsCount' => $t->comments->count(),
                'comments' => $t->comments->map(fn($comm) => [
                    'id' => $comm->id,
                    'user' => [
                        'name' => $comm->user?->name ?? 'User',
                        'avatar' => $comm->user?->avatar,
                    ],
                    'comment' => $comm->comment,
                    'createdAt' => $comm->created_at->diffForHumans(),
                ]),
                'labels' => $t->labels->map(fn($l) => [
                    'id' => $l->id,
                    'name' => $l->name,
                    'color' => $l->color,
                ]),
                'order' => $t->order,
            ];
        });

        // Group into Kanban columns
        $kanban = [
            'todo' => $allTasks->where('status', 'todo')->values(),
            'in_progress' => $allTasks->where('status', 'in_progress')->values(),
            'review' => $allTasks->where('status', 'review')->values(),
            'completed' => $allTasks->where('status', 'completed')->values(),
        ];

        // Counts
        $counts = [
            'all' => Task::where('company_id', $companyId)->count(),
            'my_tasks' => Task::where('company_id', $companyId)->where('assigned_to', $user->id)->count(),
            'today' => Task::where('company_id', $companyId)->whereDate('due_date', Carbon::today())->count(),
            'upcoming' => Task::where('company_id', $companyId)->whereDate('due_date', '>', Carbon::today())->count(),
            'completed' => Task::where('company_id', $companyId)->where('status', 'completed')->count(),
            'overdue' => Task::where('company_id', $companyId)->whereDate('due_date', '<', Carbon::today())->where('status', '!=', 'completed')->count(),
        ];

        $teamUsers = User::where('company_id', $companyId)
            ->select('id', 'name', 'avatar', 'role', 'email')
            ->get();

        return [
            'tasks' => $allTasks,
            'kanban' => $kanban,
            'counts' => $counts,
            'team' => $teamUsers,
        ];
    }

    public function createTask(User $user, array $data)
    {
        $companyId = $user->company_id ?: 1;

        return DB::transaction(function () use ($companyId, $user, $data) {
            $task = Task::create([
                'company_id' => $companyId,
                'project_id' => $data['project_id'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? $user->id,
                'reporter_id' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'status' => $data['status'] ?? 'todo',
                'category' => $data['category'] ?? 'General',
                'start_date' => !empty($data['start_date']) ? Carbon::parse($data['start_date']) : null,
                'due_date' => !empty($data['due_date']) ? Carbon::parse($data['due_date']) : null,
                'order' => Task::where('company_id', $companyId)->max('order') + 1,
            ]);

            if (!empty($data['checklist']) && is_array($data['checklist'])) {
                foreach ($data['checklist'] as $item) {
                    $itemTitle = is_string($item) ? $item : ($item['title'] ?? '');
                    if ($itemTitle) {
                        TaskChecklist::create([
                            'task_id' => $task->id,
                            'title' => $itemTitle,
                            'is_completed' => false,
                        ]);
                    }
                }
            }

            if (!empty($data['labels']) && is_array($data['labels'])) {
                foreach ($data['labels'] as $label) {
                    $lbl = is_string($label) ? $label : ($label['name'] ?? '');
                    if ($lbl) {
                        TaskLabel::create([
                            'task_id' => $task->id,
                            'name' => $lbl,
                            'color' => is_array($label) ? ($label['color'] ?? '#0F8B7A') : '#0F8B7A',
                        ]);
                    }
                }
            }

            return $task->load(['assignedUser', 'reporter', 'checklists', 'comments', 'labels']);
        });
    }

    public function updateTask(int $id, User $user, array $data)
    {
        $task = Task::findOrFail($id);

        if (isset($data['due_date'])) {
            $data['due_date'] = $data['due_date'] ? Carbon::parse($data['due_date']) : null;
        }
        if (isset($data['start_date'])) {
            $data['start_date'] = $data['start_date'] ? Carbon::parse($data['start_date']) : null;
        }

        $task->update($data);

        return $task->load(['assignedUser', 'reporter', 'checklists', 'comments', 'labels']);
    }

    public function deleteTask(int $id, User $user)
    {
        $task = Task::findOrFail($id);
        $task->delete();
        return ['success' => true];
    }

    public function addComment(int $id, User $user, string $comment)
    {
        $comm = TaskComment::create([
            'task_id' => $id,
            'user_id' => $user->id,
            'comment' => $comment,
        ]);

        return $comm->load('user');
    }

    public function toggleChecklist(int $taskId, int $checklistId)
    {
        $chk = TaskChecklist::where('task_id', $taskId)->where('id', $checklistId)->firstOrFail();
        $chk->is_completed = !$chk->is_completed;
        $chk->save();
        return ['isCompleted' => $chk->is_completed];
    }
}

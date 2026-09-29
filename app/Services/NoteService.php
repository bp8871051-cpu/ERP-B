<?php

namespace App\Services;

use App\Models\Note;
use App\Models\NoteAttachment;
use App\Models\NoteShare;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NoteService
{
    public function getNotes(User $user, ?string $filter = null, ?string $tag = null, ?string $search = null)
    {
        $companyId = $user->company_id ?: 1;

        $query = Note::where('company_id', $companyId)
            ->with(['tags', 'attachments', 'user:id,name,avatar'])
            ->orderBy('is_pinned', 'desc')
            ->orderBy('updated_at', 'desc');

        if ($filter === 'pinned') {
            $query->where('is_pinned', true);
        } elseif ($filter === 'favorites') {
            $query->where('is_favorite', true);
        } elseif ($filter === 'archived') {
            $query->where('is_archived', true);
        } else {
            $query->where('is_archived', false);
        }

        if ($tag) {
            $query->whereHas('tags', fn($q) => $q->where('name', $tag));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
                    ->orWhereHas('tags', fn($tq) => $tq->where('name', 'like', "%{$search}%"));
            });
        }

        $notes = $query->get()->map(function ($n) {
            return [
                'id' => $n->id,
                'title' => $n->title,
                'content' => $n->content,
                'checklist' => $n->checklist ?: [],
                'color' => $n->color ?: '#ffffff',
                'isPinned' => (bool) $n->is_pinned,
                'isArchived' => (bool) $n->is_archived,
                'isFavorite' => (bool) $n->is_favorite,
                'owner' => [
                    'id' => $n->user?->id,
                    'name' => $n->user?->name ?? 'User',
                    'avatar' => $n->user?->avatar,
                ],
                'tags' => $n->tags->map(fn($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'color' => $t->color,
                ]),
                'attachments' => $n->attachments->map(fn($a) => [
                    'id' => $a->id,
                    'name' => $a->file_name,
                    'path' => $a->file_path,
                    'size' => $a->file_size,
                ]),
                'updatedAt' => $n->updated_at->format('M d, Y h:i A'),
                'timeAgo' => $n->updated_at->diffForHumans(),
            ];
        });

        $tags = Tag::where('company_id', $companyId)->get();

        return [
            'notes' => $notes,
            'tags' => $tags,
            'counts' => [
                'total' => Note::where('company_id', $companyId)->where('is_archived', false)->count(),
                'pinned' => Note::where('company_id', $companyId)->where('is_pinned', true)->count(),
                'favorites' => Note::where('company_id', $companyId)->where('is_favorite', true)->count(),
                'archived' => Note::where('company_id', $companyId)->where('is_archived', true)->count(),
            ],
        ];
    }

    public function createNote(User $user, array $data)
    {
        $companyId = $user->company_id ?: 1;

        return DB::transaction(function () use ($companyId, $user, $data) {
            $note = Note::create([
                'company_id' => $companyId,
                'user_id' => $user->id,
                'title' => $data['title'] ?? 'Untitled Note',
                'content' => $data['content'] ?? null,
                'checklist' => $data['checklist'] ?? [],
                'color' => $data['color'] ?? '#ffffff',
                'is_pinned' => $data['is_pinned'] ?? false,
                'is_archived' => false,
                'is_favorite' => $data['is_favorite'] ?? false,
            ]);

            if (!empty($data['tags']) && is_array($data['tags'])) {
                $tagIds = [];
                foreach ($data['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['company_id' => $companyId, 'name' => trim($tagName)],
                        ['color' => '#0F8B7A']
                    );
                    $tagIds[] = $tag->id;
                }
                $note->tags()->sync($tagIds);
            }

            return $note->load(['tags', 'attachments', 'user']);
        });
    }

    public function updateNote(int $id, User $user, array $data)
    {
        $note = Note::findOrFail($id);

        return DB::transaction(function () use ($note, $data) {
            $note->update($data);

            if (isset($data['tags']) && is_array($data['tags'])) {
                $tagIds = [];
                foreach ($data['tags'] as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['company_id' => $note->company_id, 'name' => trim($tagName)],
                        ['color' => '#0F8B7A']
                    );
                    $tagIds[] = $tag->id;
                }
                $note->tags()->sync($tagIds);
            }

            return $note->load(['tags', 'attachments', 'user']);
        });
    }

    public function deleteNote(int $id, User $user)
    {
        $note = Note::findOrFail($id);
        $note->delete();
        return ['success' => true];
    }

    public function togglePin(int $id, User $user)
    {
        $note = Note::findOrFail($id);
        $note->is_pinned = !$note->is_pinned;
        $note->save();
        return ['isPinned' => $note->is_pinned];
    }

    public function toggleFavorite(int $id, User $user)
    {
        $note = Note::findOrFail($id);
        $note->is_favorite = !$note->is_favorite;
        $note->save();
        return ['isFavorite' => $note->is_favorite];
    }

    public function toggleArchive(int $id, User $user)
    {
        $note = Note::findOrFail($id);
        $note->is_archived = !$note->is_archived;
        $note->save();
        return ['isArchived' => $note->is_archived];
    }
}

<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageReaction;
use App\Models\MessageRead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChatService
{
    public function getConversations(User $user, ?string $search = null)
    {
        $companyId = $user->company_id ?: 1;

        $query = Conversation::where('company_id', $companyId)
            ->whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->with([
                'participants.user:id,name,email,avatar,role',
                'latestMessage.sender:id,name,avatar',
                'latestMessage.attachments',
            ])
            ->orderBy('last_message_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('participants.user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $conversations = $query->get()->map(function ($conv) use ($user) {
            $myParticipant = $conv->participants->firstWhere('user_id', $user->id);
            $lastReadId = $myParticipant?->last_read_message_id ?? 0;

            $unreadCount = Message::where('conversation_id', $conv->id)
                ->where('id', '>', $lastReadId)
                ->where('user_id', '!=', $user->id)
                ->count();

            // Determine conversation display title & avatar
            $otherParticipant = $conv->participants->firstWhere('user_id', '!=', $user->id);
            $displayName = $conv->type === 'group'
                ? ($conv->title ?: 'Group Discussion')
                : ($otherParticipant?->user?->name ?? 'Internal Chat');

            $displayAvatar = $conv->type === 'group'
                ? $conv->avatar
                : ($otherParticipant?->user?->avatar ?? 'https://i.pravatar.cc/150?u=chat');

            $isOnline = $otherParticipant ? (bool) ($otherParticipant->user->is_active ?? true) : false;

            return [
                'id' => $conv->id,
                'type' => $conv->type,
                'title' => $displayName,
                'avatar' => $displayAvatar,
                'isOnline' => $isOnline,
                'isPinned' => (bool) ($myParticipant?->is_pinned ?? false),
                'isMuted' => (bool) ($myParticipant?->is_muted ?? false),
                'unreadCount' => $unreadCount,
                'lastMessage' => $conv->latestMessage ? [
                    'id' => $conv->latestMessage->id,
                    'body' => $conv->latestMessage->body,
                    'type' => $conv->latestMessage->type,
                    'sender' => $conv->latestMessage->sender?->name ?? 'User',
                    'time' => $conv->latestMessage->created_at->diffForHumans(null, true, true),
                    'created_at' => $conv->latestMessage->created_at->toIso8601String(),
                ] : null,
                'participants' => $conv->participants->map(function ($p) {
                    return [
                        'id' => $p->user->id,
                        'name' => $p->user->name,
                        'email' => $p->user->email,
                        'avatar' => $p->user->avatar,
                        'role' => $p->role,
                        'isOnline' => (bool) ($p->user->is_active ?? true),
                    ];
                }),
                'created_at' => $conv->created_at->toIso8601String(),
            ];
        });

        return $conversations;
    }

    public function createConversation(User $user, array $data)
    {
        $companyId = $user->company_id ?: 1;
        $type = $data['type'] ?? 'direct';
        $participantIds = array_unique(array_merge([$user->id], $data['participant_ids'] ?? []));

        // If direct, check if one already exists between these 2 users
        if ($type === 'direct' && count($participantIds) === 2) {
            $otherId = $participantIds[0] === $user->id ? $participantIds[1] : $participantIds[0];
            $existing = Conversation::where('company_id', $companyId)
                ->where('type', 'direct')
                ->whereHas('participants', fn($q) => $q->where('user_id', $user->id))
                ->whereHas('participants', fn($q) => $q->where('user_id', $otherId))
                ->first();

            if ($existing) {
                return $this->getConversationDetails($existing->id, $user);
            }
        }

        return DB::transaction(function () use ($companyId, $user, $type, $data, $participantIds) {
            $conversation = Conversation::create([
                'company_id' => $companyId,
                'type' => $type,
                'title' => $type === 'group' ? ($data['title'] ?? 'New Group') : null,
                'avatar' => $data['avatar'] ?? null,
                'created_by' => $user->id,
                'last_message_at' => now(),
            ]);

            foreach ($participantIds as $pId) {
                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $pId,
                    'role' => $pId === $user->id ? 'admin' : 'member',
                ]);
            }

            if (!empty($data['initial_message'])) {
                $this->sendMessage($conversation->id, $user, ['body' => $data['initial_message']]);
            }

            return $this->getConversationDetails($conversation->id, $user);
        });
    }

    public function getConversationDetails(int $conversationId, User $user)
    {
        $conv = Conversation::with([
            'participants.user',
            'latestMessage.sender',
        ])->findOrFail($conversationId);

        $myParticipant = $conv->participants->firstWhere('user_id', $user->id);
        $otherParticipant = $conv->participants->firstWhere('user_id', '!=', $user->id);

        return [
            'id' => $conv->id,
            'type' => $conv->type,
            'title' => $conv->type === 'group' ? ($conv->title ?: 'Group Discussion') : ($otherParticipant?->user?->name ?? 'Direct Chat'),
            'avatar' => $conv->type === 'group' ? $conv->avatar : ($otherParticipant?->user?->avatar ?? 'https://i.pravatar.cc/150?u=chat'),
            'isOnline' => $otherParticipant ? (bool) ($otherParticipant->user->is_active ?? true) : false,
            'isPinned' => (bool) ($myParticipant?->is_pinned ?? false),
            'participants' => $conv->participants->map(fn($p) => [
                'id' => $p->user->id,
                'name' => $p->user->name,
                'email' => $p->user->email,
                'avatar' => $p->user->avatar,
                'role' => $p->role,
                'isOnline' => (bool) ($p->user->is_active ?? true),
            ]),
        ];
    }

    public function getMessages(int $conversationId, User $user)
    {
        // Ensure user is participant
        $isParticipant = ConversationParticipant::where('conversation_id', $conversationId)
            ->where('user_id', $user->id)
            ->exists();

        if (!$isParticipant) {
            abort(403, 'Unauthorized conversation access');
        }

        $messages = Message::where('conversation_id', $conversationId)
            ->with([
                'sender:id,name,avatar,role',
                'parent.sender:id,name',
                'attachments',
                'reactions.user:id,name',
            ])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) use ($user) {
                // Group reactions by emoji
                $reactionsSummary = [];
                foreach ($msg->reactions as $react) {
                    $reactionsSummary[$react->reaction] = ($reactionsSummary[$react->reaction] ?? 0) + 1;
                }

                $userReactions = $msg->reactions->where('user_id', $user->id)->pluck('reaction')->toArray();

                return [
                    'id' => $msg->id,
                    'conversation_id' => $msg->conversation_id,
                    'sender' => [
                        'id' => $msg->sender->id,
                        'name' => $msg->sender->name,
                        'avatar' => $msg->sender->avatar,
                        'role' => $msg->sender->role,
                    ],
                    'isMe' => $msg->user_id === $user->id,
                    'body' => $msg->body,
                    'type' => $msg->type,
                    'isEdited' => (bool) $msg->is_edited,
                    'isPinned' => (bool) $msg->is_pinned,
                    'parent' => $msg->parent ? [
                        'id' => $msg->parent->id,
                        'body' => $msg->parent->body,
                        'senderName' => $msg->parent->sender?->name ?? 'User',
                    ] : null,
                    'attachments' => $msg->attachments->map(fn($a) => [
                        'id' => $a->id,
                        'name' => $a->file_name,
                        'path' => $a->file_path,
                        'size' => $a->file_size,
                        'type' => $a->file_type,
                    ]),
                    'reactions' => $reactionsSummary,
                    'userReactions' => $userReactions,
                    'created_at' => $msg->created_at->format('h:i A'),
                    'timestamp' => $msg->created_at->toIso8601String(),
                ];
            });

        // Mark as read automatically
        $latestMsg = $messages->last();
        if ($latestMsg) {
            ConversationParticipant::where('conversation_id', $conversationId)
                ->where('user_id', $user->id)
                ->update(['last_read_message_id' => $latestMsg['id']]);

            MessageRead::firstOrCreate([
                'message_id' => $latestMsg['id'],
                'user_id' => $user->id,
            ]);
        }

        return $messages;
    }

    public function sendMessage(int $conversationId, User $user, array $data)
    {
        return DB::transaction(function () use ($conversationId, $user, $data) {
            $msg = Message::create([
                'conversation_id' => $conversationId,
                'user_id' => $user->id,
                'parent_id' => $data['parent_id'] ?? null,
                'body' => $data['body'] ?? '',
                'type' => $data['type'] ?? 'text',
            ]);

            if (!empty($data['attachments']) && is_array($data['attachments'])) {
                foreach ($data['attachments'] as $att) {
                    MessageAttachment::create([
                        'message_id' => $msg->id,
                        'file_name' => $att['name'] ?? 'attachment',
                        'file_path' => $att['path'] ?? '',
                        'file_size' => $att['size'] ?? 0,
                        'file_type' => $att['type'] ?? 'file',
                    ]);
                }
            }

            Conversation::where('id', $conversationId)->update([
                'last_message_at' => now(),
            ]);

            ConversationParticipant::where('conversation_id', $conversationId)
                ->where('user_id', $user->id)
                ->update(['last_read_message_id' => $msg->id]);

            return $msg->load(['sender', 'attachments', 'parent.sender']);
        });
    }

    public function editMessage(int $messageId, User $user, string $newBody)
    {
        $msg = Message::where('id', $messageId)->where('user_id', $user->id)->firstOrFail();
        $msg->update([
            'body' => $newBody,
            'is_edited' => true,
        ]);
        return $msg;
    }

    public function deleteMessage(int $messageId, User $user)
    {
        $msg = Message::where('id', $messageId)->where('user_id', $user->id)->firstOrFail();
        $msg->delete();
        return ['success' => true];
    }

    public function toggleReaction(int $messageId, User $user, string $reaction)
    {
        $existing = MessageReaction::where('message_id', $messageId)
            ->where('user_id', $user->id)
            ->where('reaction', $reaction)
            ->first();

        if ($existing) {
            $existing->delete();
            return ['action' => 'removed', 'reaction' => $reaction];
        } else {
            MessageReaction::create([
                'message_id' => $messageId,
                'user_id' => $user->id,
                'reaction' => $reaction,
            ]);
            return ['action' => 'added', 'reaction' => $reaction];
        }
    }

    public function togglePin(int $messageId, User $user)
    {
        $msg = Message::findOrFail($messageId);
        $msg->is_pinned = !$msg->is_pinned;
        $msg->save();
        return ['isPinned' => $msg->is_pinned];
    }

    public function markAsRead(int $conversationId, User $user)
    {
        $lastMsg = Message::where('conversation_id', $conversationId)->latest()->first();
        if ($lastMsg) {
            ConversationParticipant::where('conversation_id', $conversationId)
                ->where('user_id', $user->id)
                ->update(['last_read_message_id' => $lastMsg->id]);
        }
        return ['success' => true];
    }
}

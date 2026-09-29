<?php

namespace App\Services;

use App\Models\Email;
use App\Models\EmailAccount;
use App\Models\EmailAttachment;
use App\Models\EmailLabel;
use App\Models\EmailRecipient;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmailService
{
    public function getEmails(User $user, ?string $folder = 'inbox', ?string $search = null, ?int $labelId = null)
    {
        $companyId = $user->company_id ?: 1;

        $folderCounts = [
            'inbox' => Email::where('company_id', $companyId)->where('folder', 'inbox')->where('is_read', false)->count(),
            'sent' => Email::where('company_id', $companyId)->where('folder', 'sent')->count(),
            'drafts' => Email::where('company_id', $companyId)->where('folder', 'drafts')->count(),
            'starred' => Email::where('company_id', $companyId)->where('is_starred', true)->count(),
            'important' => Email::where('company_id', $companyId)->where('is_important', true)->count(),
            'trash' => Email::where('company_id', $companyId)->where('folder', 'trash')->count(),
            'spam' => Email::where('company_id', $companyId)->where('folder', 'spam')->count(),
        ];

        $query = Email::where('company_id', $companyId)
            ->with(['recipients', 'attachments', 'labels'])
            ->orderBy('created_at', 'desc');

        if ($folder === 'starred') {
            $query->where('is_starred', true);
        } elseif ($folder === 'important') {
            $query->where('is_important', true);
        } else {
            $query->where('folder', $folder ?: 'inbox');
        }

        if ($labelId) {
            $query->whereHas('labels', fn($q) => $q->where('label_id', $labelId));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('from_name', 'like', "%{$search}%")
                    ->orWhere('from_email', 'like', "%{$search}%")
                    ->orWhere('body_text', 'like', "%{$search}%");
            });
        }

        $emails = $query->take(50)->get()->map(function ($em) {
            return [
                'id' => $em->id,
                'threadId' => $em->thread_id,
                'folder' => $em->folder,
                'from' => [
                    'name' => $em->from_name ?: $em->from_email,
                    'email' => $em->from_email,
                    'avatar' => "https://i.pravatar.cc/150?u=" . urlencode($em->from_email),
                ],
                'to' => $em->recipients->where('type', 'to')->map(fn($r) => ['name' => $r->name, 'email' => $r->email]),
                'cc' => $em->recipients->where('type', 'cc')->map(fn($r) => ['name' => $r->name, 'email' => $r->email]),
                'bcc' => $em->recipients->where('type', 'bcc')->map(fn($r) => ['name' => $r->name, 'email' => $r->email]),
                'subject' => $em->subject,
                'snippet' => substr(strip_tags($em->body_text ?: $em->body_html ?: ''), 0, 110) . '...',
                'bodyHtml' => $em->body_html,
                'bodyText' => $em->body_text,
                'isRead' => (bool) $em->is_read,
                'isStarred' => (bool) $em->is_starred,
                'isImportant' => (bool) $em->is_important,
                'isDraft' => (bool) $em->is_draft,
                'hasAttachments' => $em->attachments->isNotEmpty(),
                'attachments' => $em->attachments->map(fn($att) => [
                    'id' => $att->id,
                    'name' => $att->file_name,
                    'path' => $att->file_path,
                    'size' => $att->file_size,
                    'mime' => $att->mime_type,
                ]),
                'labels' => $em->labels->map(fn($l) => [
                    'id' => $l->id,
                    'name' => $l->name,
                    'color' => $l->color,
                ]),
                'date' => $em->created_at->format('M d, Y h:i A'),
                'timeAgo' => $em->created_at->diffForHumans(),
            ];
        });

        $labels = EmailLabel::where('company_id', $companyId)->get();

        return [
            'folderCounts' => $folderCounts,
            'emails' => $emails,
            'labels' => $labels,
        ];
    }

    public function getEmail(int $id, User $user)
    {
        $em = Email::with(['recipients', 'attachments', 'labels'])->findOrFail($id);
        $em->update(['is_read' => true]);

        return [
            'id' => $em->id,
            'threadId' => $em->thread_id,
            'folder' => $em->folder,
            'from' => [
                'name' => $em->from_name ?: $em->from_email,
                'email' => $em->from_email,
                'avatar' => "https://i.pravatar.cc/150?u=" . urlencode($em->from_email),
            ],
            'to' => $em->recipients->where('type', 'to')->map(fn($r) => ['name' => $r->name, 'email' => $r->email]),
            'cc' => $em->recipients->where('type', 'cc')->map(fn($r) => ['name' => $r->name, 'email' => $r->email]),
            'bcc' => $em->recipients->where('type', 'bcc')->map(fn($r) => ['name' => $r->name, 'email' => $r->email]),
            'subject' => $em->subject,
            'bodyHtml' => $em->body_html,
            'bodyText' => $em->body_text,
            'isRead' => (bool) $em->is_read,
            'isStarred' => (bool) $em->is_starred,
            'isImportant' => (bool) $em->is_important,
            'isDraft' => (bool) $em->is_draft,
            'attachments' => $em->attachments->map(fn($att) => [
                'id' => $att->id,
                'name' => $att->file_name,
                'path' => $att->file_path,
                'size' => $att->file_size,
                'mime' => $att->mime_type,
            ]),
            'labels' => $em->labels,
            'date' => $em->created_at->format('M d, Y h:i A'),
            'timeAgo' => $em->created_at->diffForHumans(),
        ];
    }

    public function sendEmail(User $user, array $data)
    {
        $companyId = $user->company_id ?: 1;
        $isDraft = !empty($data['is_draft']);

        return DB::transaction(function () use ($companyId, $user, $data, $isDraft) {
            $email = Email::create([
                'company_id' => $companyId,
                'user_id' => $user->id,
                'thread_id' => $data['thread_id'] ?? ('TH-' . strtoupper(bin2hex(random_bytes(4)))),
                'folder' => $isDraft ? 'drafts' : 'sent',
                'from_email' => $user->email,
                'from_name' => $user->name,
                'subject' => $data['subject'] ?? '(No Subject)',
                'body_html' => $data['body_html'] ?? nl2br(e($data['body_text'] ?? '')),
                'body_text' => $data['body_text'] ?? strip_tags($data['body_html'] ?? ''),
                'is_read' => true,
                'is_starred' => false,
                'is_important' => $data['is_important'] ?? false,
                'is_draft' => $isDraft,
                'sent_at' => $isDraft ? null : now(),
            ]);

            // Recipients (To)
            $toRecipients = is_array($data['to'] ?? null) ? $data['to'] : (empty($data['to']) ? [] : explode(',', $data['to']));
            foreach ($toRecipients as $to) {
                $trimmed = trim(is_array($to) ? ($to['email'] ?? '') : $to);
                if ($trimmed) {
                    EmailRecipient::create([
                        'email_id' => $email->id,
                        'type' => 'to',
                        'email' => $trimmed,
                        'name' => is_array($to) ? ($to['name'] ?? null) : null,
                    ]);
                }
            }

            // CC
            $ccRecipients = is_array($data['cc'] ?? null) ? $data['cc'] : (empty($data['cc']) ? [] : explode(',', $data['cc']));
            foreach ($ccRecipients as $cc) {
                $trimmed = trim(is_array($cc) ? ($cc['email'] ?? '') : $cc);
                if ($trimmed) {
                    EmailRecipient::create([
                        'email_id' => $email->id,
                        'type' => 'cc',
                        'email' => $trimmed,
                    ]);
                }
            }

            // Attachments
            if (!empty($data['attachments']) && is_array($data['attachments'])) {
                foreach ($data['attachments'] as $att) {
                    EmailAttachment::create([
                        'email_id' => $email->id,
                        'file_name' => $att['name'] ?? 'file',
                        'file_path' => $att['path'] ?? '',
                        'file_size' => $att['size'] ?? 0,
                        'mime_type' => $att['mime'] ?? 'application/octet-stream',
                    ]);
                }
            }

            return $email->load(['recipients', 'attachments']);
        });
    }

    public function replyEmail(int $id, User $user, array $data)
    {
        $original = Email::findOrFail($id);
        $data['thread_id'] = $original->thread_id ?: ('TH-' . $original->id);
        $data['subject'] = str_starts_with($original->subject, 'Re:') ? $original->subject : 'Re: ' . $original->subject;
        $data['to'] = [$original->from_email];

        return $this->sendEmail($user, $data);
    }

    public function forwardEmail(int $id, User $user, array $data)
    {
        $original = Email::with('attachments')->findOrFail($id);
        $data['subject'] = str_starts_with($original->subject, 'Fwd:') ? $original->subject : 'Fwd: ' . $original->subject;
        $data['body_html'] = ($data['body_html'] ?? '') . "<br><br>---------- Forwarded message ---------<br>From: {$original->from_name} &lt;{$original->from_email}&gt;<br>Subject: {$original->subject}<br><br>" . $original->body_html;

        return $this->sendEmail($user, $data);
    }

    public function toggleStar(int $id, User $user)
    {
        $email = Email::findOrFail($id);
        $email->is_starred = !$email->is_starred;
        $email->save();
        return ['isStarred' => $email->is_starred];
    }

    public function toggleRead(int $id, User $user)
    {
        $email = Email::findOrFail($id);
        $email->is_read = !$email->is_read;
        $email->save();
        return ['isRead' => $email->is_read];
    }

    public function deleteEmail(int $id, User $user)
    {
        $email = Email::findOrFail($id);
        if ($email->folder === 'trash') {
            $email->delete(); // Soft delete
        } else {
            $email->update(['folder' => 'trash']);
        }
        return ['success' => true];
    }
}

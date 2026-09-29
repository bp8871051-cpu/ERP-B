<?php

namespace Database\Seeders;

use App\Models\Calendar;
use App\Models\CalendarEvent;
use App\Models\Call;
use App\Models\CallLog;
use App\Models\CallParticipant;
use App\Models\Company;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Email;
use App\Models\EmailAccount;
use App\Models\EmailAttachment;
use App\Models\EmailLabel;
use App\Models\EmailRecipient;
use App\Models\ErpNotification;
use App\Models\EventAttendee;
use App\Models\EventReminder;
use App\Models\FileItem;
use App\Models\Folder;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageReaction;
use App\Models\Note;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskComment;
use App\Models\TaskLabel;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowApproval;
use App\Models\WorkflowComment;
use App\Models\WorkflowLog;
use App\Models\WorkflowRequest;
use App\Models\WorkflowStep;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApplicationsSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first() ?: Company::create([
            'name' => 'Falcon LLP',
            'code' => 'FALCON-01',
            'email' => 'contact@falconllp.com',
            'phone' => '+1 (555) 019-2834',
            'currency' => 'USD',
            'currency_symbol' => '$',
            'status' => 'active',
        ]);

        $users = User::all();
        if ($users->count() < 5) {
            return;
        }

        // Clean existing application records for idempotent re-seeding
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \App\Models\ErpNotification::truncate();
        \App\Models\WorkflowLog::truncate();
        \App\Models\WorkflowComment::truncate();
        \App\Models\WorkflowApproval::truncate();
        \App\Models\WorkflowRequest::truncate();
        \App\Models\WorkflowStep::truncate();
        \App\Models\Workflow::truncate();
        \App\Models\TaskLabel::truncate();
        \App\Models\TaskAttachment::truncate();
        \App\Models\TaskChecklist::truncate();
        \App\Models\TaskComment::truncate();
        \Illuminate\Support\Facades\DB::table('task_assignees')->truncate();
        \App\Models\Task::whereNull('project_id')->forceDelete();
        \App\Models\NoteAttachment::truncate();
        \App\Models\NoteShare::truncate();
        \Illuminate\Support\Facades\DB::table('note_tags')->truncate();
        \App\Models\Note::truncate();
        \App\Models\Tag::truncate();
        \App\Models\FilePermission::truncate();
        \App\Models\FileShare::truncate();
        \App\Models\FileVersion::truncate();
        \App\Models\FileItem::truncate();
        \App\Models\Folder::truncate();
        \Illuminate\Support\Facades\DB::table('email_label_pivot')->truncate();
        \App\Models\EmailLabel::truncate();
        \App\Models\EmailAttachment::truncate();
        \App\Models\EmailRecipient::truncate();
        \App\Models\Email::truncate();
        \App\Models\EmailAccount::truncate();
        \App\Models\EventRecurrence::truncate();
        \App\Models\EventReminder::truncate();
        \App\Models\EventAttendee::truncate();
        \App\Models\CalendarEvent::truncate();
        \App\Models\Calendar::truncate();
        \App\Models\CallLog::truncate();
        \App\Models\CallParticipant::truncate();
        \App\Models\Call::truncate();
        \App\Models\MessageRead::truncate();
        \App\Models\MessageReaction::truncate();
        \App\Models\MessageAttachment::truncate();
        \App\Models\Message::truncate();
        \App\Models\ConversationParticipant::truncate();
        \App\Models\Conversation::truncate();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $admin = $users->firstWhere('role', 'Super Admin') ?: $users[0];
        $hr = $users->firstWhere('role', 'HR Manager') ?: $users[1];
        $sales = $users->firstWhere('role', 'Sales Manager') ?: $users[2];
        $finance = $users->firstWhere('role', 'Finance Manager') ?: $users[3];
        $emp = $users->firstWhere('role', 'Employee') ?: $users[4];

        $teamUsers = [$admin, $hr, $sales, $finance, $emp];
        $allUserIds = $users->pluck('id')->toArray();

        // =========================================================================
        // 1. CHAT MODULE (20 Conversations, 100+ Messages)
        // =========================================================================
        $groupTitles = [
            'Executive Strategic Committee',
            'Q3 Revenue Sprint Team',
            'DevOps & Infrastructure Ops',
            'Product Architecture 2026',
            'Security & Compliance Guild',
            'All-Hands Company Announcements',
            'Treasury & Audit Taskforce',
            'Customer Escalations War Room',
            'Office Facilities & Workplace',
            'Falcon AI Core Engineering',
        ];

        $createdConversations = [];

        // 10 Group conversations
        foreach ($groupTitles as $idx => $gTitle) {
            $conv = Conversation::create([
                'company_id' => $company->id,
                'type' => 'group',
                'title' => $gTitle,
                'avatar' => "https://images.unsplash.com/photo-" . (1522071820081 + $idx * 1000) . "?w=150",
                'created_by' => $admin->id,
                'last_message_at' => Carbon::now()->subMinutes(rand(5, 720)),
            ]);
            $createdConversations[] = $conv;

            $assignedParticipants = array_slice($allUserIds, 0, rand(4, min(8, count($allUserIds))));
            foreach ($assignedParticipants as $pId) {
                ConversationParticipant::create([
                    'conversation_id' => $conv->id,
                    'user_id' => $pId,
                    'role' => $pId === $admin->id ? 'admin' : 'member',
                    'is_pinned' => $idx < 2,
                    'is_muted' => false,
                ]);
            }
        }

        // 10 Direct conversations
        for ($i = 0; $i < 10; $i++) {
            $u1 = $users[$i % count($users)];
            $u2 = $users[($i + 1) % count($users)];

            $conv = Conversation::create([
                'company_id' => $company->id,
                'type' => 'direct',
                'title' => null,
                'avatar' => null,
                'created_by' => $u1->id,
                'last_message_at' => Carbon::now()->subMinutes(rand(10, 1440)),
            ]);
            $createdConversations[] = $conv;

            ConversationParticipant::create([
                'conversation_id' => $conv->id,
                'user_id' => $u1->id,
                'role' => 'member',
                'is_pinned' => $i === 0,
            ]);
            ConversationParticipant::create([
                'conversation_id' => $conv->id,
                'user_id' => $u2->id,
                'role' => 'member',
                'is_pinned' => false,
            ]);
        }

        $sampleMessages = [
            'Good morning team! The Q3 milestone deployment has been scheduled for this Thursday evening.',
            'Understood. I will verify the backup retention policies with DevOps before we initiate the migration.',
            'Please review the attached preliminary budget forecast for the cloud compute upgrade.',
            'LGTM! The unit economics look very sustainable. Let us push ahead.',
            'Has the customer support escalation for ticket #TCK-2026-101 been mitigated yet?',
            'Yes, we resolved the iDRAC connection issue and restored normal monitoring telemetry.',
            'Here are the finalized designs for the new dashboard cards and micro-animations.',
            'These look extremely sharp. Teal accents with slate navy typography give it a very sleek look.',
            'Meeting starts in 10 minutes in Conference Room A or via the remote Zoom link.',
            'Joining shortly, just concluding the quarterly vendor alignment call.',
            'Can someone approve the purchase order for the Dell PowerEdge server cluster?',
            'I just reviewed the PO specifications and signed off on the workflow request.',
            'Great job on closing the Acme Cloud Dynamics contract renewal! 🎉',
            'Thank you! The entire sales and engineering solutions team worked tirelessly on this.',
            'Attached is the updated SOC 2 audit report for our external compliance filing.',
            'Reminder: All expense claims for this month must be filed by Friday 5 PM.',
        ];

        $reactionsList = ['👍', '❤️', '🔥', '🚀', '👏', '✅'];
        $messageCount = 0;

        foreach ($createdConversations as $cIdx => $conv) {
            $participants = ConversationParticipant::where('conversation_id', $conv->id)->pluck('user_id')->toArray();
            $numMsgs = rand(5, 8);

            for ($m = 0; $m < $numMsgs; $m++) {
                $senderId = $participants[$m % count($participants)];
                $body = $sampleMessages[($cIdx * 3 + $m) % count($sampleMessages)];
                $msgType = ($m === 2 && $cIdx % 3 === 0) ? 'file' : (($m === 3 && $cIdx % 4 === 0) ? 'image' : 'text');

                $msg = Message::create([
                    'conversation_id' => $conv->id,
                    'user_id' => $senderId,
                    'parent_id' => null,
                    'body' => $body,
                    'type' => $msgType,
                    'is_edited' => ($m === 1 && $cIdx % 2 === 0),
                    'is_pinned' => ($m === 0 && $cIdx < 3),
                    'created_at' => Carbon::now()->subMinutes((10 - $m) * 35 + rand(1, 20)),
                ]);
                $messageCount++;

                if ($msgType === 'file') {
                    MessageAttachment::create([
                        'message_id' => $msg->id,
                        'file_name' => 'Q3_Financial_Forecast_v2.pdf',
                        'file_path' => 'erp_files/demo/forecast.pdf',
                        'file_size' => 2450000,
                        'file_type' => 'pdf',
                    ]);
                } elseif ($msgType === 'image') {
                    MessageAttachment::create([
                        'message_id' => $msg->id,
                        'file_name' => 'System_Architecture_Diagram.png',
                        'file_path' => 'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=800',
                        'file_size' => 1250000,
                        'file_type' => 'png',
                    ]);
                }

                // Add reactions
                if ($m % 2 === 0) {
                    MessageReaction::create([
                        'message_id' => $msg->id,
                        'user_id' => $participants[($m + 1) % count($participants)],
                        'reaction' => $reactionsList[($cIdx + $m) % count($reactionsList)],
                    ]);
                }
            }
        }

        // =========================================================================
        // 2. CALLS MODULE (Calls, Participants, Logs)
        // =========================================================================
        $callStatuses = ['completed', 'completed', 'completed', 'missed', 'rejected', 'busy'];
        $callTypes = ['voice', 'video'];
        $directions = ['incoming', 'outgoing'];

        for ($i = 0; $i < 30; $i++) {
            $caller = $users[$i % count($users)];
            $receiver = $users[($i + 2) % count($users)];
            $status = $callStatuses[$i % count($callStatuses)];
            $duration = $status === 'completed' ? rand(95, 2450) : 0;
            $direction = $directions[$i % 2];
            $type = $callTypes[$i % 2];
            $callDate = Carbon::now()->subHours(rand(1, 240));

            $call = Call::create([
                'company_id' => $company->id,
                'caller_id' => $caller->id,
                'receiver_id' => $receiver->id,
                'room_id' => 'ROOM-FALCON-' . sprintf('%04d', $i + 101),
                'type' => $type,
                'direction' => $direction,
                'status' => $status,
                'start_time' => $callDate,
                'end_time' => $callDate->copy()->addSeconds($duration),
                'duration' => $duration,
                'provider' => 'internal_webrtc',
                'notes' => $status === 'completed' ? 'Enterprise WebRTC internal peer-to-peer audio/video call.' : 'Unanswered call attempt.',
                'created_at' => $callDate,
            ]);

            CallParticipant::create([
                'call_id' => $call->id,
                'user_id' => $caller->id,
                'status' => 'joined',
                'joined_at' => $callDate,
            ]);

            CallParticipant::create([
                'call_id' => $call->id,
                'user_id' => $receiver->id,
                'status' => $status === 'completed' ? 'joined' : 'missed',
                'joined_at' => $status === 'completed' ? $callDate->copy()->addSeconds(4) : null,
            ]);

            CallLog::create([
                'call_id' => $call->id,
                'event' => 'CALL_STATUS_' . strtoupper($status),
                'metadata' => [
                    'direction' => $direction,
                    'duration' => $duration,
                    'codec' => 'Opus/48000',
                ],
            ]);
        }

        // =========================================================================
        // 3. CALENDAR MODULE (50 Calendar Events)
        // =========================================================================
        $defaultCal = Calendar::create([
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'name' => 'Enterprise Master Calendar',
            'color' => '#0F8B7A',
            'is_default' => true,
        ]);

        $eventTemplates = [
            ['Quarterly Executive Board Review', 'Reviewing financial statements, annual projections, and executive expansion roadmap.', 'meeting', 'Boardroom A (Tower 742)', 'https://meet.falconerp.com/board-q3'],
            ['Sprint 14 Backlog Grooming & Story Mapping', 'Technical refinement of microservice endpoints and user story estimation.', 'task', 'Virtual Room 1', 'https://meet.falconerp.com/sprint-grooming'],
            ['Workplace Safety & Compliance Certification', 'Mandatory semi-annual compliance workshop for facilities and technical staff.', 'schedule', 'HQ Auditorium', null],
            ['Client SLA & Technical Architecture Alignment', 'Acme Cloud Dynamics engineering deep dive and throughput capacity planning.', 'meeting', 'Client Briefing Center', 'https://meet.falconerp.com/acme-sla'],
            ['Capital Expenditure Approval Committee', 'Evaluating server cluster quotes, storage hardware requisition, and lease renewals.', 'workflow', 'Finance Executive Office', null],
            ['All-Hands Townhall & Product Vision 2026', 'Company-wide address from leadership, milestone celebrations, and live Q&A.', 'schedule', 'Main Hall / Global Stream', 'https://meet.falconerp.com/townhall'],
            ['Cybersecurity Threat Modeling & Red Team Briefing', 'Reviewing zero-trust perimeter telemetry and penetration test findings.', 'task', 'Secure Operations Lab', null],
            ['Human Resources 1-on-1 Performance Sync', 'Bi-monthly career growth discussion and KPI alignment.', 'personal', 'Private Huddle 3', null],
            ['Vendor Negotiations: Cisco & Dell Direct', 'Contract renewal pricing review with key procurement representatives.', 'meeting', 'Procurement Conference Room', 'https://meet.falconerp.com/vendor-sync'],
            ['DevOps Deployment Window & Database Index Rebuild', 'Scheduled off-hours maintenance window for index optimization.', 'task', 'Online NOC Center', null],
        ];

        for ($i = 0; $i < 50; $i++) {
            $tpl = $eventTemplates[$i % count($eventTemplates)];
            $dayOffset = ($i % 30) - 10; // Mix of past, current, and upcoming days
            $startHour = 9 + ($i % 8);
            $startDt = Carbon::today()->addDays($dayOffset)->setHour($startHour)->setMinute(($i % 2 === 0 ? 0 : 30));
            $endDt = $startDt->copy()->addMinutes(rand(45, 120));

            $event = CalendarEvent::create([
                'company_id' => $company->id,
                'calendar_id' => $defaultCal->id,
                'creator_id' => $users[$i % count($users)]->id,
                'title' => $tpl[0] . ' #' . ($i + 1),
                'description' => $tpl[1],
                'start_time' => $startDt,
                'end_time' => $endDt,
                'timezone' => 'America/Los_Angeles',
                'location' => $tpl[3],
                'meeting_link' => $tpl[4],
                'category' => $tpl[2],
                'status' => $dayOffset < 0 ? 'completed' : ($i % 12 === 0 ? 'cancelled' : 'scheduled'),
                'is_all_day' => $i % 15 === 0,
                'is_recurring' => $i % 7 === 0,
                'recurrence_rule' => $i % 7 === 0 ? 'FREQ=WEEKLY;INTERVAL=1' : null,
            ]);

            // Attendees
            EventAttendee::create([
                'event_id' => $event->id,
                'user_id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'status' => 'accepted',
            ]);
            EventAttendee::create([
                'event_id' => $event->id,
                'user_id' => $users[($i + 1) % count($users)]->id,
                'name' => $users[($i + 1) % count($users)]->name,
                'email' => $users[($i + 1) % count($users)]->email,
                'status' => 'accepted',
            ]);

            // Reminder
            EventReminder::create([
                'event_id' => $event->id,
                'minutes_before' => 15,
                'type' => 'notification',
            ]);
        }

        // =========================================================================
        // 4. EMAIL MODULE (100 Emails across Folders)
        // =========================================================================
        $defaultMailAccount = EmailAccount::create([
            'company_id' => $company->id,
            'user_id' => $admin->id,
            'name' => 'Falcon Corporate Exchange',
            'email' => 'alexander.wright@falconllp.com',
            'provider' => 'office365',
            'incoming_host' => 'outlook.office365.com',
            'incoming_port' => 993,
            'outgoing_host' => 'smtp.office365.com',
            'outgoing_port' => 587,
            'credentials' => ['access_token' => 'secure_encrypted_token_sample'],
            'is_default' => true,
            'status' => 'active',
        ]);

        $labels = [
            EmailLabel::create(['company_id' => $company->id, 'user_id' => $admin->id, 'name' => 'Executive', 'color' => '#7C3AED']),
            EmailLabel::create(['company_id' => $company->id, 'user_id' => $admin->id, 'name' => 'Finance', 'color' => '#0F8B7A']),
            EmailLabel::create(['company_id' => $company->id, 'user_id' => $admin->id, 'name' => 'Clients', 'color' => '#2563EB']),
            EmailLabel::create(['company_id' => $company->id, 'user_id' => $admin->id, 'name' => 'Security', 'color' => '#EF4444']),
            EmailLabel::create(['company_id' => $company->id, 'user_id' => $admin->id, 'name' => 'HR & Payroll', 'color' => '#F59E0B']),
        ];

        $emailSubjects = [
            ['Quarterly Earnings & EBITDA Performance Summary', 'inbox', true, true],
            ['Action Required: Approve Annual Security Compliance Audit', 'inbox', false, true],
            ['Acme Cloud Dynamics Contract Addendum Executed', 'inbox', true, false],
            ['Vendor Quotation: Dell PowerEdge R750 Bulk Order', 'inbox', false, false],
            ['Monthly Health & Dental Benefits Enrollment Open', 'inbox', true, false],
            ['FWD: Tier 1 SLA Response Time Metrics for March', 'inbox', false, false],
            ['Proposal for Cloud Infrastructure Cost Optimization', 'sent', true, false],
            ['Invitation: Product Steering Council Review', 'sent', true, false],
            ['Draft: Confidential Acquisition Terms of Agreement', 'drafts', true, true],
            ['Draft: Q4 Regional Sales Incentive Structure', 'drafts', false, false],
            ['Security Alert: Unusual API Access Token Traffic', 'important', false, true],
            ['Spam: Unsolicited Offshore Development Solicitation', 'spam', true, false],
            ['Old Project Timesheet Confirmation', 'trash', true, false],
        ];

        for ($i = 0; $i < 100; $i++) {
            $tmpl = $emailSubjects[$i % count($emailSubjects)];
            $folder = $tmpl[1];
            $sender = $users[($i + 3) % count($users)];
            $sentDt = Carbon::now()->subHours(rand(1, 720));

            $email = Email::create([
                'company_id' => $company->id,
                'email_account_id' => $defaultMailAccount->id,
                'user_id' => $admin->id,
                'thread_id' => 'TH-2026-' . sprintf('%04d', ($i % 30) + 1),
                'folder' => $folder,
                'from_email' => $folder === 'sent' ? $admin->email : $sender->email,
                'from_name' => $folder === 'sent' ? $admin->name : $sender->name,
                'subject' => $tmpl[0] . ($i > 13 ? " (Ref: #" . ($i + 100) . ")" : ""),
                'body_html' => "<p>Dear Team,</p><p>Please find the official update regarding <strong>" . e($tmpl[0]) . "</strong>.</p><p>We have carefully reviewed all parameters with stakeholders and aligned our upcoming execution priorities with company KPIs.</p><p>Best regards,<br><strong>" . ($folder === 'sent' ? $admin->name : $sender->name) . "</strong><br>Falcon ERP Enterprise Suite</p>",
                'body_text' => "Dear Team,\n\nPlease find the official update regarding " . $tmpl[0] . ".\n\nWe have carefully reviewed all parameters with stakeholders and aligned our upcoming execution priorities with company KPIs.\n\nBest regards,\n" . ($folder === 'sent' ? $admin->name : $sender->name),
                'is_read' => $tmpl[2],
                'is_starred' => $tmpl[3] || ($i % 5 === 0),
                'is_important' => $tmpl[3],
                'is_draft' => $folder === 'drafts',
                'sent_at' => $folder === 'drafts' ? null : $sentDt,
                'created_at' => $sentDt,
            ]);

            // Recipient
            EmailRecipient::create([
                'email_id' => $email->id,
                'type' => 'to',
                'email' => $folder === 'sent' ? $sender->email : $admin->email,
                'name' => $folder === 'sent' ? $sender->name : $admin->name,
            ]);

            // Attachments for some
            if ($i % 4 === 0) {
                EmailAttachment::create([
                    'email_id' => $email->id,
                    'file_name' => 'Report_Document_' . ($i + 1) . '.pdf',
                    'file_path' => 'erp_files/demo/doc_' . $i . '.pdf',
                    'file_size' => rand(150000, 3500000),
                    'mime_type' => 'application/pdf',
                ]);
            }

            // Labels
            $email->labels()->sync([$labels[$i % count($labels)]->id]);
        }

        // =========================================================================
        // 5. FILE MANAGER MODULE (Folders & 50 Files)
        // =========================================================================
        $folderNames = [
            'Contracts & Master NDAs',
            'Financial Statements 2026',
            'HR Policies & Employee Handbooks',
            'Product Specifications & RFCs',
            'Enterprise Marketing Assets',
            'Procurement Invoices & Receipts',
            'External Audit & Compliance Files',
        ];

        $createdFolders = [];
        foreach ($folderNames as $fName) {
            $createdFolders[] = Folder::create([
                'company_id' => $company->id,
                'user_id' => $admin->id,
                'name' => $fName,
                'color' => '#0F8B7A',
                'is_favorite' => false,
            ]);
        }

        $fileExtensions = [
            ['Falcon_ERP_Architecture_Whitepaper', 'pdf', 'application/pdf', 3840000],
            ['Q1_Consolidated_Balance_Sheet', 'xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 1420000],
            ['Enterprise_Master_Service_Agreement_v4', 'docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 640000],
            ['Hardware_Procurement_Purchase_Order', 'pdf', 'application/pdf', 980000],
            ['Brand_Guidelines_and_Logos_2026', 'zip', 'application/zip', 14800000],
            ['Employee_Handbook_and_Benefits_Manual', 'pdf', 'application/pdf', 2150000],
            ['Cloud_Infrastructure_Topology_HighRes', 'png', 'image/png', 4200000],
            ['Client_Billing_Schedule_Matrix', 'csv', 'text/csv', 320000],
            ['Board_Presentation_Q3_Strategic_Deck', 'pptx', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 8900000],
            ['Executive_Product_Brief', 'doc', 'application/msword', 450000],
        ];

        for ($i = 0; $i < 50; $i++) {
            $fe = $fileExtensions[$i % count($fileExtensions)];
            $assignedFolder = ($i < 12) ? null : $createdFolders[$i % count($createdFolders)];
            $fName = $fe[0] . '_' . ($i + 1) . '.' . $fe[1];

            FileItem::create([
                'company_id' => $company->id,
                'user_id' => $users[$i % count($users)]->id,
                'folder_id' => $assignedFolder?->id,
                'name' => $fName,
                'disk' => 'public',
                'file_path' => 'erp_files/' . $company->id . '/' . $fName,
                'file_type' => $fe[1],
                'mime_type' => $fe[2],
                'file_size' => $fe[3] + rand(1000, 50000),
                'is_favorite' => $i % 6 === 0,
                'is_recent' => $i < 15,
                'download_count' => rand(2, 48),
                'created_at' => Carbon::now()->subDays(rand(1, 45)),
            ]);
        }

        // =========================================================================
        // 6. NOTES MODULE (30 Notes with Tags & Checklists)
        // =========================================================================
        $tagNames = ['Architecture', 'Strategy', 'Sprint', 'Budget', 'Security', 'Operations', 'Meeting Notes'];
        $createdTags = [];
        foreach ($tagNames as $tName) {
            $createdTags[] = Tag::create([
                'company_id' => $company->id,
                'name' => $tName,
                'color' => '#0F8B7A',
            ]);
        }

        $noteTemplates = [
            [
                'title' => 'Core Architecture Principles for Falcon ERP v2',
                'content' => "Key tenets for the microservices and frontend design:\n1. Zero hardcoded business data.\n2. Unified multi-company database structure.\n3. FilamentPHP exclusively for administrative operations.\n4. Responsive 3-column desktop and single-column mobile views.\n5. WebSockets event-driven architecture.",
                'checklist' => [
                    ['text' => 'Verify Bearer token interceptor in Axios', 'done' => true],
                    ['text' => 'Enforce server-side multi-company scoping', 'done' => true],
                    ['text' => 'Implement soft deletes on all application models', 'done' => true],
                    ['text' => 'Audit log every state alteration', 'done' => false],
                ],
                'pinned' => true,
            ],
            [
                'title' => 'Q3 Revenue Sprint & Enterprise Pipeline Target',
                'content' => "Strategic targets for this fiscal quarter:\n- Close 12 Tier-1 accounts.\n- Expand average contract value by 18%.\n- Complete SOC-2 Type II audit readiness.\n- Launch automated recurring invoice generator.",
                'checklist' => [
                    ['text' => 'Finalize Acme Cloud Dynamics contract renewal', 'done' => true],
                    ['text' => 'Deliver proposal to CyberPulse Security', 'done' => true],
                    ['text' => 'Schedule QBR with Vanguard Life Sciences', 'done' => false],
                ],
                'pinned' => true,
            ],
            [
                'title' => 'Weekly Engineering Sync & Roadmap Priorities',
                'content' => "Sprint highlights:\n- REST API controllers benchmarked under 80ms response time.\n- Database indexes added on foreign keys and timestamps.\n- Centralized notifications system with unread badge counter.",
                'checklist' => [
                    ['text' => 'Review pull request #42', 'done' => true],
                    ['text' => 'Conduct staging stress test', 'done' => false],
                ],
                'pinned' => false,
            ],
            [
                'title' => 'Office Relocation & Data Center Hardware Plan',
                'content' => "Rack configuration and power redundancy requirements for the new primary facility in Austin.\nEnsure backup generators are tested bi-weekly.",
                'checklist' => [],
                'pinned' => false,
            ],
            [
                'title' => 'Customer Feedback Summary: March Review',
                'content' => "Clients appreciated the fast dashboard load times and intuitive navigation.\nFeature requests: Group chat pinning and multi-file drag and drop.",
                'checklist' => [
                    ['text' => 'Implement drag & drop file upload', 'done' => true],
                    ['text' => 'Support group chat mentions', 'done' => false],
                ],
                'pinned' => false,
            ],
        ];

        for ($i = 0; $i < 30; $i++) {
            $nt = $noteTemplates[$i % count($noteTemplates)];

            $note = Note::create([
                'company_id' => $company->id,
                'user_id' => $users[$i % count($users)]->id,
                'title' => $nt['title'] . ($i > 4 ? " (#" . ($i + 1) . ")" : ""),
                'content' => $nt['content'],
                'checklist' => $nt['checklist'],
                'color' => '#ffffff',
                'is_pinned' => $nt['pinned'] || ($i === 0 || $i === 1),
                'is_archived' => $i === 28,
                'is_favorite' => $i % 4 === 0,
                'created_at' => Carbon::now()->subDays(rand(1, 60)),
            ]);

            $note->tags()->sync([$createdTags[$i % count($createdTags)]->id]);
        }

        // =========================================================================
        // 7. TO DO / TASK MODULE (100 Tasks across Kanban & Views)
        // =========================================================================
        $taskTemplates = [
            ['Implement Sanctum authentication interceptor in React Axios client', 'high', 'Engineering', 'completed'],
            ['Refactor responsive accordion sidebar with Applications menu', 'urgent', 'Design', 'completed'],
            ['Configure Filament admin resources for all 9 application entities', 'urgent', 'Operations', 'completed'],
            ['Write database migrations for Chat, Calls, Calendar and Email modules', 'high', 'Engineering', 'completed'],
            ['Construct 3-column internal Chat conversation user interface', 'urgent', 'Design', 'in_progress'],
            ['Design internal WebRTC voice and video call management interface', 'high', 'Engineering', 'in_progress'],
            ['Build interactive Month/Week/Day ERP Calendar with category filters', 'high', 'Design', 'in_progress'],
            ['Develop Enterprise Email client with folder trees and compose modal', 'urgent', 'Engineering', 'todo'],
            ['Implement File Manager with breadcrumbs, multi-upload and storage quota', 'medium', 'Engineering', 'todo'],
            ['Build Notes application with tag filtering and checklist tracking', 'medium', 'Design', 'todo'],
            ['Create Kanban drag-and-drop task board with priority badges', 'high', 'Engineering', 'in_progress'],
            ['Architect multi-stage sequential and parallel Workflow Approvals engine', 'urgent', 'Operations', 'in_progress'],
            ['Benchmark MySQL query execution time on large ledger tables', 'medium', 'Operations', 'review'],
            ['Verify TLS 1.3 cipher suite and OAuth2 token expiration handling', 'high', 'Security', 'review'],
            ['Prepare comprehensive realistic database seeders for all modules', 'high', 'Operations', 'completed'],
            ['Conduct sprint review with cross-functional department leaders', 'medium', 'Management', 'completed'],
        ];

        for ($i = 0; $i < 100; $i++) {
            $tt = $taskTemplates[$i % count($taskTemplates)];
            $assignee = $users[$i % count($users)];
            $reporter = $admin;
            $dayOffset = ($i % 30) - 8;
            $status = $tt[3];

            // Mix statuses for healthy Kanban distribution
            if ($i >= 16) {
                $statusChoices = ['todo', 'in_progress', 'review', 'completed'];
                $status = $statusChoices[$i % 4];
            }

            $task = Task::create([
                'company_id' => $company->id,
                'project_id' => null,
                'assigned_to' => $assignee->id,
                'reporter_id' => $reporter->id,
                'title' => $tt[0] . ($i >= 16 ? " - Iteration " . ($i + 1) : ""),
                'description' => "Detailed specification and acceptance criteria for: " . $tt[0] . ". Ensure complete test coverage and proper responsive UI rendering.",
                'priority' => $tt[1],
                'status' => $status,
                'category' => $tt[2],
                'start_date' => Carbon::today()->subDays(rand(1, 15)),
                'due_date' => Carbon::today()->addDays($dayOffset),
                'order' => $i + 1,
            ]);

            // Checklists
            TaskChecklist::create([
                'task_id' => $task->id,
                'title' => 'Review specifications and API contract',
                'is_completed' => true,
            ]);
            TaskChecklist::create([
                'task_id' => $task->id,
                'title' => 'Verify UI behavior on mobile viewports',
                'is_completed' => $status === 'completed',
            ]);

            // Comment
            if ($i % 3 === 0) {
                TaskComment::create([
                    'task_id' => $task->id,
                    'user_id' => $reporter->id,
                    'comment' => 'Please coordinate with the team lead before finalizing this ticket.',
                ]);
            }
        }

        // =========================================================================
        // 8. WORKFLOW & APPROVALS (20 Workflows, 50 Approval Requests)
        // =========================================================================
        $workflowConfigs = [
            ['Purchase Requisition Approval (Over $5,000)', 'Purchase Request', 'amount_threshold'],
            ['Annual & Casual Leave Application', 'Leave Application', 'on_submit'],
            ['Corporate Expense & Travel Claim Reimbursement', 'Expense Claim', 'on_submit'],
            ['Enterprise Customer Contract Legal Review', 'Contract Approval', 'manual'],
            ['Capital Hardware & Server Requisition', 'Purchase Request', 'amount_threshold'],
            ['Customer Credit Limit Extension Request', 'Finance', 'manual'],
            ['Software License & SaaS Subscription Approval', 'IT Requisition', 'on_submit'],
            ['Employee Promotion & Compensation Adjustment', 'HR Management', 'manual'],
            ['Datacenter Maintenance Window Clearance', 'Operations', 'on_submit'],
            ['Vendor Invoice Authorization & Payment Release', 'Finance', 'amount_threshold'],
            ['Marketing Campaign Budget Allocation', 'Marketing', 'amount_threshold'],
            ['Client SLA Exception Authorization', 'Customer Success', 'manual'],
            ['Emergency Patch & Production Hotfix Deployment', 'DevOps', 'on_submit'],
            ['Company Vehicle & Asset Fleet Requisition', 'Operations', 'manual'],
            ['Corporate Travel & Airfare Booking Request', 'Expense Claim', 'on_submit'],
            ['External Consultant Onboarding Authorization', 'HR Management', 'manual'],
            ['Intellectual Property & Trademark Registration', 'Legal', 'manual'],
            ['Warehouse Stock Write-off & Disposal', 'Inventory', 'amount_threshold'],
            ['Key Customer Discount Authorization (> 20%)', 'Sales Discount', 'amount_threshold'],
            ['Annual Strategic IT Budget Allocation', 'Executive', 'manual'],
        ];

        $createdWorkflows = [];
        foreach ($workflowConfigs as $wfIdx => $wfConf) {
            $wf = Workflow::create([
                'company_id' => $company->id,
                'name' => $wfConf[0],
                'description' => "Standard operating procedure workflow for {$wfConf[1]}.",
                'module' => $wfConf[1],
                'trigger' => $wfConf[2],
                'status' => 'active',
                'created_by' => $admin->id,
            ]);
            $createdWorkflows[] = $wf;

            // 3 Standard sequential steps
            WorkflowStep::create([
                'workflow_id' => $wf->id,
                'step_order' => 1,
                'name' => 'Department Manager Endorsement',
                'approver_role' => 'Manager',
                'approver_user_id' => $hr->id,
                'type' => 'sequential',
                'sla_hours' => 24,
            ]);

            WorkflowStep::create([
                'workflow_id' => $wf->id,
                'step_order' => 2,
                'name' => 'Financial Controller Audit',
                'approver_role' => 'Finance Manager',
                'approver_user_id' => $finance->id,
                'type' => 'sequential',
                'sla_hours' => 48,
            ]);

            WorkflowStep::create([
                'workflow_id' => $wf->id,
                'step_order' => 3,
                'name' => 'Executive Sign-off',
                'approver_role' => 'Super Admin',
                'approver_user_id' => $admin->id,
                'type' => 'sequential',
                'sla_hours' => 72,
            ]);
        }

        $requestStatuses = ['pending', 'pending', 'approved', 'approved', 'rejected', 'changes_requested'];

        for ($i = 0; $i < 50; $i++) {
            $wf = $createdWorkflows[$i % count($createdWorkflows)];
            $requester = $users[($i + 1) % count($users)];
            $status = $requestStatuses[$i % count($requestStatuses)];
            $firstStep = $wf->steps->first();
            $secondStep = $wf->steps->skip(1)->first();

            $currentStep = $status === 'pending' ? ($i % 2 === 0 ? $firstStep : $secondStep) : null;
            $refNumber = 'REQ-2026-' . sprintf('%04d', $i + 101);

            $req = WorkflowRequest::create([
                'company_id' => $company->id,
                'workflow_id' => $wf->id,
                'requester_id' => $requester->id,
                'reference_number' => $refNumber,
                'title' => $wf->name . ' - Case #' . ($i + 1),
                'module' => $wf->module,
                'amount' => rand(1500, 75000),
                'data' => [
                    'urgency' => $i % 4 === 0 ? 'Urgent' : 'Normal',
                    'department' => $requester->role,
                    'justification' => 'Operational requirement for team scaling and customer commitments.',
                ],
                'current_step_id' => $currentStep?->id,
                'status' => $status,
                'due_date' => Carbon::today()->addDays(rand(2, 10)),
                'created_at' => Carbon::now()->subDays(rand(1, 20)),
            ]);

            // Add Approvals
            if ($status === 'approved') {
                WorkflowApproval::create([
                    'workflow_request_id' => $req->id,
                    'workflow_step_id' => $firstStep->id,
                    'approver_id' => $hr->id,
                    'action' => 'approved',
                    'comments' => 'Endorsed. Meets all department operational guidelines.',
                    'action_taken_at' => Carbon::now()->subDays(2),
                ]);
                WorkflowApproval::create([
                    'workflow_request_id' => $req->id,
                    'workflow_step_id' => $secondStep->id,
                    'approver_id' => $finance->id,
                    'action' => 'approved',
                    'comments' => 'Budget confirmed and allocated.',
                    'action_taken_at' => Carbon::now()->subDay(),
                ]);
            } elseif ($status === 'rejected') {
                WorkflowApproval::create([
                    'workflow_request_id' => $req->id,
                    'workflow_step_id' => $firstStep->id,
                    'approver_id' => $hr->id,
                    'action' => 'rejected',
                    'comments' => 'Request does not align with current fiscal priorities.',
                    'action_taken_at' => Carbon::now()->subDays(1),
                ]);
            }

            WorkflowLog::create([
                'workflow_request_id' => $req->id,
                'user_id' => $requester->id,
                'action' => 'SUBMITTED',
                'description' => "Request {$refNumber} submitted by {$requester->name}",
            ]);

            WorkflowComment::create([
                'workflow_request_id' => $req->id,
                'user_id' => $requester->id,
                'comment' => 'All quotation files and justification documents have been attached for review.',
            ]);
        }

        // =========================================================================
        // 9. CENTRALIZED NOTIFICATIONS (Realistic ERP alerts)
        // =========================================================================
        $notificationItems = [
            ['chat', 'New Chat Message', 'Sarah Jenkins sent you a file in Q3 Revenue Sprint Team', '/app/applications/chat', 'MessageSquare'],
            ['calls', 'Missed Call Alert', 'Missed call from Elena Rostova (+1 555 100-0004)', '/app/applications/calls', 'PhoneCall'],
            ['calendar', 'Upcoming Event Reminder', 'Quarterly Executive Board Review starts in 15 minutes', '/app/applications/calendar', 'Calendar'],
            ['email', 'New High Priority Email', 'Acme Cloud Dynamics Contract Addendum Executed', '/app/applications/email', 'Mail'],
            ['task', 'Task Assigned to You', 'Build interactive Month/Week/Day ERP Calendar with category filters', '/app/applications/todo', 'CheckSquare'],
            ['workflow', 'Approval Required', 'REQ-2026-0105: Purchase Requisition requires your sign-off', '/app/applications/workflows', 'GitPullRequest'],
            ['files', 'File Shared with You', 'Thomas Miller shared Enterprise_Master_Service_Agreement_v4.docx', '/app/applications/files', 'FolderClosed'],
        ];

        foreach ($notificationItems as $nIdx => $nItem) {
            ErpNotification::create([
                'company_id' => $company->id,
                'user_id' => $admin->id,
                'type' => $nItem[0],
                'title' => $nItem[1],
                'message' => $nItem[2],
                'link' => $nItem[3],
                'icon' => $nItem[4],
                'read_at' => $nIdx > 3 ? Carbon::now()->subHours(2) : null, // 4 unread
                'created_at' => Carbon::now()->subMinutes(($nIdx + 1) * 20),
            ]);
        }
    }
}

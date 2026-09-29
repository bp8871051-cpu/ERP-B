<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. CHAT MODULE
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('type')->default('direct'); // direct, group
            $table->string('title')->nullable();
            $table->string('avatar')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'type']);
            $table->index('last_message_at');
        });

        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('member'); // admin, member
            $table->unsignedBigInteger('last_read_message_id')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_muted')->default(false);
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'is_pinned']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('body')->nullable();
            $table->string('type')->default('text'); // text, image, file, voice, system
            $table->boolean('is_edited')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conversation_id', 'created_at']);
            $table->index('user_id');
        });

        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->bigInteger('file_size')->default(0);
            $table->string('file_type')->nullable();
            $table->timestamps();
        });

        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('reaction');
            $table->timestamps();

            $table->unique(['message_id', 'user_id', 'reaction']);
        });

        Schema::create('message_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();

            $table->unique(['message_id', 'user_id']);
        });

        // 2. CALLS MODULE
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('caller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('room_id')->nullable();
            $table->string('type')->default('voice'); // voice, video, internal, external
            $table->string('direction')->default('outgoing'); // incoming, outgoing
            $table->string('status')->default('completed'); // completed, missed, rejected, busy, cancelled
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->integer('duration')->default(0); // in seconds
            $table->string('provider')->default('internal_webrtc'); // internal_webrtc, twilio, agora
            $table->string('recording_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'caller_id']);
            $table->index(['company_id', 'receiver_id']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('call_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained('calls')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('joined'); // joined, left, missed, declined
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
        });

        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained('calls')->cascadeOnDelete();
            $table->string('event');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // 3. CALENDAR MODULE
        Schema::create('calendars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('#0F8B7A');
            $table->boolean('is_default')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'user_id']);
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('calendar_id')->nullable()->constrained('calendars')->nullOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('timezone')->default('UTC');
            $table->string('location')->nullable();
            $table->string('meeting_link')->nullable();
            $table->string('color')->nullable();
            $table->string('category')->default('meeting'); // meeting, task, workflow, schedule, personal
            $table->string('status')->default('scheduled'); // scheduled, completed, cancelled
            $table->boolean('is_all_day')->default(false);
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_rule')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'start_time', 'end_time']);
            $table->index(['creator_id', 'status']);
        });

        Schema::create('event_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('status')->default('pending'); // pending, accepted, declined, tentative
            $table->timestamps();
        });

        Schema::create('event_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->integer('minutes_before')->default(15);
            $table->string('type')->default('notification'); // notification, email
            $table->boolean('is_sent')->default(false);
            $table->timestamps();
        });

        Schema::create('event_recurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('frequency')->default('weekly'); // daily, weekly, monthly, yearly
            $table->integer('interval')->default(1);
            $table->date('until')->nullable();
            $table->timestamps();
        });

        // 4. EMAIL MODULE
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('provider')->default('smtp'); // smtp, gmail, office365
            $table->string('incoming_host')->nullable();
            $table->integer('incoming_port')->nullable();
            $table->string('outgoing_host')->nullable();
            $table->integer('outgoing_port')->nullable();
            $table->text('credentials')->nullable(); // encrypted JSON
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['company_id', 'user_id']);
        });

        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('email_account_id')->nullable()->constrained('email_accounts')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('thread_id')->nullable();
            $table->string('folder')->default('inbox'); // inbox, sent, drafts, starred, important, trash, spam
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->string('subject');
            $table->longText('body_html')->nullable();
            $table->text('body_text')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_important')->default(false);
            $table->boolean('is_draft')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'folder', 'is_read']);
            $table->index(['user_id', 'folder']);
            $table->index('thread_id');
        });

        Schema::create('email_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_id')->constrained('emails')->cascadeOnDelete();
            $table->string('type')->default('to'); // to, cc, bcc
            $table->string('email');
            $table->string('name')->nullable();
            $table->timestamps();

            $table->index(['email_id', 'type']);
        });

        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_id')->constrained('emails')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->bigInteger('file_size')->default(0);
            $table->string('mime_type')->nullable();
            $table->timestamps();
        });

        Schema::create('email_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('#2563EB');
            $table->timestamps();
        });

        Schema::create('email_label_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_id')->constrained('emails')->cascadeOnDelete();
            $table->foreignId('label_id')->constrained('email_labels')->cascadeOnDelete();

            $table->unique(['email_id', 'label_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_label_pivot');
        Schema::dropIfExists('email_labels');
        Schema::dropIfExists('email_attachments');
        Schema::dropIfExists('email_recipients');
        Schema::dropIfExists('emails');
        Schema::dropIfExists('email_accounts');

        Schema::dropIfExists('event_recurrences');
        Schema::dropIfExists('event_reminders');
        Schema::dropIfExists('event_attendees');
        Schema::dropIfExists('events');
        Schema::dropIfExists('calendars');

        Schema::dropIfExists('call_logs');
        Schema::dropIfExists('call_participants');
        Schema::dropIfExists('calls');

        Schema::dropIfExists('message_reads');
        Schema::dropIfExists('message_reactions');
        Schema::dropIfExists('message_attachments');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversation_participants');
        Schema::dropIfExists('conversations');
    }
};

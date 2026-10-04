<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. SLA Policies Table
        Schema::create('support_sla_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('priority', ['urgent', 'high', 'medium', 'low'])->default('medium');
            $table->integer('first_response_target_minutes')->default(240); // 4 hours
            $table->integer('resolution_target_minutes')->default(1440);   // 24 hours
            $table->boolean('business_hours')->default(true);
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('customer_type')->default('all'); // all, vip, enterprise, standard
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Enhance existing tickets table if columns don't exist
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->after('assigned_agent_id');
            }
            if (!Schema::hasColumn('tickets', 'sla_policy_id')) {
                $table->unsignedBigInteger('sla_policy_id')->nullable()->after('department_id');
            }
            if (!Schema::hasColumn('tickets', 'source')) {
                $table->string('source', 50)->default('web')->after('status');
            }
            if (!Schema::hasColumn('tickets', 'first_response_due_at')) {
                $table->dateTime('first_response_due_at')->nullable()->after('source');
            }
            if (!Schema::hasColumn('tickets', 'resolution_due_at')) {
                $table->dateTime('resolution_due_at')->nullable()->after('first_response_due_at');
            }
            if (!Schema::hasColumn('tickets', 'first_responded_at')) {
                $table->dateTime('first_responded_at')->nullable()->after('resolution_due_at');
            }
            if (!Schema::hasColumn('tickets', 'resolved_at')) {
                $table->dateTime('resolved_at')->nullable()->after('first_responded_at');
            }
            if (!Schema::hasColumn('tickets', 'closed_at')) {
                $table->dateTime('closed_at')->nullable()->after('resolved_at');
            }
            if (!Schema::hasColumn('tickets', 'sla_status')) {
                $table->enum('sla_status', ['within_sla', 'warning', 'breached'])->default('within_sla')->after('closed_at');
            }
            if (!Schema::hasColumn('tickets', 'sla_paused_at')) {
                $table->dateTime('sla_paused_at')->nullable()->after('sla_status');
            }
            if (!Schema::hasColumn('tickets', 'sla_paused_minutes')) {
                $table->integer('sla_paused_minutes')->default(0)->after('sla_paused_at');
            }
            if (!Schema::hasColumn('tickets', 'tags')) {
                $table->json('tags')->nullable()->after('sla_paused_minutes');
            }
            if (!Schema::hasColumn('tickets', 'merged_into_ticket_id')) {
                $table->unsignedBigInteger('merged_into_ticket_id')->nullable()->after('tags');
            }
        });

        // 3. Enhance ticket_messages table
        Schema::table('ticket_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('ticket_messages', 'company_id')) {
                $table->unsignedBigInteger('company_id')->default(1)->after('id');
            }
            if (!Schema::hasColumn('ticket_messages', 'sender_type')) {
                $table->enum('sender_type', ['agent', 'customer', 'system'])->default('agent')->after('user_id');
            }
            if (!Schema::hasColumn('ticket_messages', 'message_type')) {
                $table->enum('message_type', ['reply', 'internal_note', 'status_change', 'assignment'])->default('reply')->after('is_internal');
            }
            if (!Schema::hasColumn('ticket_messages', 'attachment_name')) {
                $table->string('attachment_name')->nullable()->after('message');
            }
            if (!Schema::hasColumn('ticket_messages', 'attachment_url')) {
                $table->string('attachment_url')->nullable()->after('attachment_name');
            }
        });

        // 4. Contact Messages Table
        Schema::create('support_contact_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('subject');
            $table->text('message');
            $table->enum('source', ['website', 'email', 'crm', 'support_form'])->default('website');
            $table->enum('status', ['new', 'read', 'replied', 'converted', 'archived', 'spam'])->default('new');
            $table->unsignedBigInteger('assigned_to_user_id')->nullable();
            $table->unsignedBigInteger('ticket_id')->nullable();
            $table->text('reply_message')->nullable();
            $table->dateTime('replied_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 5. Knowledge Base Categories
        Schema::create('knowledge_base_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('icon')->default('BookOpen');
            $table->integer('sort_order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // 6. Knowledge Base Articles
        Schema::create('knowledge_base_articles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('category_id')->index();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('title');
            $table->string('slug')->index();
            $table->text('description')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->enum('status', ['draft', 'review', 'published', 'archived'])->default('published');
            $table->enum('visibility', ['public', 'internal', 'customer_portal'])->default('public');
            $table->json('tags')->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('not_helpful_count')->default(0);
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 7. SLA Logs
        Schema::create('support_sla_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('ticket_id')->index();
            $table->unsignedBigInteger('sla_policy_id')->nullable();
            $table->string('event'); // created, first_response, resolved, breached, paused, resumed
            $table->integer('target_minutes')->nullable();
            $table->integer('actual_minutes')->nullable();
            $table->boolean('is_breached')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_sla_logs');
        Schema::dropIfExists('knowledge_base_articles');
        Schema::dropIfExists('knowledge_base_categories');
        Schema::dropIfExists('support_contact_messages');
        Schema::dropIfExists('support_sla_policies');
    }
};

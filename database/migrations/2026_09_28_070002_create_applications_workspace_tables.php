<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 5. FILE MANAGER MODULE
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name');
            $table->string('color')->default('#0F8B7A');
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('folders')->cascadeOnDelete();
            $table->index(['company_id', 'parent_id']);
        });

        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('folders')->nullOnDelete();
            $table->string('name');
            $table->string('disk')->default('local');
            $table->string('file_path');
            $table->string('file_type'); // pdf, docx, xlsx, etc.
            $table->string('mime_type')->nullable();
            $table->bigInteger('file_size')->default(0); // in bytes
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_recent')->default(true);
            $table->integer('download_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'folder_id']);
            $table->index(['company_id', 'file_type']);
            $table->index(['user_id', 'is_favorite']);
        });

        Schema::create('file_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->integer('version_number')->default(1);
            $table->string('file_path');
            $table->bigInteger('file_size')->default(0);
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('file_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('file_id')->nullable()->constrained('files')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('folders')->cascadeOnDelete();
            $table->foreignId('shared_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('shared_with_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('permission')->default('view'); // view, download, edit
            $table->string('share_token')->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('file_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('can_view')->default(true);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();

            $table->unique(['file_id', 'user_id']);
        });

        // 6. NOTES MODULE
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('#0F8B7A');
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->json('checklist')->nullable();
            $table->string('color')->default('#ffffff');
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'user_id']);
            $table->index(['user_id', 'is_pinned']);
        });

        Schema::create('note_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['note_id', 'tag_id']);
        });

        Schema::create('note_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->bigInteger('file_size')->default(0);
            $table->timestamps();
        });

        Schema::create('note_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained('notes')->cascadeOnDelete();
            $table->foreignId('shared_with_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('permission')->default('view'); // view, edit
            $table->timestamps();

            $table->unique(['note_id', 'shared_with_user_id']);
        });

        // 7. TO DO / TASKS EXTENSIONS
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            $table->foreignId('reporter_id')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            $table->date('start_date')->nullable()->after('due_date');
            $table->string('category')->default('General')->after('status');
            $table->integer('order')->default(0)->after('category');
            $table->dateTime('reminder_at')->nullable()->after('order');
            $table->softDeletes()->after('updated_at');
        });

        // Make project_id nullable on tasks table for general To Do tasks
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->change()->constrained('projects')->nullOnDelete();
        });

        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
        });

        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
        });

        Schema::create('task_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('title');
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });

        Schema::create('task_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->bigInteger('file_size')->default(0);
            $table->timestamps();
        });

        Schema::create('task_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('#0F8B7A');
            $table->timestamps();
        });

        // 8. WORKFLOW & APPROVALS MODULE
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('module'); // Purchase Request, Expense Claim, Leave Application, Contract Approval, etc.
            $table->string('trigger')->default('on_submit'); // on_submit, amount_threshold, manual
            $table->string('status')->default('active'); // active, inactive, draft
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'module']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->integer('step_order')->default(1);
            $table->string('name'); // Step 1: Department Manager, Step 2: Finance Manager, etc.
            $table->string('approver_role')->nullable();
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('sequential'); // sequential, parallel
            $table->integer('sla_hours')->default(24);
            $table->timestamps();

            $table->index(['workflow_id', 'step_order']);
        });

        Schema::create('workflow_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->string('reference_number')->unique(); // REQ-2026-0001
            $table->string('title');
            $table->string('module');
            $table->decimal('amount', 12, 2)->nullable();
            $table->json('data')->nullable();
            $table->foreignId('current_step_id')->nullable()->constrained('workflow_steps')->nullOnDelete();
            $table->string('status')->default('pending'); // pending, approved, rejected, changes_requested, cancelled
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['requester_id', 'status']);
            $table->index('reference_number');
        });

        Schema::create('workflow_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_request_id')->constrained('workflow_requests')->cascadeOnDelete();
            $table->foreignId('workflow_step_id')->constrained('workflow_steps')->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->string('action')->default('pending'); // pending, approved, rejected, changes_requested
            $table->text('comments')->nullable();
            $table->timestamp('action_taken_at')->nullable();
            $table->timestamps();

            $table->index(['workflow_request_id', 'action']);
        });

        Schema::create('workflow_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_request_id')->constrained('workflow_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment');
            $table->timestamps();
        });

        Schema::create('workflow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_request_id')->constrained('workflow_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('description');
            $table->timestamps();
        });

        // 9. CENTRALIZED NOTIFICATIONS MODULE
        Schema::create('erp_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // chat, call, calendar, email, file, note, task, workflow
            $table->string('title');
            $table->text('message');
            $table->string('link')->nullable();
            $table->string('icon')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['company_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_notifications');

        Schema::dropIfExists('workflow_logs');
        Schema::dropIfExists('workflow_comments');
        Schema::dropIfExists('workflow_approvals');
        Schema::dropIfExists('workflow_requests');
        Schema::dropIfExists('workflow_steps');
        Schema::dropIfExists('workflows');

        Schema::dropIfExists('task_labels');
        Schema::dropIfExists('task_attachments');
        Schema::dropIfExists('task_checklists');
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('task_assignees');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['reporter_id']);
            $table->dropColumn(['company_id', 'reporter_id', 'start_date', 'category', 'order', 'reminder_at', 'deleted_at']);
        });

        Schema::dropIfExists('note_shares');
        Schema::dropIfExists('note_attachments');
        Schema::dropIfExists('note_tags');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('tags');

        Schema::dropIfExists('file_permissions');
        Schema::dropIfExists('file_shares');
        Schema::dropIfExists('file_versions');
        Schema::dropIfExists('files');
        Schema::dropIfExists('folders');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Document Categories
        if (!Schema::hasTable('document_categories')) {
            Schema::create('document_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('icon')->default('Folder');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Document Folders (Hierarchical folder tree)
        if (!Schema::hasTable('document_folders')) {
            Schema::create('document_folders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('document_folders')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->string('color')->default('#2563EB');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 3. Documents
        if (!Schema::hasTable('documents')) {
            Schema::create('documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('folder_id')->nullable()->constrained('document_folders')->nullOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('document_categories')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('document_number')->unique(); // DOC-2026-000001
                $table->string('name');
                $table->string('file_type', 20)->default('pdf'); // pdf, doc, docx, xls, xlsx, ppt, pptx, jpg, png, zip, csv, txt
                $table->string('file_path')->nullable();
                $table->string('original_name')->nullable();
                $table->string('mime_type')->nullable();
                $table->bigInteger('file_size')->default(0); // in bytes
                $table->string('file_hash')->nullable();
                $table->string('disk')->default('local');
                $table->string('current_version')->default('v1.0');
                $table->string('confidentiality')->default('Internal'); // Public, Internal, Confidential, Highly Confidential
                $table->string('status')->default('Approved'); // Draft, Pending Review, Pending Approval, Approved, Rejected, Expired, Archived
                $table->date('issue_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->json('tags')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_archived')->default(false);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // 4. Document Versions
        if (!Schema::hasTable('document_versions')) {
            Schema::create('document_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
                $table->string('version'); // v1.0, v1.1, v2.0
                $table->string('file_path');
                $table->string('original_name');
                $table->string('mime_type')->nullable();
                $table->bigInteger('file_size')->default(0);
                $table->string('file_hash')->nullable();
                $table->text('change_summary')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('approved_at')->nullable();
                $table->timestamps();
            });
        }

        // 5. Document Tags & Relations
        if (!Schema::hasTable('document_tags')) {
            Schema::create('document_tags', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('name');
                $table->string('color')->default('#0F8B7A');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('document_tag_relations')) {
            Schema::create('document_tag_relations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
                $table->foreignId('tag_id')->constrained('document_tags')->cascadeOnDelete();
            });
        }

        // 6. Document Permissions (Granular RBAC)
        if (!Schema::hasTable('document_permissions')) {
            Schema::create('document_permissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->string('role')->nullable();
                $table->foreignId('department_id')->nullable()->constrained('departments')->cascadeOnDelete();
                $table->boolean('can_view')->default(true);
                $table->boolean('can_upload')->default(false);
                $table->boolean('can_edit')->default(false);
                $table->boolean('can_delete')->default(false);
                $table->boolean('can_download')->default(true);
                $table->boolean('can_share')->default(false);
                $table->boolean('can_approve')->default(false);
                $table->timestamps();
            });
        }

        // 7. Document Shares (Secure share links)
        if (!Schema::hasTable('document_shares')) {
            Schema::create('document_shares', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
                $table->foreignId('shared_by')->constrained('users')->cascadeOnDelete();
                $table->string('share_token', 64)->unique();
                $table->string('share_type')->default('user'); // user, department, role, public_link
                $table->foreignId('shared_with_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('shared_with_department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->string('shared_with_role')->nullable();
                $table->string('password_hash')->nullable();
                $table->dateTime('expires_at')->nullable();
                $table->boolean('allow_download')->default(true);
                $table->integer('access_count')->default(0);
                $table->timestamps();
            });
        }

        // 8. Document Workflows & Approval Steps
        if (!Schema::hasTable('document_workflows')) {
            Schema::create('document_workflows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('document_workflow_steps')) {
            Schema::create('document_workflow_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_id')->constrained('document_workflows')->cascadeOnDelete();
                $table->string('step_name'); // e.g. "Reviewer", "Legal Approver", "Finance Approver", "Final Signoff"
                $table->integer('sequence')->default(1);
                $table->string('approver_type')->default('role'); // user, role, department
                $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('approver_role')->nullable(); // e.g. "Finance Manager", "Legal Counsel"
                $table->foreignId('approver_department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->boolean('is_required')->default(true);
                $table->integer('sla_hours')->default(48);
                $table->timestamps();
            });
        }

        // 9. Document Approvals (Per-document workflow executions)
        if (!Schema::hasTable('document_approvals')) {
            Schema::create('document_approvals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
                $table->foreignId('workflow_step_id')->nullable()->constrained('document_workflow_steps')->nullOnDelete();
                $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status')->default('pending'); // pending, approved, rejected, changes_requested
                $table->text('comments')->nullable();
                $table->dateTime('acted_at')->nullable();
                $table->timestamps();
            });
        }

        // 10. Document Comments
        if (!Schema::hasTable('document_comments')) {
            Schema::create('document_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('comment');
                $table->timestamps();
            });
        }

        // 11. Document Compliance Records
        if (!Schema::hasTable('document_compliance')) {
            Schema::create('document_compliance', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
                $table->string('compliance_type'); // GST, Tax, Legal, ISO, Licenses, Certifications, Contracts, Government Documents, Employee Compliance, Vendor Compliance
                $table->string('title');
                $table->string('authority'); // e.g. GST Department, ISO Registrar, Municipal Corp
                $table->string('document_number');
                $table->date('issue_date');
                $table->date('expiry_date');
                $table->foreignId('responsible_person_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->string('status')->default('active'); // active, expiring_soon, expired, under_renewal
                $table->text('notes')->nullable();
                $table->string('attachment_path')->nullable();
                $table->timestamps();
            });
        }

        // 12. Policies & Manuals
        if (!Schema::hasTable('document_policies')) {
            Schema::create('document_policies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
                $table->string('title');
                $table->string('policy_number')->unique(); // POL-2026-0001
                $table->string('category')->default('HR'); // HR, IT, Security, Finance, Operations, Handbook
                $table->string('version')->default('v1.0');
                $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->date('effective_date');
                $table->date('review_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('status')->default('Published'); // Draft, Review, Approval, Published, Review Due, Archived
                $table->text('summary')->nullable();
                $table->string('attachment_path')->nullable();
                $table->timestamps();
            });
        }

        // 13. Document Audit Logs
        if (!Schema::hasTable('document_audit_logs')) {
            Schema::create('document_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('document_id')->nullable()->constrained('documents')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action'); // uploaded, version_added, approved, rejected, shared, downloaded, deleted, archived
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('document_audit_logs');
        Schema::dropIfExists('document_policies');
        Schema::dropIfExists('document_compliance');
        Schema::dropIfExists('document_comments');
        Schema::dropIfExists('document_approvals');
        Schema::dropIfExists('document_workflow_steps');
        Schema::dropIfExists('document_workflows');
        Schema::dropIfExists('document_shares');
        Schema::dropIfExists('document_permissions');
        Schema::dropIfExists('document_tag_relations');
        Schema::dropIfExists('document_tags');
        Schema::dropIfExists('document_versions');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('document_folders');
        Schema::dropIfExists('document_categories');
    }
};

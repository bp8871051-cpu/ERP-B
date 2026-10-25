<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Material Requests (Shared between Service Engineer, Project Engineer, and Store Executive)
        if (!Schema::hasTable('material_requests')) {
            Schema::create('material_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1);
                $table->string('request_number')->unique();
                $table->unsignedBigInteger('requested_by'); // User ID
                $table->string('request_type')->default('general'); // spare_parts, project_material, general
                $table->unsignedBigInteger('project_id')->nullable();
                $table->unsignedBigInteger('ticket_id')->nullable();
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->string('status')->default('pending'); // pending, approved, issued, partially_issued, rejected
                $table->string('priority')->default('medium'); // low, medium, high, urgent
                $table->date('required_date')->nullable();
                $table->string('site_location')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('issued_by')->nullable(); // Store Executive
                $table->timestamp('issued_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status']);
                $table->index(['requested_by', 'request_type']);
            });
        }

        if (!Schema::hasTable('material_request_items')) {
            Schema::create('material_request_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('material_request_id');
                $table->unsignedBigInteger('product_id');
                $table->integer('quantity_requested')->default(1);
                $table->integer('quantity_issued')->default(0);
                $table->string('unit')->default('pcs');
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->foreign('material_request_id')->references('id')->on('material_requests')->onDelete('cascade');
            });
        }

        // 2. Service Engineer: Site Visits & Service Reports
        if (!Schema::hasTable('service_site_visits')) {
            Schema::create('service_site_visits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1);
                $table->unsignedBigInteger('ticket_id')->nullable();
                $table->unsignedBigInteger('engineer_id'); // User ID
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('site_address')->nullable();
                $table->string('contact_phone')->nullable();
                $table->date('visit_date');
                $table->string('visit_time')->nullable();
                $table->string('service_type')->default('breakdown_repair'); // installation, preventive_maintenance, breakdown_repair, warranty_inspection
                $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, cancelled
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'engineer_id', 'visit_date']);
            });
        }

        if (!Schema::hasTable('service_reports')) {
            Schema::create('service_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1);
                $table->string('report_number')->unique();
                $table->unsignedBigInteger('ticket_id')->nullable();
                $table->unsignedBigInteger('engineer_id'); // User ID
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('equipment_name')->nullable();
                $table->string('serial_number')->nullable();
                $table->text('work_performed');
                $table->decimal('hours_spent', 5, 2)->default(1.0);
                $table->json('parts_used')->nullable(); // [{"product_id": 1, "name": "Pump Seal", "qty": 2}]
                $table->text('recommendations')->nullable();
                $table->string('customer_confirmation_status')->default('pending'); // pending, signed_off, rejected
                $table->text('sign_off_notes')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'engineer_id']);
                $table->index(['customer_confirmation_status']);
            });
        }

        // 3. Quality Control: Inspections & NCRs
        if (!Schema::hasTable('qc_inspections')) {
            Schema::create('qc_inspections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1);
                $table->string('inspection_number')->unique();
                $table->unsignedBigInteger('purchase_order_id')->nullable();
                $table->unsignedBigInteger('purchase_id')->nullable();
                $table->unsignedBigInteger('product_id');
                $table->unsignedBigInteger('warehouse_id')->nullable();
                $table->unsignedBigInteger('inspector_id'); // User ID
                $table->string('lot_number')->nullable();
                $table->string('inspection_type')->default('incoming_material'); // incoming_material, in_process, final_product, reinspection
                $table->integer('batch_quantity')->default(100);
                $table->integer('sample_size')->default(10);
                $table->string('status')->default('pending'); // pending, in_progress, passed, failed, quality_hold
                $table->timestamp('decision_date')->nullable();
                $table->integer('defect_count')->default(0);
                $table->json('measurements')->nullable(); // [{"parameter": "Tolerance", "standard": "±0.05mm", "actual": "+0.02mm", "status": "pass"}]
                $table->json('checklist')->nullable();
                $table->text('notes')->nullable();
                $table->date('reinspection_date')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status']);
                $table->index(['inspector_id', 'inspection_type']);
            });
        }

        if (!Schema::hasTable('qc_ncrs')) {
            Schema::create('qc_ncrs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1);
                $table->string('ncr_number')->unique();
                $table->unsignedBigInteger('inspection_id')->nullable();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->unsignedBigInteger('reported_by'); // User ID
                $table->string('title');
                $table->text('description');
                $table->string('severity')->default('major'); // minor, major, critical
                $table->string('status')->default('open'); // open, under_review, capa_pending, resolved, closed
                $table->text('root_cause')->nullable();
                $table->text('corrective_action')->nullable(); // CAPA
                $table->unsignedBigInteger('action_assigned_to')->nullable(); // User ID
                $table->date('action_due_date')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'status', 'severity']);
            });
        }

        // 4. Project Engineer: Progress Reports, Technical Reviews & Engineering Issues
        if (!Schema::hasTable('project_site_reports')) {
            Schema::create('project_site_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1);
                $table->unsignedBigInteger('project_id');
                $table->unsignedBigInteger('engineer_id'); // User ID
                $table->date('report_date');
                $table->text('work_completed');
                $table->string('site_conditions')->nullable();
                $table->text('blockers')->nullable();
                $table->integer('manpower_count')->default(0);
                $table->timestamps();

                $table->index(['company_id', 'project_id', 'engineer_id']);
            });
        }

        if (!Schema::hasTable('project_engineering_issues')) {
            Schema::create('project_engineering_issues', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1);
                $table->unsignedBigInteger('project_id');
                $table->unsignedBigInteger('engineer_id'); // User ID
                $table->string('title');
                $table->text('description');
                $table->string('severity')->default('medium'); // low, medium, high, critical
                $table->string('status')->default('open'); // open, in_progress, resolved, mitigated
                $table->text('mitigation_plan')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'project_id', 'status']);
            });
        }

        if (!Schema::hasTable('project_technical_reviews')) {
            Schema::create('project_technical_reviews', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1);
                $table->unsignedBigInteger('project_id');
                $table->unsignedBigInteger('submitted_by'); // User ID
                $table->unsignedBigInteger('reviewer_id')->nullable(); // User ID
                $table->string('title');
                $table->string('deliverable_type')->default('drawing'); // drawing, calculation, specification, site_plan
                $table->string('status')->default('pending'); // pending, under_review, approved, revision_required
                $table->text('submission_notes')->nullable();
                $table->text('review_comments')->nullable();
                $table->string('version')->default('1.0');
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'project_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_technical_reviews');
        Schema::dropIfExists('project_engineering_issues');
        Schema::dropIfExists('project_site_reports');
        Schema::dropIfExists('qc_ncrs');
        Schema::dropIfExists('qc_inspections');
        Schema::dropIfExists('service_reports');
        Schema::dropIfExists('service_site_visits');
        Schema::dropIfExists('material_request_items');
        Schema::dropIfExists('material_requests');
    }
};

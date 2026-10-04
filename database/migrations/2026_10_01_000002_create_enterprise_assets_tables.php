<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Asset Categories
        if (!Schema::hasTable('asset_categories')) {
            Schema::create('asset_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->string('depreciation_method')->default('straight_line'); // straight_line, declining_balance, double_declining, units_of_production
                $table->integer('useful_life_years')->default(5);
                $table->decimal('salvage_percentage', 5, 2)->default(5.00); // 5% default
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Asset Locations
        if (!Schema::hasTable('asset_locations')) {
            Schema::create('asset_locations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('name');
                $table->string('building')->nullable();
                $table->string('floor')->nullable();
                $table->string('room')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 3. Assets Register
        if (!Schema::hasTable('assets')) {
            Schema::create('assets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
                $table->foreignId('assigned_to')->nullable()->constrained('employees')->nullOnDelete();
                $table->string('asset_code')->unique(); // AST-2026-000001
                $table->string('name');
                $table->string('subcategory')->nullable();
                $table->string('serial_number')->nullable();
                $table->string('model_number')->nullable();
                $table->string('brand')->nullable();
                $table->string('vendor_name')->nullable();
                $table->date('purchase_date');
                $table->string('purchase_invoice')->nullable();
                $table->decimal('purchase_cost', 14, 2);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('total_cost', 14, 2);
                $table->decimal('salvage_value', 12, 2)->default(0);
                $table->integer('useful_life_years')->default(5);
                $table->string('depreciation_method')->default('straight_line');
                $table->decimal('current_book_value', 14, 2);
                $table->decimal('accumulated_depreciation', 14, 2)->default(0);
                $table->date('warranty_start')->nullable();
                $table->date('warranty_end')->nullable();
                $table->string('status')->default('Available'); // Available, Assigned, Under Maintenance, Lost, Damaged, Disposed, Retired
                $table->string('condition')->default('Good'); // New, Good, Fair, Poor, Damaged
                $table->text('notes')->nullable();
                $table->string('image')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // 4. Asset Assignments
        if (!Schema::hasTable('asset_assignments')) {
            Schema::create('asset_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('location_id')->nullable()->constrained('asset_locations')->nullOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('assigned_date');
                $table->date('expected_return_date')->nullable();
                $table->date('actual_return_date')->nullable();
                $table->string('condition')->default('Good');
                $table->string('status')->default('Assigned'); // Assigned, Returned, Damaged, Transferred
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 5. Asset Assignment History
        if (!Schema::hasTable('asset_assignment_history')) {
            Schema::create('asset_assignment_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->string('previous_holder')->nullable();
                $table->string('new_holder')->nullable();
                $table->string('previous_location')->nullable();
                $table->string('new_location')->nullable();
                $table->date('assigned_date');
                $table->date('returned_date')->nullable();
                $table->string('condition')->default('Good');
                $table->string('assigned_by_name')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 6. Asset Depreciations (Annual/Periodic Calculation Records)
        if (!Schema::hasTable('asset_depreciations')) {
            Schema::create('asset_depreciations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->string('financial_year'); // e.g. 2025-2026
                $table->date('period_date');
                $table->string('method');
                $table->decimal('purchase_cost', 14, 2);
                $table->decimal('salvage_value', 12, 2);
                $table->decimal('depreciation_rate', 5, 2)->default(0);
                $table->decimal('depreciation_amount', 12, 2);
                $table->decimal('accumulated_depreciation', 14, 2);
                $table->decimal('ending_book_value', 14, 2);
                $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 7. Asset Depreciation Schedules (Forecasted schedule)
        if (!Schema::hasTable('asset_depreciation_schedules')) {
            Schema::create('asset_depreciation_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->string('schedule_type')->default('monthly'); // monthly, quarterly, yearly
                $table->string('period_label'); // e.g. "Month 1 (Oct 2026)", "Year 1"
                $table->date('period_date');
                $table->decimal('beginning_value', 14, 2);
                $table->decimal('depreciation_amount', 12, 2);
                $table->decimal('accumulated_depreciation', 14, 2);
                $table->decimal('ending_value', 14, 2);
                $table->string('status')->default('projected'); // projected, posted
                $table->timestamps();
            });
        }

        // 8. Asset Maintenance
        if (!Schema::hasTable('asset_maintenance')) {
            Schema::create('asset_maintenance', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->string('maintenance_type')->default('Preventive'); // Preventive, Corrective, Calibration, Upgrade, Inspection
                $table->string('priority')->default('Medium'); // Low, Medium, High, Urgent
                $table->string('issue');
                $table->text('description')->nullable();
                $table->string('vendor')->nullable();
                $table->string('assigned_technician')->nullable();
                $table->date('start_date');
                $table->date('expected_completion')->nullable();
                $table->date('actual_completion')->nullable();
                $table->decimal('estimated_cost', 12, 2)->default(0);
                $table->decimal('actual_cost', 12, 2)->default(0);
                $table->string('status')->default('Requested'); // Requested, Scheduled, In Progress, Completed, Cancelled
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 9. Asset Disposals
        if (!Schema::hasTable('asset_disposals')) {
            Schema::create('asset_disposals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->string('disposal_type')->default('Sell'); // Sell, Scrap, Donate, Write Off, Lost
                $table->date('disposal_date');
                $table->decimal('book_value', 14, 2);
                $table->decimal('sale_value', 14, 2)->default(0);
                $table->decimal('loss_gain', 14, 2)->default(0); // sale_value - book_value
                $table->string('reason');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('approved_at')->nullable();
                $table->string('status')->default('Pending'); // Pending, Approved, Rejected, Disposed
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 10. Asset Documents / Attachments
        if (!Schema::hasTable('asset_documents')) {
            Schema::create('asset_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
                $table->string('name');
                $table->string('file_path');
                $table->string('document_type')->default('invoice'); // invoice, warranty, manual, receipt
                $table->integer('file_size')->default(0);
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 11. Asset Audit Logs
        if (!Schema::hasTable('asset_audit_logs')) {
            Schema::create('asset_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('asset_id')->nullable()->constrained('assets')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action'); // created, updated, assigned, returned, maintained, disposed
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_audit_logs');
        Schema::dropIfExists('asset_documents');
        Schema::dropIfExists('asset_disposals');
        Schema::dropIfExists('asset_maintenance');
        Schema::dropIfExists('asset_depreciation_schedules');
        Schema::dropIfExists('asset_depreciations');
        Schema::dropIfExists('asset_assignment_history');
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_locations');
        Schema::dropIfExists('asset_categories');
    }
};

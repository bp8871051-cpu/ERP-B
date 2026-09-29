<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->string('employee_code')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->date('joining_date');
            $table->decimal('salary', 12, 2)->default(0);
            $table->string('status')->default('active'); // active, on_leave, terminated
            $table->string('employment_type')->default('Full-Time'); // Full-Time, Part-Time, Contract
            $table->string('avatar')->nullable();
            $table->timestamps();
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('status')->default('present'); // present, late, absent, remote
            $table->decimal('working_hours', 5, 2)->default(8.00);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'date']);
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('days_per_year')->default(15);
            $table->timestamps();
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('days_count')->default(1);
            $table->text('reason')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('month'); // January, February...
            $table->integer('year');
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2);
            $table->string('status')->default('paid'); // paid, pending, processing
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });

        Schema::create('job_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('title');
            $table->integer('vacancies')->default(1);
            $table->string('status')->default('open'); // open, closed, on_hold
            $table->text('requirements')->nullable();
            $table->timestamps();
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_position_id')->nullable()->constrained('job_positions')->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('experience')->nullable();
            $table->string('stage')->default('screening'); // screening, interview, selected, hired, rejected
            $table->decimal('expected_salary', 12, 2)->nullable();
            $table->date('applied_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('job_positions');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('designations');
    }
};

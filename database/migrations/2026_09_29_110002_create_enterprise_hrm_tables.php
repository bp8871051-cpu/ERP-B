<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhance Departments Table
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'department_code')) {
                $table->string('department_code')->nullable()->after('name');
            }
            if (!Schema::hasColumn('departments', 'manager_id')) {
                $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('departments', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->constrained('departments')->nullOnDelete();
            }
            if (!Schema::hasColumn('departments', 'status')) {
                $table->string('status')->default('active'); // active, inactive
            }
            if (!Schema::hasColumn('departments', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 2. Enhance Designations Table
        Schema::table('designations', function (Blueprint $table) {
            if (!Schema::hasColumn('designations', 'code')) {
                $table->string('code')->nullable()->after('title');
            }
            if (!Schema::hasColumn('designations', 'level')) {
                $table->string('level')->nullable()->after('code'); // Entry, Mid, Senior, Lead, Executive
            }
            if (!Schema::hasColumn('designations', 'min_salary')) {
                $table->decimal('min_salary', 12, 2)->default(0)->after('description');
            }
            if (!Schema::hasColumn('designations', 'max_salary')) {
                $table->decimal('max_salary', 12, 2)->default(0)->after('min_salary');
            }
            if (!Schema::hasColumn('designations', 'status')) {
                $table->string('status')->default('active')->after('max_salary');
            }
            if (!Schema::hasColumn('designations', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 3. Enhance Employees Table
        Schema::table('employees', function (Blueprint $table) {
            if (!Schema::hasColumn('employees', 'middle_name')) {
                $table->string('middle_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('employees', 'gender')) {
                $table->string('gender')->default('male')->after('last_name'); // male, female, other
            }
            if (!Schema::hasColumn('employees', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('gender');
            }
            if (!Schema::hasColumn('employees', 'alternate_phone')) {
                $table->string('alternate_phone')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('employees', 'manager_id')) {
                $table->foreignId('manager_id')->nullable()->after('designation_id')->constrained('employees')->nullOnDelete();
            }
            if (!Schema::hasColumn('employees', 'work_location')) {
                $table->string('work_location')->default('Headquarters')->after('employment_type');
            }
            if (!Schema::hasColumn('employees', 'address')) {
                $table->text('address')->nullable()->after('work_location');
            }
            if (!Schema::hasColumn('employees', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            if (!Schema::hasColumn('employees', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (!Schema::hasColumn('employees', 'country')) {
                $table->string('country')->nullable()->default('US')->after('state');
            }
            if (!Schema::hasColumn('employees', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('country');
            }
            if (!Schema::hasColumn('employees', 'pay_frequency')) {
                $table->string('pay_frequency')->default('monthly')->after('salary'); // monthly, weekly, biweekly
            }
            if (!Schema::hasColumn('employees', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('pay_frequency');
            }
            if (!Schema::hasColumn('employees', 'account_holder')) {
                $table->string('account_holder')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('employees', 'account_number')) {
                $table->string('account_number')->nullable()->after('account_holder');
            }
            if (!Schema::hasColumn('employees', 'ifsc')) {
                $table->string('ifsc')->nullable()->after('account_number');
            }
            if (!Schema::hasColumn('employees', 'pan')) {
                $table->string('pan')->nullable()->after('ifsc');
            }
            if (!Schema::hasColumn('employees', 'aadhaar')) {
                $table->string('aadhaar')->nullable()->after('pan');
            }
            if (!Schema::hasColumn('employees', 'tax_info')) {
                $table->text('tax_info')->nullable()->after('aadhaar');
            }
            if (!Schema::hasColumn('employees', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('tax_info');
            }
            if (!Schema::hasColumn('employees', 'emergency_contact_relationship')) {
                $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_name');
            }
            if (!Schema::hasColumn('employees', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_relationship');
            }
            if (!Schema::hasColumn('employees', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 4. Employee Documents Table
        if (!Schema::hasTable('employee_documents')) {
            Schema::create('employee_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('title');
                $table->string('document_type'); // resume, id_proof, joining_letter, contract, certificate, other
                $table->string('file_path');
                $table->integer('file_size')->nullable();
                $table->date('expiry_date')->nullable();
                $table->timestamps();
            });
        }

        // 5. Employee Salary Structures Table
        if (!Schema::hasTable('employee_salary_structures')) {
            Schema::create('employee_salary_structures', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->decimal('basic_salary', 12, 2)->default(0);
                $table->decimal('hra', 12, 2)->default(0);
                $table->decimal('transport_allowance', 12, 2)->default(0);
                $table->decimal('medical_allowance', 12, 2)->default(0);
                $table->decimal('special_allowance', 12, 2)->default(0);
                $table->decimal('other_allowances', 12, 2)->default(0);
                $table->decimal('pf', 12, 2)->default(0);
                $table->decimal('esi', 12, 2)->default(0);
                $table->decimal('professional_tax', 12, 2)->default(0);
                $table->decimal('tds', 12, 2)->default(0);
                $table->decimal('other_deductions', 12, 2)->default(0);
                $table->date('effective_date')->nullable();
                $table->timestamps();
            });
        }

        // 6. Enhance Attendances Table
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('attendances', 'working_minutes')) {
                $table->integer('working_minutes')->default(480)->after('working_hours');
            }
            if (!Schema::hasColumn('attendances', 'late_minutes')) {
                $table->integer('late_minutes')->default(0)->after('working_minutes');
            }
            if (!Schema::hasColumn('attendances', 'overtime_minutes')) {
                $table->integer('overtime_minutes')->default(0)->after('late_minutes');
            }
            if (!Schema::hasColumn('attendances', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            }
        });

        // 7. Attendance Logs (Check In/Out details)
        if (!Schema::hasTable('attendance_logs')) {
            Schema::create('attendance_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('attendance_id')->nullable()->constrained('attendances')->cascadeOnDelete();
                $table->string('type'); // in, out
                $table->timestamp('logged_at');
                $table->string('device')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
            });
        }

        // 8. Enhance Leave Types Table
        Schema::table('leave_types', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_types', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('leave_types', 'code')) {
                $table->string('code')->nullable()->after('name');
            }
            if (!Schema::hasColumn('leave_types', 'is_paid')) {
                $table->boolean('is_paid')->default(true)->after('days_per_year');
            }
            if (!Schema::hasColumn('leave_types', 'carry_forward')) {
                $table->boolean('carry_forward')->default(false)->after('is_paid');
            }
            if (!Schema::hasColumn('leave_types', 'status')) {
                $table->string('status')->default('active')->after('carry_forward');
            }
        });

        // 9. Leave Balances Table
        if (!Schema::hasTable('leave_balances')) {
            Schema::create('leave_balances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
                $table->integer('year');
                $table->decimal('allocated', 5, 1)->default(0);
                $table->decimal('used', 5, 1)->default(0);
                $table->decimal('pending', 5, 1)->default(0);
                $table->decimal('remaining', 5, 1)->default(0);
                $table->timestamps();

                $table->unique(['employee_id', 'leave_type_id', 'year']);
            });
        }

        // 10. Enhance Leave Requests Table
        Schema::table('leave_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_requests', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('leave_requests', 'attachment')) {
                $table->string('attachment')->nullable()->after('reason');
            }
            if (!Schema::hasColumn('leave_requests', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (!Schema::hasColumn('leave_requests', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('approved_at');
            }
        });

        // 11. Holidays Table
        if (!Schema::hasTable('holidays')) {
            Schema::create('holidays', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('name');
                $table->date('date');
                $table->string('type')->default('public'); // public, company, optional
                $table->text('description')->nullable();
                $table->json('applicable_departments')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();

                $table->unique(['company_id', 'date']);
            });
        }

        // 12. Payroll Periods Table
        if (!Schema::hasTable('payroll_periods')) {
            Schema::create('payroll_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('name'); // March 2026 Payroll
                $table->date('start_date');
                $table->date('end_date');
                $table->date('payment_date')->nullable();
                $table->string('status')->default('draft'); // draft, calculated, reviewed, approved, processed, paid
                $table->timestamps();
            });
        }

        // 13. Enhance Payrolls Table
        Schema::table('payrolls', function (Blueprint $table) {
            if (!Schema::hasColumn('payrolls', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('payrolls', 'payroll_period_id')) {
                $table->foreignId('payroll_period_id')->nullable()->after('employee_id')->constrained('payroll_periods')->nullOnDelete();
            }
            if (!Schema::hasColumn('payrolls', 'gross_salary')) {
                $table->decimal('gross_salary', 12, 2)->default(0)->after('allowances');
            }
            if (!Schema::hasColumn('payrolls', 'overtime_amount')) {
                $table->decimal('overtime_amount', 12, 2)->default(0)->after('deductions');
            }
            if (!Schema::hasColumn('payrolls', 'working_days')) {
                $table->integer('working_days')->default(30)->after('net_salary');
            }
            if (!Schema::hasColumn('payrolls', 'present_days')) {
                $table->integer('present_days')->default(30)->after('working_days');
            }
            if (!Schema::hasColumn('payrolls', 'absent_days')) {
                $table->integer('absent_days')->default(0)->after('present_days');
            }
            if (!Schema::hasColumn('payrolls', 'paid_leaves')) {
                $table->integer('paid_leaves')->default(0)->after('absent_days');
            }
            if (!Schema::hasColumn('payrolls', 'unpaid_leaves')) {
                $table->integer('unpaid_leaves')->default(0)->after('paid_leaves');
            }
            if (!Schema::hasColumn('payrolls', 'overtime_hours')) {
                $table->decimal('overtime_hours', 5, 2)->default(0)->after('unpaid_leaves');
            }
            if (!Schema::hasColumn('payrolls', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('payment_date');
            }
            if (!Schema::hasColumn('payrolls', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('processed_at');
            }
        });

        // 14. Payroll Items Table
        if (!Schema::hasTable('payroll_items')) {
            Schema::create('payroll_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
                $table->string('type'); // allowance, deduction
                $table->string('name');
                $table->decimal('amount', 12, 2);
                $table->timestamps();
            });
        }

        // 15. Payslips Table
        if (!Schema::hasTable('payslips')) {
            Schema::create('payslips', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
                $table->string('payslip_number')->unique();
                $table->string('file_path')->nullable();
                $table->timestamp('generated_at')->nullable();
                $table->timestamps();
            });
        }

        // 16. Enhance Job Positions Table
        Schema::table('job_positions', function (Blueprint $table) {
            if (!Schema::hasColumn('job_positions', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('job_positions', 'job_code')) {
                $table->string('job_code')->nullable()->after('title');
            }
            if (!Schema::hasColumn('job_positions', 'designation_id')) {
                $table->foreignId('designation_id')->nullable()->after('department_id')->constrained('designations')->nullOnDelete();
            }
            if (!Schema::hasColumn('job_positions', 'location')) {
                $table->string('location')->default('Headquarters')->after('designation_id');
            }
            if (!Schema::hasColumn('job_positions', 'employment_type')) {
                $table->string('employment_type')->default('Full-Time')->after('location');
            }
            if (!Schema::hasColumn('job_positions', 'experience')) {
                $table->string('experience')->nullable()->after('employment_type');
            }
            if (!Schema::hasColumn('job_positions', 'min_salary')) {
                $table->decimal('min_salary', 12, 2)->default(0)->after('experience');
            }
            if (!Schema::hasColumn('job_positions', 'max_salary')) {
                $table->decimal('max_salary', 12, 2)->default(0)->after('min_salary');
            }
            if (!Schema::hasColumn('job_positions', 'description')) {
                $table->text('description')->nullable()->after('requirements');
            }
        });

        // 17. Enhance Candidates Table
        Schema::table('candidates', function (Blueprint $table) {
            if (!Schema::hasColumn('candidates', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('candidates', 'skills')) {
                $table->text('skills')->nullable()->after('experience');
            }
            if (!Schema::hasColumn('candidates', 'location')) {
                $table->string('location')->nullable()->after('skills');
            }
            if (!Schema::hasColumn('candidates', 'source')) {
                $table->string('source')->default('LinkedIn')->after('location');
            }
            if (!Schema::hasColumn('candidates', 'resume_path')) {
                $table->string('resume_path')->nullable()->after('source');
            }
            if (!Schema::hasColumn('candidates', 'notes')) {
                $table->text('notes')->nullable()->after('resume_path');
            }
        });

        // 18. Job Applications Table
        if (!Schema::hasTable('job_applications')) {
            Schema::create('job_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
                $table->foreignId('job_position_id')->constrained('job_positions')->cascadeOnDelete();
                $table->date('applied_date');
                $table->string('stage')->default('applied'); // applied, screening, shortlisted, interview, selected, offer, hired, rejected, withdrawn
                $table->integer('rating')->default(0); // 1 to 5
                $table->text('notes')->nullable();
                $table->string('status')->default('active'); // active, closed
                $table->timestamps();
            });
        }

        // 19. Interviews Table
        if (!Schema::hasTable('interviews')) {
            Schema::create('interviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
                $table->foreignId('job_position_id')->constrained('job_positions')->cascadeOnDelete();
                $table->string('interview_type')->default('video'); // phone, video, in_person, technical, hr
                $table->date('interview_date');
                $table->time('interview_time');
                $table->string('interviewers')->nullable();
                $table->string('meeting_link')->nullable();
                $table->text('feedback')->nullable();
                $table->integer('rating')->nullable(); // 1 - 5
                $table->string('status')->default('scheduled'); // scheduled, completed, cancelled, rescheduled
                $table->timestamps();
            });
        }

        // 20. Job Offers Table
        if (!Schema::hasTable('job_offers')) {
            Schema::create('job_offers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
                $table->foreignId('job_position_id')->constrained('job_positions')->cascadeOnDelete();
                $table->date('offer_date');
                $table->date('joining_date');
                $table->decimal('salary', 12, 2);
                $table->text('benefits')->nullable();
                $table->string('status')->default('draft'); // draft, sent, accepted, rejected, expired
                $table->string('offer_letter_path')->nullable();
                $table->timestamps();
            });
        }

        // 21. Performance Cycles Table
        if (!Schema::hasTable('performance_cycles')) {
            Schema::create('performance_cycles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('title'); // 2026 Annual Review
                $table->integer('year');
                $table->date('start_date');
                $table->date('end_date');
                $table->string('status')->default('active'); // draft, active, closed
                $table->timestamps();
            });
        }

        // 22. Performance Goals Table
        if (!Schema::hasTable('performance_goals')) {
            Schema::create('performance_goals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('cycle_id')->constrained('performance_cycles')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('kpi')->nullable();
                $table->integer('weight')->default(20); // percentage weight
                $table->string('target_value')->nullable();
                $table->string('achieved_value')->nullable();
                $table->string('status')->default('in_progress'); // in_progress, achieved, exceeded, missed
                $table->timestamps();
            });
        }

        // 23. Performance Reviews Table
        if (!Schema::hasTable('performance_reviews')) {
            Schema::create('performance_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('cycle_id')->constrained('performance_cycles')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
                $table->integer('self_rating')->nullable(); // 1 to 5
                $table->text('self_comments')->nullable();
                $table->integer('manager_rating')->nullable(); // 1 to 5
                $table->text('manager_comments')->nullable();
                $table->integer('final_rating')->nullable(); // 1 to 5
                $table->decimal('final_score', 5, 2)->nullable();
                $table->string('status')->default('draft'); // draft, self_review, manager_review, completed
                $table->timestamps();
            });
        }

        // 24. Training Programs Table
        if (!Schema::hasTable('training_programs')) {
            Schema::create('training_programs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('title');
                $table->string('trainer')->nullable();
                $table->text('description')->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->integer('duration_hours')->default(8);
                $table->string('location')->nullable();
                $table->decimal('cost', 12, 2)->default(0);
                $table->string('status')->default('planned'); // planned, active, completed, cancelled
                $table->timestamps();
            });
        }

        // 25. Training Sessions Table
        if (!Schema::hasTable('training_sessions')) {
            Schema::create('training_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('program_id')->constrained('training_programs')->cascadeOnDelete();
                $table->string('title');
                $table->date('session_date');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->string('room')->nullable();
                $table->timestamps();
            });
        }

        // 26. Employee Trainings (Enrollment) Table
        if (!Schema::hasTable('employee_trainings')) {
            Schema::create('employee_trainings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('program_id')->constrained('training_programs')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->date('enrollment_date');
                $table->string('completion_status')->default('enrolled'); // enrolled, in_progress, completed, failed
                $table->integer('score')->nullable();
                $table->string('certificate_path')->nullable();
                $table->timestamps();
            });
        }

        // 27. HR Activity Logs Table
        if (!Schema::hasTable('hr_activity_logs')) {
            Schema::create('hr_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action'); // employee_created, leave_approved, payroll_processed, etc.
                $table->string('module')->default('hrm');
                $table->unsignedBigInteger('record_id')->nullable();
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_activity_logs');
        Schema::dropIfExists('employee_trainings');
        Schema::dropIfExists('training_sessions');
        Schema::dropIfExists('training_programs');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('performance_goals');
        Schema::dropIfExists('performance_cycles');
        Schema::dropIfExists('job_offers');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('attendance_logs');
        Schema::dropIfExists('employee_salary_structures');
        Schema::dropIfExists('employee_documents');
    }
};

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
        if (!Schema::hasTable('advance_payments')) {
            Schema::create('advance_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->default(1)->index();
                $table->string('advance_number')->unique();
                $table->string('title');
                $table->text('description')->nullable();
                $table->enum('advance_type', ['employees', 'vendor', 'project'])->default('employees')->index();
                $table->unsignedBigInteger('employee_id')->nullable()->index();
                $table->unsignedBigInteger('vendor_id')->nullable()->index();
                $table->unsignedBigInteger('project_id')->nullable()->index();
                $table->decimal('project_amount', 12, 2)->default(0);
                $table->date('advance_date');
                $table->date('expected_settlement_date')->nullable();
                $table->string('purpose')->nullable();
                $table->text('note')->nullable();
                $table->enum('status', ['pending', 'approved', 'settled', 'rejected'])->default('pending')->index();
                $table->enum('approval_level', ['manager', 'finance', 'director', 'completed'])->default('manager')->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->timestamp('approved_at')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advance_payments');
    }
};


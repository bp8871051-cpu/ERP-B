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
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'project_id')) {
                $table->foreignId('project_id')->nullable()->after('category_id')->constrained('projects')->nullOnDelete();
            }
            if (!Schema::hasColumn('documents', 'checker_id')) {
                $table->foreignId('checker_id')->nullable()->after('owner_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('documents', 'priority')) {
                $table->string('priority')->default('medium')->after('status'); // low, medium, high, urgent
            }
            if (!Schema::hasColumn('documents', 'verification_status')) {
                $table->string('verification_status')->default('pending')->after('priority'); // pending, under_review, verified, rejected, revision_required
            }
            if (!Schema::hasColumn('documents', 'verification_notes')) {
                $table->text('verification_notes')->nullable()->after('verification_status');
            }
            if (!Schema::hasColumn('documents', 'verified_at')) {
                $table->dateTime('verified_at')->nullable()->after('verification_notes');
            }
            if (!Schema::hasColumn('documents', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('documents', 'revision_reason')) {
                $table->text('revision_reason')->nullable()->after('verified_by');
            }
            if (!Schema::hasColumn('documents', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('revision_reason');
            }
            if (!Schema::hasColumn('documents', 'checklist')) {
                $table->json('checklist')->nullable()->after('rejection_reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_id');
            $table->dropConstrainedForeignId('checker_id');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn([
                'priority',
                'verification_status',
                'verification_notes',
                'verified_at',
                'revision_reason',
                'rejection_reason',
                'checklist',
            ]);
        });
    }
};

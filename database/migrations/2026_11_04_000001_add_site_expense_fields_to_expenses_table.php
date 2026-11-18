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
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                if (!Schema::hasColumn('expenses', 'title')) {
                    $table->string('title')->nullable()->after('expense_number');
                }
                if (!Schema::hasColumn('expenses', 'project_id')) {
                    $table->unsignedBigInteger('project_id')->nullable()->after('vendor_id')->index();
                }
                if (!Schema::hasColumn('expenses', 'site_location')) {
                    $table->string('site_location')->nullable()->after('project_id');
                }
                if (!Schema::hasColumn('expenses', 'vendor_number')) {
                    $table->string('vendor_number')->nullable()->after('site_location');
                }
                if (!Schema::hasColumn('expenses', 'receipt_number')) {
                    $table->string('receipt_number')->nullable()->after('vendor_number');
                }
                if (!Schema::hasColumn('expenses', 'additional_note')) {
                    $table->text('additional_note')->nullable()->after('notes');
                }
                if (!Schema::hasColumn('expenses', 'document')) {
                    $table->string('document')->nullable()->after('attachment');
                }
                if (!Schema::hasColumn('expenses', 'is_site_expense')) {
                    $table->boolean('is_site_expense')->default(false)->after('document')->index();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                $cols = ['title', 'project_id', 'site_location', 'vendor_number', 'receipt_number', 'additional_note', 'document', 'is_site_expense'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('expenses', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};


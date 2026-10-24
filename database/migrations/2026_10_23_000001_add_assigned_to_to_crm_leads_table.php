<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('crm_leads') && !Schema::hasColumn('crm_leads', 'assigned_to')) {
            Schema::table('crm_leads', function (Blueprint $table) {
                $table->foreignId('assigned_to')
                    ->nullable()
                    ->after('company_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });

            // Safely backfill existing records from owner_id
            DB::statement('UPDATE crm_leads SET assigned_to = owner_id WHERE assigned_to IS NULL AND owner_id IS NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('crm_leads') && Schema::hasColumn('crm_leads', 'assigned_to')) {
            Schema::table('crm_leads', function (Blueprint $table) {
                $table->dropForeign(['assigned_to']);
                $table->dropColumn('assigned_to');
            });
        }
    }
};

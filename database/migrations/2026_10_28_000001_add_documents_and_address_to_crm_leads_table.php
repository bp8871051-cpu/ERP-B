<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_leads')) {
            Schema::table('crm_leads', function (Blueprint $table) {
                if (!Schema::hasColumn('crm_leads', 'address')) {
                    $table->text('address')->nullable()->after('phone');
                }
                if (!Schema::hasColumn('crm_leads', 'pincode')) {
                    $table->string('pincode', 20)->nullable()->after('address');
                }
                if (!Schema::hasColumn('crm_leads', 'city')) {
                    $table->string('city', 100)->nullable()->after('pincode');
                }
                if (!Schema::hasColumn('crm_leads', 'state')) {
                    $table->string('state', 100)->nullable()->after('city');
                }
                if (!Schema::hasColumn('crm_leads', 'country')) {
                    $table->string('country', 100)->default('India')->nullable()->after('state');
                }
                if (!Schema::hasColumn('crm_leads', 'linkedin')) {
                    $table->string('linkedin', 255)->nullable()->after('website');
                }
                if (!Schema::hasColumn('crm_leads', 'other_link')) {
                    $table->string('other_link', 255)->nullable()->after('linkedin');
                }
                if (!Schema::hasColumn('crm_leads', 'documents')) {
                    $table->longText('documents')->nullable()->after('tags');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('crm_leads')) {
            Schema::table('crm_leads', function (Blueprint $table) {
                $cols = ['address', 'pincode', 'city', 'state', 'country', 'linkedin', 'other_link', 'documents'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('crm_leads', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};

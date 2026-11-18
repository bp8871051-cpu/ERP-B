<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add other_source_details and lead_source_name to crm_leads
        Schema::table('crm_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_leads', 'lead_source_name')) {
                $table->string('lead_source_name', 100)->nullable()->after('lead_source_id');
            }
            if (!Schema::hasColumn('crm_leads', 'other_source_details')) {
                $table->text('other_source_details')->nullable()->after('lead_source_name');
            }
            if (!Schema::hasColumn('crm_leads', 'is_duplicate_approved')) {
                $table->boolean('is_duplicate_approved')->default(false)->after('status');
            }
        });

        // 2. Create crm_duplicate_lead_requests table
        if (!Schema::hasTable('crm_duplicate_lead_requests')) {
            Schema::create('crm_duplicate_lead_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
                $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
                $table->foreignId('existing_lead_id')->constrained('crm_leads')->onDelete('cascade');
                $table->string('matched_field', 50)->default('phone'); // phone, email, name
                $table->string('matched_value', 255)->nullable();
                $table->json('lead_data'); // stores full proposed lead fields
                $table->text('reason')->nullable();
                $table->string('status', 30)->default('pending'); // pending, approved, rejected
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('admin_notes')->nullable();
                $table->foreignId('created_lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 3. Ensure required standard lead sources exist for all companies
        $standardSources = [
            ['name' => 'Website', 'code' => 'WEB'],
            ['name' => 'Meta Ads', 'code' => 'META'],
            ['name' => 'Referral', 'code' => 'REF'],
            ['name' => 'Cold Call', 'code' => 'COLD'],
            ['name' => 'IndiaMART', 'code' => 'INDMART'],
            ['name' => 'Justdial', 'code' => 'JUSTDIAL'],
            ['name' => 'Other', 'code' => 'OTHER'],
        ];

        $companies = DB::table('companies')->pluck('id')->toArray();
        if (empty($companies)) {
            $companies = [1];
        }

        foreach ($companies as $compId) {
            foreach ($standardSources as $src) {
                $exists = DB::table('crm_lead_sources')
                    ->where('company_id', $compId)
                    ->where('name', $src['name'])
                    ->exists();

                if (!$exists) {
                    DB::table('crm_lead_sources')->insert([
                        'company_id' => $compId,
                        'name' => $src['name'],
                        'code' => $src['code'],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_duplicate_lead_requests');

        Schema::table('crm_leads', function (Blueprint $table) {
            if (Schema::hasColumn('crm_leads', 'lead_source_name')) {
                $table->dropColumn('lead_source_name');
            }
            if (Schema::hasColumn('crm_leads', 'other_source_details')) {
                $table->dropColumn('other_source_details');
            }
            if (Schema::hasColumn('crm_leads', 'is_duplicate_approved')) {
                $table->dropColumn('is_duplicate_approved');
            }
        });
    }
};

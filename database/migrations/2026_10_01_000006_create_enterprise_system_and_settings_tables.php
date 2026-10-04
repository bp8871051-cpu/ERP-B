<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Delete Requests Table (Two-step safe deletion approval workflow)
        Schema::create('delete_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('module'); // 'users', 'customers', 'sales_orders', 'invoices', 'products', 'assets', 'documents', 'tickets', etc.
            $table->unsignedBigInteger('record_id');
            $table->string('record_title');
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled', 'completed'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });

        // 2. System Audit Logs
        Schema::create('system_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('module');
            $table->string('action'); // login, logout, create, update, delete, permission_change, role_change, settings_change, ticket_update, membership_update, integration_change, delete_approval
            $table->string('record_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // 3. User Login Histories
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device')->nullable();
            $table->string('browser')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', ['success', 'failed'])->default('success');
            $table->dateTime('login_at');
            $table->dateTime('logout_at')->nullable();
            $table->timestamps();
        });

        // 4. User Active Sessions
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('session_token')->unique();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device')->nullable();
            $table->string('browser')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('last_activity_at');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        // 5. API Keys Table
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name');
            $table->string('key')->unique();
            $table->string('secret_hash');
            $table->json('scopes')->nullable(); // ['read', 'write', 'tickets', 'memberships', 'crm', 'inventory']
            $table->dateTime('last_used_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_revoked')->default(false);
            $table->timestamps();
        });

        // 6. Webhooks Table
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->string('name');
            $table->string('url');
            $table->string('event'); // ticket.created, membership.renewed, payment.success, delete_request.created, etc.
            $table->string('secret')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('retry_policy')->default('3_retries');
            $table->integer('last_status_code')->nullable();
            $table->dateTime('last_triggered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('api_keys');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('login_histories');
        Schema::dropIfExists('system_audit_logs');
        Schema::dropIfExists('delete_requests');
    }
};

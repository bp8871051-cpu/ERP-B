<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('currency', 10)->default('USD');
            $table->string('currency_symbol', 10)->default('$');
            $table->string('tax_number')->nullable();
            $table->string('logo')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('module');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->unique(['role_id', 'permission_id']);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('company_id')->constrained('departments')->nullOnDelete();
            $table->string('role')->default('Employee')->after('email');
            $table->string('phone')->nullable()->after('role');
            $table->string('avatar')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // create, update, delete, login, export
            $table->string('module'); // HRM, Inventory, CRM, etc.
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['company_id', 'department_id', 'role', 'phone', 'avatar', 'is_active', 'last_login_at']);
        });
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('companies');
    }
};

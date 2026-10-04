<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Membership Plans
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'half-yearly', 'yearly', 'lifetime', 'custom'])->default('monthly');
            $table->integer('trial_period_days')->default(0);
            $table->decimal('setup_fee', 10, 2)->default(0.00);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->integer('max_users')->default(5);
            $table->integer('storage_limit_gb')->default(10);
            $table->json('features')->nullable(); // ['crm', 'inventory', 'pos', 'hrm', 'reports', 'support', 'documents', 'api_access']
            $table->enum('status', ['active', 'inactive', 'draft', 'archived'])->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Membership Addons
        Schema::create('membership_addons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->enum('billing_cycle', ['monthly', 'yearly', 'one-time'])->default('monthly');
            $table->integer('quantity_limit')->nullable();
            $table->string('feature_key')->nullable(); // extra_user, extra_storage, whatsapp_api, etc.
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // 3. Customer Memberships Subscriptions
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('plan_id')->index();
            $table->string('membership_number')->unique();
            $table->enum('status', ['active', 'pending', 'expired', 'cancelled', 'paused', 'trial'])->default('active');
            $table->date('start_date');
            $table->date('expiry_date');
            $table->dateTime('trial_ends_at')->nullable();
            $table->string('billing_cycle')->default('monthly');
            $table->boolean('auto_renew')->default(true);
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->string('payment_method')->default('card');
            $table->text('notes')->nullable();
            $table->dateTime('paused_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Membership Addon Pivot / Items
        Schema::create('membership_addon_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('membership_id')->index();
            $table->unsignedBigInteger('addon_id')->index();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0.00);
            $table->decimal('total_price', 10, 2)->default(0.00);
            $table->timestamps();
        });

        // 5. Membership Transactions
        Schema::create('membership_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('membership_id')->nullable()->index();
            $table->string('transaction_number')->unique();
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->decimal('tax', 10, 2)->default(0.00);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2)->default(0.00);
            $table->enum('payment_method', ['cash', 'card', 'upi', 'bank_transfer', 'online_payment', 'wallet'])->default('card');
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded', 'cancelled'])->default('paid');
            $table->string('payment_reference')->nullable();
            $table->dateTime('transaction_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 6. Membership Renewals Log
        Schema::create('membership_renewals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->default(1)->index();
            $table->unsignedBigInteger('membership_id')->index();
            $table->unsignedBigInteger('previous_plan_id')->nullable();
            $table->unsignedBigInteger('new_plan_id')->nullable();
            $table->date('previous_expiry')->nullable();
            $table->date('new_expiry');
            $table->decimal('renewal_amount', 10, 2)->default(0.00);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('tax', 10, 2)->default(0.00);
            $table->decimal('total_paid', 10, 2)->default(0.00);
            $table->enum('renewal_type', ['renew', 'upgrade', 'downgrade'])->default('renew');
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('paid');
            $table->unsignedBigInteger('renewed_by')->nullable();
            $table->timestamps();
        });

        // 7. Membership Usage Metrics
        Schema::create('membership_usage', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('membership_id')->index();
            $table->string('metric'); // users, storage_gb, api_calls
            $table->integer('used_value')->default(0);
            $table->integer('limit_value')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_usage');
        Schema::dropIfExists('membership_renewals');
        Schema::dropIfExists('membership_transactions');
        Schema::dropIfExists('membership_addon_items');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('membership_addons');
        Schema::dropIfExists('membership_plans');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // CRM
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('position')->nullable();
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('source')->default('Website'); // Website, Referral, Cold Call, Exhibition
            $table->string('status')->default('new'); // new, contacted, qualified, proposal, negotiation, won, lost
            $table->decimal('deal_value', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('stage')->default('prospecting'); // prospecting, qualification, proposal, negotiation, closed_won, closed_lost
            $table->integer('probability')->default(20);
            $table->decimal('expected_revenue', 12, 2)->default(0);
            $table->date('close_date')->nullable();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('subjectable'); // lead, customer, opportunity
            $table->string('type'); // call, email, meeting, note, task
            $table->string('subject');
            $table->text('description')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->string('status')->default('pending'); // pending, completed, cancelled
            $table->timestamps();
        });

        // POS
        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('order_number')->unique();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('payment_method')->default('cash'); // cash, card, digital
            $table->string('status')->default('completed'); // completed, refunded, void
            $table->timestamps();
        });

        Schema::create('pos_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_order_id')->constrained('pos_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->timestamps();
        });

        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_order_id')->constrained('pos_orders')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('payment_type'); // cash, card, mobile
            $table->string('reference_no')->nullable();
            $table->timestamps();
        });

        // Finance
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('account_name');
            $table->string('account_number')->nullable();
            $table->string('type')->default('bank'); // bank, cash, credit_card
            $table->decimal('balance', 14, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('type'); // income, expense, transfer
            $table->decimal('amount', 12, 2);
            $table->string('category');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('paid');
            $table->timestamps();
        });

        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('source');
            $table->decimal('amount', 12, 2);
            $table->date('income_date');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('invoice_number')->unique();
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->string('status')->default('unpaid'); // unpaid, paid, partially_paid, overdue, draft
            $table->timestamps();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('description');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('incomes');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('pos_transactions');
        Schema::dropIfExists('pos_order_items');
        Schema::dropIfExists('pos_orders');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('customers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sales
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_number')->unique();
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('status')->default('pending'); // pending, processing, shipped, delivered, cancelled
            $table->string('payment_status')->default('unpaid'); // unpaid, paid, partial
            $table->timestamps();
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('quote_number')->unique();
            $table->date('date');
            $table->date('expiry_date');
            $table->decimal('total', 12, 2);
            $table->string('status')->default('draft'); // draft, sent, accepted, rejected
            $table->timestamps();
        });

        // Procurement
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('po_number')->unique();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('status')->default('pending'); // draft, pending, approved, ordered, received, cancelled
            $table->string('payment_status')->default('unpaid'); // unpaid, partial, paid
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->string('payment_method')->default('bank_transfer');
            $table->string('reference')->nullable();
            $table->timestamps();
        });

        // Projects
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('budget', 12, 2)->default(0);
            $table->decimal('spent', 12, 2)->default(0);
            $table->string('status')->default('in_progress'); // not_started, in_progress, on_hold, completed, cancelled
            $table->integer('progress')->default(0); // 0 - 100%
            $table->timestamps();
        });

        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role')->default('Member'); // Lead, Developer, Designer, Member
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            $table->string('status')->default('todo'); // todo, in_progress, review, completed
            $table->date('due_date')->nullable();
            $table->timestamps();
        });

        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('title');
            $table->date('due_date')->nullable();
            $table->string('status')->default('pending'); // pending, completed
            $table->timestamps();
        });

        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('hours', 5, 2);
            $table->date('date');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Support
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('ticket_categories')->nullOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->string('priority')->default('medium'); // low, medium, high, critical
            $table->string('status')->default('open'); // open, in_progress, pending, resolved, closed
            $table->timestamps();
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('message');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_categories');
        Schema::dropIfExists('timesheets');
        Schema::dropIfExists('milestones');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
    }
};

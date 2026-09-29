<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add capacity and contact info to warehouses if missing
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'capacity')) {
                $table->integer('capacity')->default(10000)->after('type'); // in units
            }
            if (!Schema::hasColumn('warehouses', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 2. inventory_transactions table
        if (!Schema::hasTable('inventory_transactions')) {
            Schema::create('inventory_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->string('type'); // purchase_in, sales_out, purchase_return, sales_return, stock_adjustment, stock_transfer_in, stock_transfer_out, opening_stock, manual_entry
                $table->integer('quantity'); // positive for in, negative for out
                $table->decimal('unit_cost', 12, 2)->default(0);
                $table->decimal('total_cost', 14, 2)->default(0);
                $table->string('reference_type')->nullable(); // PurchaseOrder, SalesOrder, StockTransfer, etc.
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('reference')->nullable();
                $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'product_id', 'warehouse_id']);
                $table->index(['type', 'created_at']);
            });
        }

        // 3. inventory_alerts table
        if (!Schema::hasTable('inventory_alerts')) {
            Schema::create('inventory_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                $table->string('type'); // low_stock, out_of_stock, capacity_warning, dead_stock, pending_purchase, pending_transfer
                $table->string('severity')->default('warning'); // info, warning, critical
                $table->string('title');
                $table->text('message');
                $table->boolean('is_resolved')->default(false);
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['company_id', 'type', 'is_resolved']);
            });
        }

        // 4. stock_forecasts table
        if (!Schema::hasTable('stock_forecasts')) {
            Schema::create('stock_forecasts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                $table->integer('current_stock')->default(0);
                $table->decimal('average_daily_sales', 8, 2)->default(0);
                $table->decimal('days_remaining', 8, 1)->default(0);
                $table->date('predicted_stockout_date')->nullable();
                $table->integer('recommended_order_quantity')->default(0);
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->decimal('estimated_cost', 12, 2)->default(0);
                $table->decimal('confidence_score', 5, 2)->default(95.00); // percentage
                $table->timestamps();

                $table->index(['company_id', 'product_id', 'days_remaining']);
            });
        }

        // 5. inventory_snapshots table
        if (!Schema::hasTable('inventory_snapshots')) {
            Schema::create('inventory_snapshots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->date('snapshot_date');
                $table->integer('total_products')->default(0);
                $table->integer('total_units')->default(0);
                $table->decimal('total_stock_value', 16, 2)->default(0);
                $table->decimal('total_purchase_value', 16, 2)->default(0);
                $table->decimal('total_sales_value', 16, 2)->default(0);
                $table->integer('low_stock_count')->default(0);
                $table->integer('out_of_stock_count')->default(0);
                $table->decimal('warehouse_utilization', 5, 2)->default(0);
                $table->timestamps();

                $table->unique(['company_id', 'snapshot_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_snapshots');
        Schema::dropIfExists('stock_forecasts');
        Schema::dropIfExists('inventory_alerts');
        Schema::dropIfExists('inventory_transactions');
    }
};

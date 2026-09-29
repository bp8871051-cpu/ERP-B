<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhance categories
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            }
            if (!Schema::hasColumn('categories', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('company_id')->constrained('categories')->nullOnDelete();
            }
            if (!Schema::hasColumn('categories', 'image')) {
                $table->string('image')->nullable()->after('description');
            }
            if (!Schema::hasColumn('categories', 'status')) {
                $table->string('status')->default('active')->after('image');
            }
        });

        // 2. Enhance brands
        Schema::table('brands', function (Blueprint $table) {
            if (!Schema::hasColumn('brands', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            }
            if (!Schema::hasColumn('brands', 'logo')) {
                $table->string('logo')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('brands', 'description')) {
                $table->text('description')->nullable()->after('logo');
            }
            if (!Schema::hasColumn('brands', 'website')) {
                $table->string('website')->nullable()->after('description');
            }
            if (!Schema::hasColumn('brands', 'status')) {
                $table->string('status')->default('active')->after('website');
            }
        });

        // 3. Enhance units
        Schema::table('units', function (Blueprint $table) {
            if (!Schema::hasColumn('units', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            }
            if (!Schema::hasColumn('units', 'short_code')) {
                $table->string('short_code', 10)->nullable()->after('name');
            }
            if (!Schema::hasColumn('units', 'unit_type')) {
                $table->string('unit_type')->default('piece')->after('short_name');
            }
            if (!Schema::hasColumn('units', 'conversion_factor')) {
                $table->decimal('conversion_factor', 10, 4)->default(1.0000)->after('unit_type');
            }
            if (!Schema::hasColumn('units', 'status')) {
                $table->string('status')->default('active')->after('conversion_factor');
            }
        });

        // 4. Enhance suppliers
        Schema::table('suppliers', function (Blueprint $table) {
            if (!Schema::hasColumn('suppliers', 'supplier_code')) {
                $table->string('supplier_code')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('suppliers', 'contact_person')) {
                $table->string('contact_person')->nullable()->after('company_name');
            }
            if (!Schema::hasColumn('suppliers', 'alternate_phone')) {
                $table->string('alternate_phone')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('suppliers', 'website')) {
                $table->string('website')->nullable()->after('alternate_phone');
            }
            if (!Schema::hasColumn('suppliers', 'gst_number')) {
                $table->string('gst_number')->nullable()->after('tax_id');
            }
            if (!Schema::hasColumn('suppliers', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            if (!Schema::hasColumn('suppliers', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (!Schema::hasColumn('suppliers', 'country')) {
                $table->string('country')->default('India')->after('state');
            }
            if (!Schema::hasColumn('suppliers', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('country');
            }
            if (!Schema::hasColumn('suppliers', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('postal_code');
            }
            if (!Schema::hasColumn('suppliers', 'account_number')) {
                $table->string('account_number')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('suppliers', 'deleted_at')) {
                $table->softDeletes()->after('status');
            }
        });

        // 5. Enhance warehouses
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'manager_id')) {
                $table->foreignId('manager_id')->nullable()->after('code')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('warehouses', 'email')) {
                $table->string('email')->nullable()->after('manager_name');
            }
            if (!Schema::hasColumn('warehouses', 'type')) {
                $table->string('type')->default('main')->after('email'); // main, branch, distribution, storage, virtual
            }
            if (!Schema::hasColumn('warehouses', 'address')) {
                $table->text('address')->nullable()->after('type');
            }
            if (!Schema::hasColumn('warehouses', 'city')) {
                $table->string('city')->nullable()->after('address');
            }
            if (!Schema::hasColumn('warehouses', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (!Schema::hasColumn('warehouses', 'country')) {
                $table->string('country')->default('India')->after('state');
            }
            if (!Schema::hasColumn('warehouses', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('country');
            }
        });

        // 6. Enhance products
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (!Schema::hasColumn('products', 'product_code')) {
                $table->string('product_code')->nullable()->after('barcode');
            }
            if (!Schema::hasColumn('products', 'purchase_price')) {
                $table->decimal('purchase_price', 12, 2)->default(0)->after('cost_price');
            }
            if (!Schema::hasColumn('products', 'mrp')) {
                $table->decimal('mrp', 12, 2)->default(0)->after('selling_price');
            }
            if (!Schema::hasColumn('products', 'discount')) {
                $table->decimal('discount', 8, 2)->default(0)->after('mrp');
            }
            if (!Schema::hasColumn('products', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('products', 'tax_type')) {
                $table->string('tax_type')->default('exclusive')->after('tax_rate');
            }
            if (!Schema::hasColumn('products', 'minimum_stock')) {
                $table->integer('minimum_stock')->default(10)->after('alert_quantity');
            }
            if (!Schema::hasColumn('products', 'maximum_stock')) {
                $table->integer('maximum_stock')->default(1000)->after('minimum_stock');
            }
            if (!Schema::hasColumn('products', 'reorder_level')) {
                $table->integer('reorder_level')->default(20)->after('maximum_stock');
            }
            if (!Schema::hasColumn('products', 'default_warehouse_id')) {
                $table->foreignId('default_warehouse_id')->nullable()->after('reorder_level')->constrained('warehouses')->nullOnDelete();
            }
            if (!Schema::hasColumn('products', 'description')) {
                $table->text('description')->nullable()->after('image');
            }
            if (!Schema::hasColumn('products', 'track_inventory')) {
                $table->boolean('track_inventory')->default(true)->after('description');
            }
            if (!Schema::hasColumn('products', 'track_batch')) {
                $table->boolean('track_batch')->default(false)->after('track_inventory');
            }
            if (!Schema::hasColumn('products', 'track_serial')) {
                $table->boolean('track_serial')->default(false)->after('track_batch');
            }
            if (!Schema::hasColumn('products', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('products', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('products', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // 7. Enhance stocks
        Schema::table('stocks', function (Blueprint $table) {
            if (!Schema::hasColumn('stocks', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            }
            if (!Schema::hasColumn('stocks', 'available_quantity')) {
                $table->integer('available_quantity')->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('stocks', 'reserved_quantity')) {
                $table->integer('reserved_quantity')->default(0)->after('available_quantity');
            }
            if (!Schema::hasColumn('stocks', 'damaged_quantity')) {
                $table->integer('damaged_quantity')->default(0)->after('reserved_quantity');
            }
            if (!Schema::hasColumn('stocks', 'average_cost')) {
                $table->decimal('average_cost', 12, 2)->default(0)->after('damaged_quantity');
            }
            if (!Schema::hasColumn('stocks', 'last_movement_at')) {
                $table->timestamp('last_movement_at')->nullable()->after('average_cost');
            }
        });

        // 8. Enhance stock_movements
        Schema::table('stock_movements', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_movements', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
            }
            if (!Schema::hasColumn('stock_movements', 'reference_type')) {
                $table->string('reference_type')->nullable()->after('warehouse_id');
            }
            if (!Schema::hasColumn('stock_movements', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            }
            if (!Schema::hasColumn('stock_movements', 'movement_type')) {
                $table->string('movement_type')->default('manual')->after('type'); // in, out, transfer_in, transfer_out, adjustment, opening, damage, sale, purchase, manual
            }
            if (!Schema::hasColumn('stock_movements', 'quantity_in')) {
                $table->integer('quantity_in')->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('stock_movements', 'quantity_out')) {
                $table->integer('quantity_out')->default(0)->after('quantity_in');
            }
            if (!Schema::hasColumn('stock_movements', 'balance_quantity')) {
                $table->integer('balance_quantity')->default(0)->after('quantity_out');
            }
            if (!Schema::hasColumn('stock_movements', 'unit_cost')) {
                $table->decimal('unit_cost', 12, 2)->default(0)->after('balance_quantity');
            }
            if (!Schema::hasColumn('stock_movements', 'total_cost')) {
                $table->decimal('total_cost', 14, 2)->default(0)->after('unit_cost');
            }
        });

        // 9. Create stock_adjustments table
        if (!Schema::hasTable('stock_adjustments')) {
            Schema::create('stock_adjustments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->string('reference_number')->unique();
                $table->string('adjustment_type')->default('increase'); // increase, decrease, both
                $table->string('reason')->nullable();
                $table->string('status')->default('draft'); // draft, pending, approved, rejected, applied
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->index(['warehouse_id', 'status']);
            });
        }

        // 10. Create stock_adjustment_items table
        if (!Schema::hasTable('stock_adjustment_items')) {
            Schema::create('stock_adjustment_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('system_quantity')->default(0);
                $table->integer('actual_quantity')->default(0);
                $table->integer('difference')->default(0); // actual - system
                $table->decimal('unit_cost', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['adjustment_id', 'product_id']);
            });
        }

        // 11. Create stock_transfers table
        if (!Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->string('transfer_number')->unique();
                $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->string('status')->default('draft'); // draft, pending, approved, in_transit, received, cancelled
                $table->string('reason')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('received_at')->nullable();
                $table->timestamps();

                $table->index(['from_warehouse_id', 'to_warehouse_id', 'status']);
            });
        }

        // 12. Create stock_transfer_items table
        if (!Schema::hasTable('stock_transfer_items')) {
            Schema::create('stock_transfer_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('quantity');
                $table->decimal('unit_cost', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['transfer_id', 'product_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
    }
};

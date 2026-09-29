<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Enhance Customers table
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'customer_code')) {
                $table->string('customer_code')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('customers', 'alternate_phone')) {
                $table->string('alternate_phone')->nullable()->after('phone');
            }
            if (!Schema::hasColumn('customers', 'website')) {
                $table->string('website')->nullable()->after('alternate_phone');
            }
            if (!Schema::hasColumn('customers', 'tax_number')) {
                $table->string('tax_number')->nullable()->after('website');
            }
            if (!Schema::hasColumn('customers', 'gst_number')) {
                $table->string('gst_number')->nullable()->after('tax_number');
            }
            if (!Schema::hasColumn('customers', 'city')) {
                $table->string('city')->nullable()->after('shipping_address');
            }
            if (!Schema::hasColumn('customers', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (!Schema::hasColumn('customers', 'country')) {
                $table->string('country')->nullable()->default('US')->after('state');
            }
            if (!Schema::hasColumn('customers', 'postal_code')) {
                $table->string('postal_code')->nullable()->after('country');
            }
            if (!Schema::hasColumn('customers', 'payment_terms')) {
                $table->string('payment_terms')->nullable()->default('Net 30')->after('credit_limit');
            }
            if (!Schema::hasColumn('customers', 'opening_balance')) {
                $table->decimal('opening_balance', 14, 2)->default(0)->after('balance');
            }
            if (!Schema::hasColumn('customers', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 2. Enhance Sales Orders table
        Schema::table('sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_orders', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->after('customer_id')->constrained('warehouses')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales_orders', 'expected_delivery_date')) {
                $table->date('expected_delivery_date')->nullable()->after('order_date');
            }
            if (!Schema::hasColumn('sales_orders', 'reference_number')) {
                $table->string('reference_number')->nullable()->after('order_number');
            }
            if (!Schema::hasColumn('sales_orders', 'shipping')) {
                $table->decimal('shipping', 12, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('sales_orders', 'grand_total')) {
                $table->decimal('grand_total', 14, 2)->default(0)->after('total');
            }
            if (!Schema::hasColumn('sales_orders', 'paid_amount')) {
                $table->decimal('paid_amount', 14, 2)->default(0)->after('grand_total');
            }
            if (!Schema::hasColumn('sales_orders', 'due_amount')) {
                $table->decimal('due_amount', 14, 2)->default(0)->after('paid_amount');
            }
            if (!Schema::hasColumn('sales_orders', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('sales_orders', 'terms')) {
                $table->text('terms')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('sales_orders', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('terms')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales_orders', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 3. Enhance Sales Order Items table
        Schema::table('sales_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_order_items', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('product_id')->constrained('units')->nullOnDelete();
            }
            if (!Schema::hasColumn('sales_order_items', 'description')) {
                $table->text('description')->nullable()->after('unit_id');
            }
            if (!Schema::hasColumn('sales_order_items', 'discount')) {
                $table->decimal('discount', 12, 2)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn('sales_order_items', 'tax')) {
                $table->decimal('tax', 12, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('sales_order_items', 'subtotal')) {
                $table->decimal('subtotal', 14, 2)->default(0)->after('tax');
            }
        });

        // 4. Enhance Invoices table
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'sales_order_id')) {
                $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
            }
            if (!Schema::hasColumn('invoices', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            }
            if (!Schema::hasColumn('invoices', 'invoice_date')) {
                $table->date('invoice_date')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'shipping')) {
                $table->decimal('shipping', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('invoices', 'round_off')) {
                $table->decimal('round_off', 8, 2)->default(0);
            }
            if (!Schema::hasColumn('invoices', 'grand_total')) {
                $table->decimal('grand_total', 14, 2)->default(0);
            }
            if (!Schema::hasColumn('invoices', 'paid_amount')) {
                $table->decimal('paid_amount', 14, 2)->default(0);
            }
            if (!Schema::hasColumn('invoices', 'due_amount')) {
                $table->decimal('due_amount', 14, 2)->default(0);
            }
            if (!Schema::hasColumn('invoices', 'payment_status')) {
                $table->string('payment_status')->default('unpaid');
            }
            if (!Schema::hasColumn('invoices', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'terms')) {
                $table->text('terms')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('invoices', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 5. Enhance Invoice Items table
        Schema::table('invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_items', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('product_id')->constrained('units')->nullOnDelete();
            }
            if (!Schema::hasColumn('invoice_items', 'discount')) {
                $table->decimal('discount', 12, 2)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn('invoice_items', 'tax')) {
                $table->decimal('tax', 12, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('invoice_items', 'subtotal')) {
                $table->decimal('subtotal', 14, 2)->default(0)->after('tax');
            }
        });

        // 6. Invoice Templates table
        if (!Schema::hasTable('invoice_templates')) {
            Schema::create('invoice_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('name');
                $table->string('logo_url')->nullable();
                $table->text('header_text')->nullable();
                $table->text('footer_text')->nullable();
                $table->text('terms')->nullable();
                $table->text('payment_instructions')->nullable();
                $table->string('tax_display')->default('summary'); // summary, itemized, none
                $table->string('color_theme')->default('#0F8B7A');
                $table->boolean('is_default')->default(false);
                $table->timestamps();
            });
        }

        // 7. Recurring Invoices table
        if (!Schema::hasTable('recurring_invoices')) {
            Schema::create('recurring_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('template_id')->nullable()->constrained('invoice_templates')->nullOnDelete();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->string('frequency')->default('monthly'); // weekly, monthly, quarterly, yearly
                $table->decimal('amount', 14, 2);
                $table->date('next_invoice_date');
                $table->string('payment_terms')->default('Net 30');
                $table->string('status')->default('active'); // active, paused, completed, cancelled
                $table->text('items_json')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 8. Sales Quotes table
        if (!Schema::hasTable('sales_quotes')) {
            Schema::create('sales_quotes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('quote_number')->unique();
                $table->date('quote_date');
                $table->date('valid_until');
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('shipping', 12, 2)->default(0);
                $table->decimal('grand_total', 14, 2)->default(0);
                $table->string('status')->default('draft'); // draft, sent, accepted, rejected, expired, converted
                $table->foreignId('converted_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->text('terms')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('sales_quote_items')) {
            Schema::create('sales_quote_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_quote_id')->constrained('sales_quotes')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
                $table->text('description')->nullable();
                $table->integer('quantity');
                $table->decimal('unit_price', 12, 2);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('subtotal', 14, 2);
                $table->timestamps();
            });
        }

        // 9. Credit Notes table
        if (!Schema::hasTable('credit_notes')) {
            Schema::create('credit_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->string('credit_note_number')->unique();
                $table->date('date');
                $table->text('reason')->nullable();
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('total', 14, 2);
                $table->string('status')->default('draft'); // draft, issued, applied, cancelled
                $table->boolean('restock_inventory')->default(false);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('credit_note_items')) {
            Schema::create('credit_note_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('credit_note_id')->constrained('credit_notes')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('quantity');
                $table->decimal('unit_price', 12, 2);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('subtotal', 14, 2);
                $table->timestamps();
            });
        }

        // 10. Cash Sales table
        if (!Schema::hasTable('cash_sales')) {
            Schema::create('cash_sales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->string('sale_number')->unique();
                $table->date('sale_date');
                $table->decimal('subtotal', 14, 2);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('grand_total', 14, 2);
                $table->string('payment_method')->default('cash'); // cash, card, bank_transfer, upi, other
                $table->decimal('paid_amount', 14, 2);
                $table->decimal('change_amount', 12, 2)->default(0);
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->string('status')->default('completed');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cash_sale_items')) {
            Schema::create('cash_sale_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cash_sale_id')->constrained('cash_sales')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('quantity');
                $table->decimal('unit_price', 12, 2);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('subtotal', 14, 2);
                $table->timestamps();
            });
        }

        // 11. Refunds table
        if (!Schema::hasTable('refunds')) {
            Schema::create('refunds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->string('refund_number')->unique();
                $table->date('refund_date');
                $table->text('reason')->nullable();
                $table->decimal('refund_amount', 14, 2);
                $table->string('refund_method')->default('cash'); // cash, card, bank_transfer, upi, credit
                $table->boolean('restock_inventory')->default(false);
                $table->string('status')->default('requested'); // requested, approved, processed, rejected
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 12. Delivery Notes table
        if (!Schema::hasTable('delivery_notes')) {
            Schema::create('delivery_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('sales_order_id')->nullable()->constrained('sales_orders')->nullOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->string('delivery_number')->unique();
                $table->date('delivery_date');
                $table->text('delivery_address');
                $table->string('driver_name')->nullable();
                $table->string('vehicle_number')->nullable();
                $table->string('status')->default('pending'); // pending, packed, dispatched, delivered, partially_delivered, cancelled
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('delivery_note_items')) {
            Schema::create('delivery_note_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('delivery_note_id')->constrained('delivery_notes')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('ordered_quantity')->default(0);
                $table->integer('delivered_quantity')->default(0);
                $table->integer('remaining_quantity')->default(0);
                $table->timestamps();
            });
        }

        // 13. Customer Payments table
        if (!Schema::hasTable('customer_payments')) {
            Schema::create('customer_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->string('payment_number')->unique();
                $table->decimal('amount', 14, 2);
                $table->date('payment_date');
                $table->string('payment_method')->default('bank_transfer'); // cash, card, bank_transfer, upi, cheque
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // ==========================================
        // PURCHASE TABLES
        // ==========================================

        // 14. Vendors table
        if (!Schema::hasTable('vendors')) {
            Schema::create('vendors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('vendor_code')->nullable();
                $table->string('name');
                $table->string('company_name')->nullable();
                $table->string('contact_person')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('alternate_phone')->nullable();
                $table->string('website')->nullable();
                $table->string('tax_number')->nullable();
                $table->string('gst_number')->nullable();
                $table->text('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('country')->nullable()->default('US');
                $table->string('postal_code')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('account_holder')->nullable();
                $table->string('account_number')->nullable();
                $table->string('ifsc')->nullable();
                $table->decimal('credit_limit', 14, 2)->default(0);
                $table->string('payment_terms')->nullable()->default('Net 30');
                $table->decimal('opening_balance', 14, 2)->default(0);
                $table->decimal('balance', 14, 2)->default(0);
                $table->string('status')->default('active'); // active, inactive
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 15. Enhance Purchase Orders table
        Schema::table('purchase_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_orders', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()->after('company_id')->constrained('vendors')->nullOnDelete();
            }
            if (!Schema::hasColumn('purchase_orders', 'warehouse_id')) {
                $table->foreignId('warehouse_id')->nullable()->after('vendor_id')->constrained('warehouses')->nullOnDelete();
            }
            if (!Schema::hasColumn('purchase_orders', 'po_date')) {
                $table->date('po_date')->nullable()->after('po_number');
            }
            if (!Schema::hasColumn('purchase_orders', 'discount')) {
                $table->decimal('discount', 12, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('purchase_orders', 'shipping')) {
                $table->decimal('shipping', 12, 2)->default(0)->after('tax');
            }
            if (!Schema::hasColumn('purchase_orders', 'grand_total')) {
                $table->decimal('grand_total', 14, 2)->default(0)->after('total');
            }
            if (!Schema::hasColumn('purchase_orders', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (!Schema::hasColumn('purchase_orders', 'terms')) {
                $table->text('terms')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('purchase_orders', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 16. Enhance Purchase Order Items
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_order_items', 'unit_id')) {
                $table->foreignId('unit_id')->nullable()->after('product_id')->constrained('units')->nullOnDelete();
            }
            if (!Schema::hasColumn('purchase_order_items', 'unit_cost')) {
                $table->decimal('unit_cost', 12, 2)->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('purchase_order_items', 'discount')) {
                $table->decimal('discount', 12, 2)->default(0)->after('unit_cost');
            }
            if (!Schema::hasColumn('purchase_order_items', 'tax')) {
                $table->decimal('tax', 12, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('purchase_order_items', 'subtotal')) {
                $table->decimal('subtotal', 14, 2)->default(0)->after('tax');
            }
        });

        // 17. Purchases (Actual Goods Received)
        if (!Schema::hasTable('purchases')) {
            Schema::create('purchases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->string('purchase_number')->unique();
                $table->string('vendor_invoice_number')->nullable();
                $table->date('invoice_date');
                $table->decimal('subtotal', 14, 2);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('shipping', 12, 2)->default(0);
                $table->decimal('grand_total', 14, 2);
                $table->decimal('paid_amount', 14, 2)->default(0);
                $table->decimal('due_amount', 14, 2)->default(0);
                $table->string('payment_status')->default('unpaid'); // unpaid, partially_paid, paid
                $table->string('status')->default('received'); // draft, received, cancelled
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('purchase_items')) {
            Schema::create('purchase_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
                $table->integer('quantity');
                $table->decimal('unit_cost', 12, 2);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('subtotal', 14, 2);
                $table->timestamps();
            });
        }

        // 18. Purchase Returns
        if (!Schema::hasTable('purchase_returns')) {
            Schema::create('purchase_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
                $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
                $table->string('return_number')->unique();
                $table->date('return_date');
                $table->text('reason')->nullable();
                $table->decimal('subtotal', 14, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('grand_total', 14, 2);
                $table->string('status')->default('draft'); // draft, requested, approved, returned, completed, cancelled
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('purchase_return_items')) {
            Schema::create('purchase_return_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('quantity');
                $table->decimal('unit_cost', 12, 2);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('total', 14, 2);
                $table->timestamps();
            });
        }

        // 19. Vendor Payments
        if (!Schema::hasTable('vendor_payments')) {
            Schema::create('vendor_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
                $table->string('payment_number')->unique();
                $table->decimal('amount', 14, 2);
                $table->date('payment_date');
                $table->string('payment_method')->default('bank_transfer');
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_payments');
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('customer_payments');
        Schema::dropIfExists('delivery_note_items');
        Schema::dropIfExists('delivery_notes');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('cash_sale_items');
        Schema::dropIfExists('cash_sales');
        Schema::dropIfExists('credit_note_items');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('sales_quote_items');
        Schema::dropIfExists('sales_quotes');
        Schema::dropIfExists('recurring_invoices');
        Schema::dropIfExists('invoice_templates');
    }
};

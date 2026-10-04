<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. POS Registers (Physical or virtual cash registers)
        if (!Schema::hasTable('pos_registers')) {
            Schema::create('pos_registers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                $table->string('name');
                $table->string('register_code')->unique();
                $table->string('status')->default('closed'); // open, closed
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 2. POS Register Sessions (Opening and closing cash float history)
        if (!Schema::hasTable('pos_register_sessions')) {
            Schema::create('pos_register_sessions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('pos_register_id')->constrained('pos_registers')->cascadeOnDelete();
                $table->foreignId('cashier_id')->constrained('users')->cascadeOnDelete();
                $table->dateTime('opened_at');
                $table->dateTime('closed_at')->nullable();
                $table->decimal('opening_cash', 12, 2)->default(0);
                $table->decimal('closing_cash', 12, 2)->nullable();
                $table->decimal('expected_cash', 12, 2)->nullable();
                $table->decimal('difference', 12, 2)->nullable();
                $table->decimal('total_sales', 12, 2)->default(0);
                $table->integer('total_transactions')->default(0);
                $table->string('status')->default('open'); // open, closed
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // Modify or ensure pos_orders has all enterprise fields
        if (Schema::hasTable('pos_orders')) {
            Schema::table('pos_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('pos_orders', 'warehouse_id')) {
                    $table->foreignId('warehouse_id')->nullable()->after('company_id')->constrained('warehouses')->nullOnDelete();
                }
                if (!Schema::hasColumn('pos_orders', 'register_session_id')) {
                    $table->foreignId('register_session_id')->nullable()->after('warehouse_id')->constrained('pos_register_sessions')->nullOnDelete();
                }
                if (!Schema::hasColumn('pos_orders', 'invoice_id')) {
                    $table->foreignId('invoice_id')->nullable()->after('customer_id')->constrained('invoices')->nullOnDelete();
                }
                if (!Schema::hasColumn('pos_orders', 'round_off')) {
                    $table->decimal('round_off', 8, 2)->default(0)->after('discount_amount');
                }
                if (!Schema::hasColumn('pos_orders', 'paid_amount')) {
                    $table->decimal('paid_amount', 12, 2)->default(0)->after('total_amount');
                }
                if (!Schema::hasColumn('pos_orders', 'change_amount')) {
                    $table->decimal('change_amount', 12, 2)->default(0)->after('paid_amount');
                }
                if (!Schema::hasColumn('pos_orders', 'payment_status')) {
                    $table->string('payment_status')->default('paid')->after('payment_method'); // pending, paid, partially_paid, refunded
                }
                if (!Schema::hasColumn('pos_orders', 'order_status')) {
                    $table->string('order_status')->default('completed')->after('payment_status'); // draft, completed, cancelled, refunded, partially_refunded
                }
                if (!Schema::hasColumn('pos_orders', 'notes')) {
                    $table->text('notes')->nullable()->after('order_status');
                }
            });
        }

        // Modify or ensure pos_order_items has extra detail
        if (Schema::hasTable('pos_order_items')) {
            Schema::table('pos_order_items', function (Blueprint $table) {
                if (!Schema::hasColumn('pos_order_items', 'product_name')) {
                    $table->string('product_name')->nullable()->after('product_id');
                }
                if (!Schema::hasColumn('pos_order_items', 'sku')) {
                    $table->string('sku')->nullable()->after('product_name');
                }
                if (!Schema::hasColumn('pos_order_items', 'discount_amount')) {
                    $table->decimal('discount_amount', 10, 2)->default(0)->after('unit_price');
                }
                if (!Schema::hasColumn('pos_order_items', 'tax_amount')) {
                    $table->decimal('tax_amount', 10, 2)->default(0)->after('discount_amount');
                }
                if (!Schema::hasColumn('pos_order_items', 'subtotal')) {
                    $table->decimal('subtotal', 12, 2)->default(0)->after('tax_amount');
                }
            });
        }

        // 3. POS Payments (Detailed record of multiple payment tenders per order)
        if (!Schema::hasTable('pos_payments')) {
            Schema::create('pos_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('pos_order_id')->constrained('pos_orders')->cascadeOnDelete();
                $table->string('payment_method'); // cash, card, upi, bank_transfer, wallet, credit
                $table->decimal('amount', 12, 2);
                $table->string('currency', 10)->default('INR');
                $table->string('reference_no')->nullable();
                $table->string('card_last4', 4)->nullable();
                $table->string('card_type')->nullable(); // visa, mastercard, rupay, etc.
                $table->string('upi_id')->nullable();
                $table->string('status')->default('completed'); // pending, completed, failed, refunded
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 4. POS Refunds
        if (!Schema::hasTable('pos_refunds')) {
            Schema::create('pos_refunds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('pos_order_id')->constrained('pos_orders')->cascadeOnDelete();
                $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('refund_number')->unique();
                $table->string('refund_type')->default('full'); // full, partial, product
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('amount', 12, 2);
                $table->string('payment_method')->default('cash'); // cash, card, upi, credit
                $table->string('reason');
                $table->string('status')->default('completed'); // completed, cancelled
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 5. POS Refund Items (Product-level refund items)
        if (!Schema::hasTable('pos_refund_items')) {
            Schema::create('pos_refund_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pos_refund_id')->constrained('pos_refunds')->cascadeOnDelete();
                $table->foreignId('pos_order_item_id')->nullable()->constrained('pos_order_items')->nullOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('quantity');
                $table->decimal('unit_price', 12, 2);
                $table->decimal('refund_amount', 12, 2);
                $table->boolean('restock_inventory')->default(true);
                $table->timestamps();
            });
        }

        // 6. POS Receipts (Receipt archive & print logs)
        if (!Schema::hasTable('pos_receipts')) {
            Schema::create('pos_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('pos_order_id')->constrained('pos_orders')->cascadeOnDelete();
                $table->string('receipt_number')->unique();
                $table->json('receipt_payload')->nullable();
                $table->integer('print_count')->default(1);
                $table->boolean('sent_email')->default(false);
                $table->boolean('sent_whatsapp')->default(false);
                $table->string('customer_email')->nullable();
                $table->string('customer_phone')->nullable();
                $table->timestamps();
            });
        }

        // 7. POS Print Settings
        if (!Schema::hasTable('pos_print_settings')) {
            Schema::create('pos_print_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('receipt_width')->default('80mm'); // 58mm, 80mm
                $table->boolean('show_logo')->default(true);
                $table->boolean('show_company_details')->default(true);
                $table->boolean('show_gst')->default(true);
                $table->boolean('show_sku')->default(true);
                $table->boolean('show_barcode')->default(true);
                $table->boolean('show_qr')->default(true);
                $table->string('footer_text')->nullable()->default('Goods once sold will only be exchanged as per policy.');
                $table->string('thank_you_message')->nullable()->default('Thank you for shopping with us! Have a great day.');
                $table->boolean('auto_print')->default(false);
                $table->integer('print_copies')->default(1);
                $table->timestamps();
            });
        }

        // 8. POS Payment Transactions (Audit & gateway transaction log)
        if (!Schema::hasTable('pos_payment_transactions')) {
            Schema::create('pos_payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('pos_order_id')->constrained('pos_orders')->cascadeOnDelete();
                $table->string('gateway')->default('manual'); // manual, razorpay, stripe, pos_terminal
                $table->string('transaction_id')->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('status')->default('success'); // pending, success, failed
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_payment_transactions');
        Schema::dropIfExists('pos_print_settings');
        Schema::dropIfExists('pos_receipts');
        Schema::dropIfExists('pos_refund_items');
        Schema::dropIfExists('pos_refunds');
        Schema::dropIfExists('pos_payments');
        Schema::dropIfExists('pos_register_sessions');
        Schema::dropIfExists('pos_registers');
    }
};

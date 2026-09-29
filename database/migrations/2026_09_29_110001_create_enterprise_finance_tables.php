<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Expense Categories Table (Hierarchical)
        if (!Schema::hasTable('expense_categories')) {
            Schema::create('expense_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('parent_id')->nullable()->constrained('expense_categories')->nullOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->text('description')->nullable();
                $table->decimal('budget', 14, 2)->default(0);
                $table->string('status')->default('active'); // active, inactive
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Enhance Accounts / Cash Accounts Table
        Schema::table('accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('accounts', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('account_name');
            }
            if (!Schema::hasColumn('accounts', 'opening_balance')) {
                $table->decimal('opening_balance', 14, 2)->default(0)->after('balance');
            }
            if (!Schema::hasColumn('accounts', 'account_type')) {
                $table->string('account_type')->default('bank')->after('type'); // cash, bank, upi, credit_card, other
            }
            if (!Schema::hasColumn('accounts', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('status');
            }
            if (!Schema::hasColumn('accounts', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 3. Enhance Expenses Table
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'expense_number')) {
                $table->string('expense_number')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('expenses', 'expense_category_id')) {
                $table->foreignId('expense_category_id')->nullable()->after('account_id')->constrained('expense_categories')->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'vendor_id')) {
                $table->foreignId('vendor_id')->nullable()->after('expense_category_id')->constrained('vendors')->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'tax')) {
                $table->decimal('tax', 12, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('expenses', 'discount')) {
                $table->decimal('discount', 12, 2)->default(0)->after('tax');
            }
            if (!Schema::hasColumn('expenses', 'total')) {
                $table->decimal('total', 14, 2)->default(0)->after('discount');
            }
            if (!Schema::hasColumn('expenses', 'payment_method')) {
                $table->string('payment_method')->default('cash')->after('total'); // cash, bank_transfer, card, upi, cheque, other
            }
            if (!Schema::hasColumn('expenses', 'attachment')) {
                $table->string('attachment')->nullable()->after('description');
            }
            if (!Schema::hasColumn('expenses', 'notes')) {
                $table->text('notes')->nullable()->after('attachment');
            }
            if (!Schema::hasColumn('expenses', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'approved_at')) {
                $table->timestamp('approved_at')->nullable();
            }
            if (!Schema::hasColumn('expenses', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
            if (!Schema::hasColumn('expenses', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 4. Central Payments Table
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('payment_number')->unique();
                $table->date('payment_date');
                $table->string('payment_type'); // customer_payment, vendor_payment, expense_payment, refund, other_income, other_payment
                $table->string('party_type')->nullable(); // customer, vendor, employee, other
                $table->unsignedBigInteger('party_id')->nullable();
                $table->string('party_name')->nullable();
                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
                $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
                $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
                $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->string('payment_method')->default('bank_transfer'); // cash, bank_transfer, card, upi, cheque, other
                $table->string('reference')->nullable();
                $table->decimal('amount', 14, 2);
                $table->text('notes')->nullable();
                $table->string('status')->default('completed'); // pending, completed, failed, cancelled
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['company_id', 'payment_type']);
                $table->index(['party_type', 'party_id']);
            });
        }

        // 5. Cashflow Transactions Table
        if (!Schema::hasTable('cashflow_transactions')) {
            Schema::create('cashflow_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
                $table->date('date');
                $table->string('reference')->nullable();
                $table->string('type'); // inflow, outflow
                $table->string('category')->nullable();
                $table->decimal('amount', 14, 2);
                $table->decimal('balance_after', 14, 2)->default(0);
                $table->text('description')->nullable();
                $table->nullableMorphs('sourceable');
                $table->timestamps();

                $table->index(['company_id', 'date']);
            });
        }

        // 6. Budgets Table
        if (!Schema::hasTable('budgets')) {
            Schema::create('budgets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('name');
                $table->string('financial_year'); // e.g. FY 2026-27
                $table->date('start_date');
                $table->date('end_date');
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
                $table->decimal('budget_amount', 14, 2);
                $table->decimal('actual_spent', 14, 2)->default(0);
                $table->text('notes')->nullable();
                $table->string('status')->default('approved'); // draft, submitted, approved, closed
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 7. Budget Items Table
        if (!Schema::hasTable('budget_items')) {
            Schema::create('budget_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
                $table->string('item_name');
                $table->decimal('planned_amount', 14, 2);
                $table->decimal('actual_amount', 14, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // 8. Taxes Table
        if (!Schema::hasTable('taxes')) {
            Schema::create('taxes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('name'); // GST 18%, VAT 5%, CGST 9%, SGST 9%, IGST 18%
                $table->string('code')->unique();
                $table->string('type')->default('percentage'); // percentage, fixed, compound
                $table->decimal('rate', 8, 4); // 18.0000 %
                $table->boolean('is_compound')->default(false);
                $table->text('description')->nullable();
                $table->string('status')->default('active'); // active, inactive
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 9. Financial Ledger Transactions Table
        if (!Schema::hasTable('financial_transactions')) {
            Schema::create('financial_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                $table->string('transaction_number')->unique();
                $table->date('transaction_date');
                $table->string('transaction_type'); // sales_invoice, customer_payment, purchase, vendor_payment, expense, payroll, refund
                $table->string('reference_type')->nullable(); // Invoice, Purchase, Expense, Payroll, CustomerPayment, etc.
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->string('account_type')->default('bank'); // bank, cash, receivable, payable, revenue, expense, equity
                $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->text('description')->nullable();
                $table->decimal('debit', 14, 2)->default(0);
                $table->decimal('credit', 14, 2)->default(0);
                $table->string('currency', 10)->default('USD');
                $table->string('status')->default('posted'); // draft, posted, reconciled, void
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['company_id', 'transaction_date']);
                $table->index(['reference_type', 'reference_id']);
            });
        }

        // 10. Expense Attachments
        if (!Schema::hasTable('expense_attachments')) {
            Schema::create('expense_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expense_id')->constrained('expenses')->cascadeOnDelete();
                $table->string('file_name');
                $table->string('file_path');
                $table->string('file_type')->nullable();
                $table->integer('file_size')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_attachments');
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('budget_items');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('cashflow_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('expense_categories');
    }
};

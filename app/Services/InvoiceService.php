<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Transaction;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function getList(array $filters = [], ?int $companyId = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Invoice::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['customer', 'salesOrder', 'warehouse', 'items.product']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        return $query->latest('invoice_date')->paginate($perPage);
    }

    public function getDetails(int $id, ?int $companyId = null): Invoice
    {
        $query = Invoice::query()
            ->with([
                'company',
                'customer',
                'salesOrder',
                'warehouse',
                'items.product',
                'items.unit',
                'payments',
                'creditNotes',
                'refunds',
            ]);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->findOrFail($id);
    }

    public function create(array $data, array $items, ?int $userId = null): Invoice
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            if (empty($data['invoice_number'])) {
                $count = Invoice::where('company_id', $data['company_id'] ?? 1)->count() + 1;
                $data['invoice_number'] = 'INV-' . date('Y') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
            }

            $data['issue_date'] = $data['issue_date'] ?? $data['invoice_date'] ?? now()->toDateString();
            $data['invoice_date'] = $data['invoice_date'] ?? $data['issue_date'] ?? now()->toDateString();

            $subtotal = 0;
            $tax = 0;
            $discount = 0;

            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $price = (float) ($item['unit_price'] ?? 0);
                $disc = (float) ($item['discount'] ?? 0);
                $tx = (float) ($item['tax'] ?? 0);

                $subtotal += ($qty * $price);
                $discount += $disc;
                $tax += $tx;
            }

            $shipping = (float) ($data['shipping'] ?? 0);
            $roundOff = (float) ($data['round_off'] ?? 0);
            $grandTotal = $subtotal - $discount + $tax + $shipping + $roundOff;

            $data['subtotal'] = $subtotal;
            $data['discount'] = $discount;
            $data['tax'] = $tax;
            $data['shipping'] = $shipping;
            $data['round_off'] = $roundOff;
            $data['total'] = $grandTotal;
            $data['grand_total'] = $grandTotal;
            $data['amount_paid'] = 0;
            $data['paid_amount'] = 0;
            $data['due_amount'] = $grandTotal;
            $data['payment_status'] = 'unpaid';
            $data['status'] = $data['status'] ?? 'sent';
            $data['created_by'] = $userId;

            $invoice = Invoice::create($data);

            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $price = (float) ($item['unit_price'] ?? 0);
                $disc = (float) ($item['discount'] ?? 0);
                $tx = (float) ($item['tax'] ?? 0);
                $lineSubtotal = ($qty * $price) - $disc + $tx;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item['product_id'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'description' => $item['description'] ?? ($item['name'] ?? 'Product item'),
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'discount' => $disc,
                    'tax' => $tx,
                    'subtotal' => $lineSubtotal,
                    'total' => $lineSubtotal,
                ]);
            }

            // Increase customer accounts receivable
            $customer = Customer::find($invoice->customer_id);
            if ($customer) {
                $customer->increment('balance', $grandTotal);
            }

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $invoice->company_id,
                'action' => 'invoice.created',
                'module' => 'sales',
                'record_id' => $invoice->id,
                'new_values' => ['invoice_number' => $invoice->invoice_number, 'amount' => $grandTotal],
            ]);

            return $invoice->load(['customer', 'items.product']);
        });
    }

    public function recordPayment(int $invoiceId, array $paymentData, ?int $userId = null): CustomerPayment
    {
        return DB::transaction(function () use ($invoiceId, $paymentData, $userId) {
            $invoice = Invoice::with('customer')->findOrFail($invoiceId);
            $amount = (float) ($paymentData['amount'] ?? 0);

            if ($amount <= 0) {
                throw new Exception("Payment amount must be greater than zero.");
            }

            if ($amount > (float) $invoice->due_amount) {
                throw new Exception("Payment amount exceeds outstanding invoice balance of \${$invoice->due_amount}.");
            }

            $count = CustomerPayment::where('company_id', $invoice->company_id)->count() + 1;
            $paymentNumber = 'PAY-' . date('Y') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);

            $payment = CustomerPayment::create([
                'company_id' => $invoice->company_id,
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'payment_number' => $paymentNumber,
                'amount' => $amount,
                'payment_date' => $paymentData['payment_date'] ?? Carbon::now()->toDateString(),
                'payment_method' => $paymentData['payment_method'] ?? 'bank_transfer',
                'reference' => $paymentData['reference'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
                'created_by' => $userId,
            ]);

            // Update invoice paid and due amounts
            $newPaid = (float) $invoice->paid_amount + $amount;
            $newDue = max(0, (float) $invoice->grand_total - $newPaid);
            $status = $newDue <= 0.01 ? 'paid' : 'partially_paid';

            $invoice->amount_paid = $newPaid;
            $invoice->paid_amount = $newPaid;
            $invoice->due_amount = $newDue;
            $invoice->payment_status = $status;
            if ($status === 'paid') {
                $invoice->status = 'paid';
            }
            $invoice->save();

            // Update Customer receivable balance
            if ($invoice->customer) {
                $invoice->customer->decrement('balance', $amount);
            }

            // Create ledger entry in transactions
            Transaction::create([
                'account_id' => 1, // Primary operations bank account
                'type' => 'income',
                'amount' => $amount,
                'category' => 'Customer Invoice Payment',
                'reference' => $paymentNumber,
                'description' => "Payment received for Invoice #{$invoice->invoice_number}",
                'transaction_date' => $payment->payment_date,
            ]);

            AuditLog::create([
                'user_id' => $userId,
                'company_id' => $invoice->company_id,
                'action' => 'invoice.payment_recorded',
                'module' => 'sales',
                'record_id' => $invoice->id,
                'new_values' => ['payment_number' => $paymentNumber, 'amount' => $amount, 'status' => $status],
            ]);

            return $payment;
        });
    }

    public function getPdfData(int $id): array
    {
        $invoice = Invoice::with(['company', 'customer', 'items.product', 'items.unit', 'payments'])->findOrFail($id);
        $company = $invoice->company ?? Company::first();

        return [
            'invoice' => $invoice,
            'company' => [
                'name' => $company->name ?? 'Falcon ERP Enterprise',
                'address' => $company->address ?? '742 Innovation Blvd, Suite 400, Austin, TX 78701',
                'phone' => $company->phone ?? '+1 (555) 019-2834',
                'email' => $company->email ?? 'billing@falconerp.com',
                'tax_number' => $company->tax_number ?? 'US-982348123',
                'gst_number' => 'GST-884912903',
                'currency_symbol' => '$',
            ],
            'customer' => $invoice->customer,
            'items' => $invoice->items,
            'totals' => [
                'subtotal' => $invoice->subtotal,
                'discount' => $invoice->discount,
                'tax' => $invoice->tax,
                'shipping' => $invoice->shipping,
                'grand_total' => $invoice->grand_total,
                'paid_amount' => $invoice->paid_amount,
                'due_amount' => $invoice->due_amount,
            ],
            'terms' => $invoice->terms ?? 'Payment due within 30 days of issue. Overdue accounts are subject to a 1.5% monthly finance charge.',
            'notes' => $invoice->notes,
        ];
    }
}

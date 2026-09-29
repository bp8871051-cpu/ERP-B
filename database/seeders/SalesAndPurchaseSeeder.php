<?php

namespace Database\Seeders;

use App\Models\CashSale;
use App\Models\CashSaleItem;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTemplate;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\RecurringInvoice;
use App\Models\Refund;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesQuote;
use App\Models\SalesQuoteItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalesAndPurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first() ?? Company::create([
            'name' => 'Falcon Enterprise Ltd',
            'email' => 'admin@falconerp.com',
            'phone' => '+1 800 555 0199',
            'currency' => 'USD',
            'currency_symbol' => '$',
        ]);

        $user = User::first() ?? User::create([
            'company_id' => $company->id,
            'name' => 'System Admin',
            'email' => 'admin@erp.com',
            'password' => bcrypt('password123'),
        ]);

        $warehouses = Warehouse::where('company_id', $company->id)->get();
        if ($warehouses->isEmpty()) {
            $warehouses = collect([
                Warehouse::create([
                    'company_id' => $company->id,
                    'name' => 'Main Logistics Hub',
                    'code' => 'WH-MAIN-01',
                    'is_active' => true,
                ]),
                Warehouse::create([
                    'company_id' => $company->id,
                    'name' => 'West Coast Distribution Center',
                    'code' => 'WH-WEST-02',
                    'is_active' => true,
                ]),
            ]);
        }

        $products = Product::where('company_id', $company->id)->get();
        if ($products->isEmpty()) {
            $products = collect([
                Product::create(['company_id' => $company->id, 'name' => 'Enterprise Server Rack 42U', 'sku' => 'SRV-42U-01', 'sale_price' => 1250.00, 'purchase_price' => 850.00, 'stock_quantity' => 150]),
                Product::create(['company_id' => $company->id, 'name' => 'Gigabit Switch 48-Port PoE', 'sku' => 'NET-SW48-POE', 'sale_price' => 640.00, 'purchase_price' => 410.00, 'stock_quantity' => 300]),
                Product::create(['company_id' => $company->id, 'name' => 'Fiber Optic Transceiver 10G', 'sku' => 'FBR-TRX-10G', 'sale_price' => 85.00, 'purchase_price' => 45.00, 'stock_quantity' => 1000]),
                Product::create(['company_id' => $company->id, 'name' => 'Industrial UPS 3000VA', 'sku' => 'PWR-UPS-3000', 'sale_price' => 890.00, 'purchase_price' => 580.00, 'stock_quantity' => 120]),
                Product::create(['company_id' => $company->id, 'name' => 'Cat6A Shielded Patch Cable 5m', 'sku' => 'CBL-C6A-5M', 'sale_price' => 18.00, 'purchase_price' => 8.50, 'stock_quantity' => 2500]),
            ]);
        }

        // 1. INVOICE TEMPLATES
        $templateData = [
            ['name' => 'Corporate Standard (Default)', 'color' => '#1e40af', 'is_default' => true, 'terms' => 'Net 30 days. Late payments accrue interest at 1.5% per month.'],
            ['name' => 'Modern Minimalist', 'color' => '#0f172a', 'is_default' => false, 'terms' => 'Due on receipt. Wire instructions on invoice header.'],
            ['name' => 'Emerald Executive', 'color' => '#059669', 'is_default' => false, 'terms' => 'Standard enterprise terms apply.'],
            ['name' => 'Tech Indigo Accent', 'color' => '#6366f1', 'is_default' => false, 'terms' => 'Payment due within 15 calendar days.'],
        ];
        foreach ($templateData as $tmpl) {
            InvoiceTemplate::updateOrCreate(
                ['company_id' => $company->id, 'name' => $tmpl['name']],
                [
                    'color_theme' => $tmpl['color'],
                    'header_text' => 'FALCON ENTERPRISE TECHNOLOGIES - GLOBAL SOLUTIONS',
                    'footer_text' => 'Thank you for your business. For billing queries contact billing@falconerp.com',
                    'terms' => $tmpl['terms'],
                    'payment_instructions' => 'Bank: Chase Manhattan | Account: 8847291039 | Swift: CHASUS33',
                    'is_default' => $tmpl['is_default'],
                ]
            );
        }

        // 2. 50 CUSTOMERS
        $customerNames = [
            'Apex Cloud Systems', 'BlueWave Logistics Inc', 'Zenith Tech Solutions', 'Nexus Global Supply',
            'Quantum Dynamics Corp', 'Horizon Healthcare', 'Titan Industrial Tools', 'Vanguard BioPharm',
            'AeroSpace Global LLC', 'Pinnacle Capital Partners', 'Silverline Media Group', 'Summit Retail Hub',
            'Crestview Construction', 'Beacon Renewable Energy', 'Trinity Manufacturing', 'Velocity Auto Parts',
            'Ironclad Cybersecurity', 'Starlight Hospitality Group', 'Prism Design Agency', 'Atlas Shipping Corp',
            'Paramount Food Distributors', 'Omega Semiconductor', 'Novus Robotics', 'Solaria Green Grid',
            'Terra Environmental', 'Astra Defense Systems', 'Catalyst Biotech', 'Eclipse Data Centers',
            'Frontier Mining Corp', 'Cascade Beverage Co', 'Pioneer Agrotech', 'Olympus Heavy Machinery',
            'Elysium Entertainment', 'Highland Paper & Packaging', 'Keystone Warehousing', 'Lighthouse Maritime',
            'Meridian Fiber Networks', 'Northstar Aerospace', 'Orbit Satellite Comms', 'Pacifica Real Estate',
            'Redwood Materials Lab', 'Sequoia Forest Products', 'Timberline Tools', 'Unity Healthcare Group',
            'Valence Chemical Co', 'Westwind Aviation', 'Xenon Automation Systems', 'Yellowstone Hospitality',
            'Zephyr Telecom', 'Centurion Security Services'
        ];

        $customers = collect();
        foreach ($customerNames as $idx => $cName) {
            $code = 'CUST-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT);
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $cName));
            $customer = Customer::updateOrCreate(
                ['company_id' => $company->id, 'customer_code' => $code],
                [
                    'name' => $cName,
                    'company_name' => $cName . ' Group LLC',
                    'email' => "contact@{$slug}.example.com",
                    'phone' => '+1 555 ' . rand(100, 999) . ' ' . rand(1000, 9999),
                    'alternate_phone' => '+1 555 ' . rand(100, 999) . ' ' . rand(1000, 9999),
                    'website' => "https://www.{$slug}.example.com",
                    'tax_number' => 'TAX-' . rand(10000000, 99999999),
                    'gst_number' => 'GST-' . rand(1000000, 9999999),
                    'billing_address' => (rand(100, 9999)) . ' Enterprise Boulevard, Suite ' . rand(100, 500),
                    'shipping_address' => (rand(100, 9999)) . ' Logistics Park, Dock ' . rand(1, 20),
                    'city' => ['San Francisco', 'Chicago', 'Austin', 'New York', 'Seattle', 'Dallas', 'Boston'][rand(0, 6)],
                    'state' => ['CA', 'IL', 'TX', 'NY', 'WA', 'TX', 'MA'][rand(0, 6)],
                    'country' => 'United States',
                    'postal_code' => (string) rand(10001, 99950),
                    'credit_limit' => rand(15, 100) * 1000,
                    'payment_terms' => ['net_15', 'net_30', 'net_60'][rand(0, 2)],
                    'opening_balance' => rand(0, 5000),
                    'status' => 'active',
                ]
            );
            $customers->push($customer);
        }

        // 3. 30 VENDORS
        $vendorNames = [
            'Amphenol Industrial Supply', 'Schneider Electric Hub', 'Siemens Automation Components',
            'Honeywell Process Solutions', 'Corning Optical Fiber Co', 'Delta Electronics Supply',
            'Belden Enterprise Cables', 'Eaton Power Systems', 'ABB Heavy Robotics', 'Rockwell Automation Group',
            'Microchip Technology Direct', 'Texas Instruments Wholesale', 'STMicroelectronics Dist',
            'TE Connectivity Global', 'Molex Micro-interconnect', 'Analog Devices Partner',
            'Infineon Technologies Corp', 'Broadcom Systems Dist', 'Broadcom Optoelectronics',
            'Broadcom Switching Corp', 'Murata Manufacturing Co', 'TDK Electronics Global',
            'Yageo Components Ltd', 'Phoenix Contact USA', 'WAGO Automation Co',
            'Omron Industrial Solutions', 'Keyence Sensor Systems', 'Advantech Embedded IoT',
            'Supermicro Computer Supply', 'Western Digital Enterprise'
        ];

        $vendors = collect();
        foreach ($vendorNames as $vIdx => $vName) {
            $vCode = 'VEND-' . str_pad($vIdx + 1, 4, '0', STR_PAD_LEFT);
            $vSlug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $vName));
            $vendor = Vendor::updateOrCreate(
                ['company_id' => $company->id, 'vendor_code' => $vCode],
                [
                    'name' => $vName,
                    'company_name' => $vName . ' Inc',
                    'contact_person' => 'Procurement Officer ' . ($vIdx + 1),
                    'email' => "orders@{$vSlug}.example.com",
                    'phone' => '+1 800 ' . rand(100, 999) . ' ' . rand(1000, 9999),
                    'alternate_phone' => '+1 800 ' . rand(100, 999) . ' ' . rand(1000, 9999),
                    'website' => "https://www.{$vSlug}.example.com",
                    'tax_number' => 'VTAX-' . rand(10000000, 99999999),
                    'gst_number' => 'VGST-' . rand(1000000, 9999999),
                    'address' => (rand(100, 9000)) . ' Industrial Way, Bldg ' . rand(1, 10),
                    'city' => ['Atlanta', 'Detroit', 'Houston', 'Phoenix', 'San Jose', 'Portland'][rand(0, 5)],
                    'state' => ['GA', 'MI', 'TX', 'AZ', 'CA', 'OR'][rand(0, 5)],
                    'country' => 'United States',
                    'postal_code' => (string) rand(10001, 99950),
                    'bank_name' => 'JPMorgan Chase Enterprise',
                    'account_holder' => $vName . ' Inc',
                    'account_number' => 'ACC' . rand(100000000, 999999999),
                    'ifsc' => 'CHASUS33XXX',
                    'credit_limit' => rand(50, 250) * 1000,
                    'payment_terms' => 'net_30',
                    'opening_balance' => 0,
                    'status' => 'active',
                ]
            );
            $vendors->push($vendor);
        }

        // 4. 100 SALES ORDERS
        $soStatuses = ['confirmed', 'processing', 'partially_delivered', 'delivered', 'completed', 'pending'];
        for ($i = 1; $i <= 100; $i++) {
            $cust = $customers->random();
            $wh = $warehouses->random();
            $orderDate = Carbon::now()->subDays(rand(1, 120));
            $soNumber = 'SO-2026-' . str_pad($i, 5, '0', STR_PAD_LEFT);
            $status = $soStatuses[array_rand($soStatuses)];

            $order = SalesOrder::updateOrCreate(
                ['company_id' => $company->id, 'order_number' => $soNumber],
                [
                    'customer_id' => $cust->id,
                    'warehouse_id' => $wh->id,
                    'salesperson_id' => $user->id,
                    'order_date' => $orderDate->toDateString(),
                    'expected_delivery_date' => $orderDate->copy()->addDays(7)->toDateString(),
                    'reference_number' => 'PO-REF-' . rand(10000, 99999),
                    'subtotal' => 0,
                    'discount' => 0,
                    'tax' => 0,
                    'shipping' => rand(25, 150),
                    'total' => 0,
                    'grand_total' => 0,
                    'paid_amount' => 0,
                    'due_amount' => 0,
                    'payment_status' => in_array($status, ['completed', 'delivered']) ? 'paid' : 'pending',
                    'status' => $status,
                    'notes' => 'Standard corporate delivery requirements apply.',
                    'created_by' => $user->id,
                ]
            );

            // Items
            $subtotal = 0;
            $itemsCount = rand(2, 5);
            $pickedProducts = $products->random(min($itemsCount, $products->count()));
            foreach ($pickedProducts as $prod) {
                $qty = rand(2, 15);
                $unitPrice = (float) (($prod->selling_price && $prod->selling_price > 0) ? $prod->selling_price : (($prod->mrp && $prod->mrp > 0) ? $prod->mrp : 120.00));
                $lineSub = $qty * $unitPrice;
                $lineTax = round($lineSub * 0.08, 2);
                $subtotal += $lineSub;

                SalesOrderItem::updateOrCreate(
                    ['sales_order_id' => $order->id, 'product_id' => $prod->id],
                    [
                        'quantity' => $qty,
                        'unit_id' => $prod->unit_id,
                        'unit_price' => $unitPrice,
                        'discount' => 0,
                        'tax' => $lineTax,
                        'subtotal' => $lineSub + $lineTax,
                        'total' => $lineSub + $lineTax,
                    ]
                );
            }

            $taxTotal = round($subtotal * 0.08, 2);
            $discount = round($subtotal * (rand(0, 5) / 100), 2);
            $grandTotal = $subtotal - $discount + $taxTotal + $order->shipping;
            $paid = in_array($status, ['completed']) ? $grandTotal : (in_array($status, ['delivered']) ? round($grandTotal * 0.5, 2) : 0);
            $due = max(0, $grandTotal - $paid);
            $payStatus = $due <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'pending');

            $order->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $taxTotal,
                'total' => $grandTotal,
                'grand_total' => $grandTotal,
                'paid_amount' => $paid,
                'due_amount' => $due,
                'payment_status' => $payStatus,
            ]);
        }

        // 5. 100 INVOICES
        $invStatuses = ['paid', 'paid', 'sent', 'overdue', 'partially_paid'];
        for ($j = 1; $j <= 100; $j++) {
            $cust = $customers->random();
            $invDate = Carbon::now()->subDays(rand(1, 90));
            $dueDate = $invDate->copy()->addDays(30);
            $invNum = 'INV-2026-' . str_pad($j, 5, '0', STR_PAD_LEFT);
            $status = $invStatuses[array_rand($invStatuses)];

            $invoice = Invoice::updateOrCreate(
                ['company_id' => $company->id, 'invoice_number' => $invNum],
                [
                    'customer_id' => $cust->id,
                    'sales_order_id' => null,
                    'invoice_date' => $invDate->toDateString(),
                    'issue_date' => $invDate->toDateString(),
                    'due_date' => $dueDate->toDateString(),
                    'subtotal' => 0,
                    'discount' => 0,
                    'tax' => 0,
                    'shipping' => rand(15, 100),
                    'round_off' => 0,
                    'total' => 0,
                    'grand_total' => 0,
                    'paid_amount' => 0,
                    'due_amount' => 0,
                    'payment_status' => $status === 'paid' ? 'paid' : ($status === 'partially_paid' ? 'partial' : 'unpaid'),
                    'status' => $status,
                    'created_by' => $user->id,
                ]
            );

            $subtotal = 0;
            $itemsCount = rand(2, 4);
            $pickedProducts = $products->random(min($itemsCount, $products->count()));
            foreach ($pickedProducts as $prod) {
                $qty = rand(2, 10);
                $unitPrice = (float) (($prod->selling_price && $prod->selling_price > 0) ? $prod->selling_price : (($prod->mrp && $prod->mrp > 0) ? $prod->mrp : 120.00));
                $lineSub = $qty * $unitPrice;
                $lineTax = round($lineSub * 0.08, 2);
                $subtotal += $lineSub;

                InvoiceItem::updateOrCreate(
                    ['invoice_id' => $invoice->id, 'product_id' => $prod->id],
                    [
                        'description' => $prod->name ?? 'Standard enterprise equipment',
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'discount' => 0,
                        'tax' => $lineTax,
                        'subtotal' => $lineSub + $lineTax,
                        'total' => $lineSub + $lineTax,
                    ]
                );
            }

            $taxTotal = round($subtotal * 0.08, 2);
            $discount = round($subtotal * 0.02, 2);
            $grandTotal = $subtotal - $discount + $taxTotal + $invoice->shipping;
            $paid = $status === 'paid' ? $grandTotal : ($status === 'partially_paid' ? round($grandTotal * 0.4, 2) : 0);
            $due = max(0, $grandTotal - $paid);

            $invoice->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $taxTotal,
                'total' => $grandTotal,
                'grand_total' => $grandTotal,
                'paid_amount' => $paid,
                'due_amount' => $due,
            ]);

            // Add payment record if paid or partial
            if ($paid > 0) {
                CustomerPayment::updateOrCreate(
                    ['payment_number' => 'PAY-' . str_pad($j, 5, '0', STR_PAD_LEFT)],
                    [
                        'company_id' => $company->id,
                        'customer_id' => $cust->id,
                        'invoice_id' => $invoice->id,
                        'payment_date' => $invDate->copy()->addDays(5)->toDateString(),
                        'amount' => $paid,
                        'payment_method' => ['bank_transfer', 'credit_card', 'ach'][rand(0, 2)],
                        'reference' => 'REF-' . rand(100000, 999999),
                        'notes' => 'Settlement for invoice ' . $invNum,
                        'created_by' => $user->id,
                    ]
                );
            }
        }

        // 6. 50 SALES QUOTES
        $quoteStatuses = ['draft', 'sent', 'accepted', 'converted', 'rejected'];
        for ($q = 1; $q <= 50; $q++) {
            $cust = $customers->random();
            $qDate = Carbon::now()->subDays(rand(1, 60));
            $qNum = 'QUO-2026-' . str_pad($q, 5, '0', STR_PAD_LEFT);
            $status = $quoteStatuses[array_rand($quoteStatuses)];

            $quote = SalesQuote::updateOrCreate(
                ['company_id' => $company->id, 'quote_number' => $qNum],
                [
                    'customer_id' => $cust->id,
                    'salesperson_id' => $user->id,
                    'quote_date' => $qDate->toDateString(),
                    'valid_until' => $qDate->copy()->addDays(30)->toDateString(),
                    'subtotal' => 0,
                    'discount' => 0,
                    'tax' => 0,
                    'shipping' => 50.00,
                    'grand_total' => 0,
                    'status' => $status,
                    'notes' => 'Valid for 30 business days from issue date.',
                    'terms' => 'Price lock guarantee subject to vendor component rates.',
                    'created_by' => $user->id,
                ]
            );

            $subtotal = 0;
            $itemsCount = rand(2, 4);
            $pickedProducts = $products->random(min($itemsCount, $products->count()));
            foreach ($pickedProducts as $prod) {
                $qty = rand(1, 8);
                $unitPrice = (float) (($prod->selling_price && $prod->selling_price > 0) ? $prod->selling_price : (($prod->mrp && $prod->mrp > 0) ? $prod->mrp : 120.00));
                $lineSub = $qty * $unitPrice;
                $lineTax = round($lineSub * 0.08, 2);
                $subtotal += $lineSub;

                SalesQuoteItem::updateOrCreate(
                    ['sales_quote_id' => $quote->id, 'product_id' => $prod->id],
                    [
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'discount' => 0,
                        'tax' => $lineTax,
                        'subtotal' => $lineSub + $lineTax,
                    ]
                );
            }

            $taxTotal = round($subtotal * 0.08, 2);
            $grandTotal = $subtotal + $taxTotal + $quote->shipping;
            $quote->update([
                'subtotal' => $subtotal,
                'tax' => $taxTotal,
                'grand_total' => $grandTotal,
            ]);
        }

        // 7. 20 CREDIT NOTES
        for ($cn = 1; $cn <= 20; $cn++) {
            $cust = $customers->random();
            $cnNum = 'CN-2026-' . str_pad($cn, 5, '0', STR_PAD_LEFT);
            $prod = $products->random();
            $qty = rand(1, 4);
            $price = (float) (($prod->selling_price && $prod->selling_price > 0) ? $prod->selling_price : (($prod->mrp && $prod->mrp > 0) ? $prod->mrp : 120.00));
            $amount = $qty * $price;
            $tax = round($amount * 0.08, 2);

            $note = CreditNote::updateOrCreate(
                ['company_id' => $company->id, 'credit_note_number' => $cnNum],
                [
                    'customer_id' => $cust->id,
                    'invoice_id' => null,
                    'date' => Carbon::now()->subDays(rand(2, 45))->toDateString(),
                    'reason' => 'Customer equipment upgrade exchange / return of surplus units.',
                    'subtotal' => $amount,
                    'tax' => $tax,
                    'total' => $amount + $tax,
                    'status' => ['issued', 'applied', 'draft'][rand(0, 2)],
                    'created_by' => $user->id,
                ]
            );

            CreditNoteItem::updateOrCreate(
                ['credit_note_id' => $note->id, 'product_id' => $prod->id],
                [
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'tax' => $tax,
                    'subtotal' => $amount + $tax,
                ]
            );
        }

        // 8. 20 REFUNDS
        $refundStatuses = ['requested', 'approved', 'processed'];
        for ($rf = 1; $rf <= 20; $rf++) {
            $cust = $customers->random();
            $rfNum = 'REF-2026-' . str_pad($rf, 5, '0', STR_PAD_LEFT);
            Refund::updateOrCreate(
                ['company_id' => $company->id, 'refund_number' => $rfNum],
                [
                    'customer_id' => $cust->id,
                    'invoice_id' => null,
                    'refund_date' => Carbon::now()->subDays(rand(1, 30))->toDateString(),
                    'reason' => 'Duplicate settlement or contract service level adjustment.',
                    'refund_amount' => rand(150, 2500),
                    'refund_method' => ['bank_transfer', 'card'][rand(0, 1)],
                    'status' => $refundStatuses[array_rand($refundStatuses)],
                    'created_by' => $user->id,
                ]
            );
        }

        // 9. 50 DELIVERY NOTES
        $dnStatuses = ['pending', 'packed', 'dispatched', 'delivered'];
        for ($dn = 1; $dn <= 50; $dn++) {
            $cust = $customers->random();
            $wh = $warehouses->random();
            $dnNum = 'DN-2026-' . str_pad($dn, 5, '0', STR_PAD_LEFT);
            $status = $dnStatuses[array_rand($dnStatuses)];

            $delivery = DeliveryNote::updateOrCreate(
                ['company_id' => $company->id, 'delivery_number' => $dnNum],
                [
                    'customer_id' => $cust->id,
                    'sales_order_id' => null,
                    'warehouse_id' => $wh->id,
                    'delivery_date' => Carbon::now()->subDays(rand(1, 40))->toDateString(),
                    'delivery_address' => $cust->shipping_address ?? 'Enterprise Hub Dock 4',
                    'driver_name' => 'Driver ' . ['John Miller', 'Carlos Mendez', 'David Chen', 'Sarah Jenkins'][rand(0, 3)],
                    'vehicle_number' => 'Freightliner Van #' . rand(10, 99),
                    'status' => $status,
                    'notes' => 'Special handling: fragile sensitive electronics.',
                    'created_by' => $user->id,
                ]
            );

            $prod = $products->random();
            $qty = rand(5, 20);
            $deliveredQty = in_array($status, ['delivered']) ? $qty : (in_array($status, ['dispatched']) ? round($qty * 0.8) : 0);
            DeliveryNoteItem::updateOrCreate(
                ['delivery_note_id' => $delivery->id, 'product_id' => $prod->id],
                [
                    'ordered_quantity' => $qty,
                    'delivered_quantity' => $deliveredQty,
                    'remaining_quantity' => max(0, $qty - $deliveredQty),
                ]
            );
        }

        // 10. 100 PURCHASE ORDERS
        $poStatuses = ['approved', 'ordered', 'received', 'completed', 'pending_approval'];
        for ($p = 1; $p <= 100; $p++) {
            $vend = $vendors->random();
            $wh = $warehouses->random();
            $poDate = Carbon::now()->subDays(rand(1, 120));
            $poNum = 'PO-2026-' . str_pad($p, 5, '0', STR_PAD_LEFT);
            $status = $poStatuses[array_rand($poStatuses)];

            $pOrder = PurchaseOrder::updateOrCreate(
                ['company_id' => $company->id, 'po_number' => $poNum],
                [
                    'vendor_id' => $vend->id,
                    'warehouse_id' => $wh->id,
                    'po_date' => $poDate->toDateString(),
                    'order_date' => $poDate->toDateString(),
                    'expected_delivery_date' => $poDate->copy()->addDays(14)->toDateString(),
                    'subtotal' => 0,
                    'discount' => 0,
                    'tax' => 0,
                    'shipping' => rand(50, 300),
                    'total' => 0,
                    'grand_total' => 0,
                    'payment_status' => in_array($status, ['received', 'completed']) ? 'paid' : 'pending',
                    'status' => $status,
                    'notes' => 'Direct OEM freight shipment.',
                    'created_by' => $user->id,
                ]
            );

            $subtotal = 0;
            $itemsCount = rand(2, 5);
            $pickedProducts = $products->random(min($itemsCount, $products->count()));
            foreach ($pickedProducts as $prod) {
                $qty = rand(5, 30);
                $unitCost = (float) (($prod->purchase_price && $prod->purchase_price > 0) ? $prod->purchase_price : (($prod->cost_price && $prod->cost_price > 0) ? $prod->cost_price : 75.00));
                $lineSub = $qty * $unitCost;
                $lineTax = round($lineSub * 0.08, 2);
                $subtotal += $lineSub;

                PurchaseOrderItem::updateOrCreate(
                    ['purchase_order_id' => $pOrder->id, 'product_id' => $prod->id],
                    [
                        'product_id' => $prod->id,
                        'quantity' => $qty,
                        'unit_id' => $prod->unit_id,
                        'unit_cost' => $unitCost,
                        'unit_price' => $unitCost,
                        'discount' => 0,
                        'tax' => $lineTax,
                        'subtotal' => $lineSub + $lineTax,
                        'total' => $lineSub + $lineTax,
                    ]
                );
            }

            $taxTotal = round($subtotal * 0.08, 2);
            $discount = round($subtotal * (rand(0, 4) / 100), 2);
            $grandTotal = $subtotal - $discount + $taxTotal + $pOrder->shipping;

            $pOrder->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $taxTotal,
                'total' => $grandTotal,
                'grand_total' => $grandTotal,
            ]);
        }

        // 11. 100 PURCHASES (RECEIPTS & DIRECT PURCHASES)
        $purchaseStatuses = ['received', 'received', 'paid', 'partially_paid', 'draft'];
        for ($k = 1; $k <= 100; $k++) {
            $vend = $vendors->random();
            $wh = $warehouses->random();
            $purDate = Carbon::now()->subDays(rand(1, 90));
            $purNum = 'PUR-2026-' . str_pad($k, 5, '0', STR_PAD_LEFT);
            $status = $purchaseStatuses[array_rand($purchaseStatuses)];

            $purchase = Purchase::updateOrCreate(
                ['company_id' => $company->id, 'purchase_number' => $purNum],
                [
                    'vendor_id' => $vend->id,
                    'purchase_order_id' => null,
                    'warehouse_id' => $wh->id,
                    'vendor_invoice_number' => 'VINV-' . rand(100000, 999999),
                    'invoice_date' => $purDate->toDateString(),
                    'subtotal' => 0,
                    'discount' => 0,
                    'tax' => 0,
                    'shipping' => rand(30, 200),
                    'grand_total' => 0,
                    'paid_amount' => 0,
                    'due_amount' => 0,
                    'payment_status' => $status === 'paid' ? 'paid' : ($status === 'partially_paid' ? 'partial' : 'pending'),
                    'status' => in_array($status, ['paid', 'partially_paid']) ? 'received' : $status,
                    'created_by' => $user->id,
                ]
            );

            $subtotal = 0;
            $itemsCount = rand(2, 4);
            $pickedProducts = $products->random(min($itemsCount, $products->count()));
            foreach ($pickedProducts as $prod) {
                $qty = rand(4, 25);
                $unitCost = (float) (($prod->purchase_price && $prod->purchase_price > 0) ? $prod->purchase_price : (($prod->cost_price && $prod->cost_price > 0) ? $prod->cost_price : 75.00));
                $lineSub = $qty * $unitCost;
                $lineTax = round($lineSub * 0.08, 2);
                $subtotal += $lineSub;

                PurchaseItem::updateOrCreate(
                    ['purchase_id' => $purchase->id, 'product_id' => $prod->id],
                    [
                        'quantity' => $qty,
                        'unit_id' => $prod->unit_id,
                        'unit_cost' => $unitCost,
                        'discount' => 0,
                        'tax' => $lineTax,
                        'subtotal' => $lineSub + $lineTax,
                    ]
                );
            }

            $taxTotal = round($subtotal * 0.08, 2);
            $discount = round($subtotal * 0.01, 2);
            $grandTotal = $subtotal - $discount + $taxTotal + $purchase->shipping;
            $paid = $status === 'paid' ? $grandTotal : ($status === 'partially_paid' ? round($grandTotal * 0.5, 2) : 0);
            $due = max(0, $grandTotal - $paid);

            $purchase->update([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $taxTotal,
                'grand_total' => $grandTotal,
                'paid_amount' => $paid,
                'due_amount' => $due,
            ]);

            if ($paid > 0) {
                VendorPayment::updateOrCreate(
                    ['payment_number' => 'VPAY-' . str_pad($k, 5, '0', STR_PAD_LEFT)],
                    [
                        'company_id' => $company->id,
                        'vendor_id' => $vend->id,
                        'purchase_id' => $purchase->id,
                        'payment_date' => $purDate->copy()->addDays(10)->toDateString(),
                        'amount' => $paid,
                        'payment_method' => ['bank_transfer', 'wire_transfer'][rand(0, 1)],
                        'reference' => 'VREF-' . rand(100000, 999999),
                        'notes' => 'Settlement for purchase ' . $purNum,
                        'created_by' => $user->id,
                    ]
                );
            }
        }

        // 12. 25 PURCHASE RETURNS
        $retStatuses = ['draft', 'requested', 'approved', 'returned', 'completed'];
        for ($r = 1; $r <= 25; $r++) {
            $vend = $vendors->random();
            $wh = $warehouses->random();
            $rNum = 'PR-2026-' . str_pad($r, 5, '0', STR_PAD_LEFT);
            $prod = $products->random();
            $qty = rand(2, 6);
            $cost = $qty * (float) (($prod->purchase_price && $prod->purchase_price > 0) ? $prod->purchase_price : (($prod->cost_price && $prod->cost_price > 0) ? $prod->cost_price : 75.00));
            $tax = round($cost * 0.08, 2);

            $pReturn = PurchaseReturn::updateOrCreate(
                ['company_id' => $company->id, 'return_number' => $rNum],
                [
                    'vendor_id' => $vend->id,
                    'purchase_id' => null,
                    'warehouse_id' => $wh->id,
                    'return_date' => Carbon::now()->subDays(rand(2, 45))->toDateString(),
                    'reason' => 'Defective batch during QA inspection or vendor recall.',
                    'status' => $retStatuses[array_rand($retStatuses)],
                    'subtotal' => $cost,
                    'tax' => $tax,
                    'grand_total' => $cost + $tax,
                    'created_by' => $user->id,
                ]
            );

            PurchaseReturnItem::updateOrCreate(
                ['purchase_return_id' => $pReturn->id, 'product_id' => $prod->id],
                [
                    'quantity' => $qty,
                    'unit_cost' => ($qty > 0 ? round($cost / $qty, 2) : 75.00),
                    'tax' => $tax,
                    'total' => $cost + $tax,
                ]
            );
        }

        // 13. RECURRING INVOICES
        $template = InvoiceTemplate::where('company_id', $company->id)->first();
        for ($rc = 1; $rc <= 10; $rc++) {
            $cust = $customers->random();
            RecurringInvoice::updateOrCreate(
                ['company_id' => $company->id, 'customer_id' => $cust->id, 'frequency' => 'monthly'],
                [
                    'template_id' => $template?->id,
                    'start_date' => Carbon::now()->subMonths(3)->toDateString(),
                    'end_date' => Carbon::now()->addMonths(9)->toDateString(),
                    'frequency' => ['monthly', 'quarterly', 'yearly'][rand(0, 2)],
                    'amount' => rand(1500, 9500),
                    'next_invoice_date' => Carbon::now()->addDays(rand(5, 25))->toDateString(),
                    'payment_terms' => 'net_30',
                    'status' => 'active',
                ]
            );
        }

        // 14. 15 CASH SALES
        for ($cs = 1; $cs <= 15; $cs++) {
            $cust = $customers->random();
            $wh = $warehouses->random();
            $csNum = 'CS-2026-' . str_pad($cs, 5, '0', STR_PAD_LEFT);
            $prod = $products->random();
            $qty = rand(1, 3);
            $price = (float) (($prod->selling_price && $prod->selling_price > 0) ? $prod->selling_price : (($prod->mrp && $prod->mrp > 0) ? $prod->mrp : 120.00));
            $lineSub = $qty * $price;
            $lineTax = round($lineSub * 0.08, 2);
            $total = $lineSub + $lineTax;

            $cashSale = CashSale::updateOrCreate(
                ['company_id' => $company->id, 'sale_number' => $csNum],
                [
                    'customer_id' => $cust->id,
                    'warehouse_id' => $wh->id,
                    'sale_date' => Carbon::now()->subDays(rand(1, 15))->toDateString(),
                    'subtotal' => $lineSub,
                    'discount' => 0,
                    'tax' => $lineTax,
                    'grand_total' => $total,
                    'payment_method' => ['cash', 'card', 'upi'][rand(0, 2)],
                    'paid_amount' => $total,
                    'change_amount' => 0,
                    'created_by' => $user->id,
                ]
            );

            CashSaleItem::updateOrCreate(
                ['cash_sale_id' => $cashSale->id, 'product_id' => $prod->id],
                [
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'discount' => 0,
                    'tax' => $lineTax,
                    'subtotal' => $total,
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first() ?? Company::create(['name' => 'Falcon Technologies Inc.', 'code' => 'FTI']);
        $adminUser = User::first() ?? User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@falconerp.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $companyId = $company->id;
        $userId = $adminUser->id;

        // 1. Categories (10 items, hierarchical)
        $parentCategories = [
            ['name' => 'Electronics & IT', 'slug' => 'electronics-it', 'description' => 'Enterprise computing, servers and peripherals'],
            ['name' => 'Industrial Hardware', 'slug' => 'industrial-hardware', 'description' => 'Heavy duty equipment, tools and spare components'],
            ['name' => 'Office Supplies', 'slug' => 'office-supplies', 'description' => 'Corporate workplace essentials and stationary'],
            ['name' => 'Networking & Telecom', 'slug' => 'networking-telecom', 'description' => 'Switches, routers, optical fiber and telecom gear'],
        ];

        $createdParentCats = [];
        foreach ($parentCategories as $cat) {
            $createdParentCats[$cat['slug']] = Category::firstOrCreate(
                ['slug' => $cat['slug']],
                array_merge($cat, ['company_id' => $companyId, 'status' => 'active'])
            );
        }

        $subCategories = [
            ['name' => 'Enterprise Servers', 'slug' => 'enterprise-servers', 'parent_slug' => 'electronics-it'],
            ['name' => 'Laptops & Workstations', 'slug' => 'laptops-workstations', 'parent_slug' => 'electronics-it'],
            ['name' => 'Computer Peripherals', 'slug' => 'computer-peripherals', 'parent_slug' => 'electronics-it'],
            ['name' => 'Industrial Machinery', 'slug' => 'industrial-machinery', 'parent_slug' => 'industrial-hardware'],
            ['name' => 'Safety & PPE Equipment', 'slug' => 'safety-ppe-equipment', 'parent_slug' => 'industrial-hardware'],
            ['name' => 'Switches & Routers', 'slug' => 'switches-routers', 'parent_slug' => 'networking-telecom'],
        ];

        $allCategories = array_values($createdParentCats);
        foreach ($subCategories as $sub) {
            $parent = $createdParentCats[$sub['parent_slug']] ?? null;
            $cat = Category::firstOrCreate(
                ['slug' => $sub['slug']],
                [
                    'company_id' => $companyId,
                    'parent_id' => $parent?->id,
                    'name' => $sub['name'],
                    'description' => "Subcategory under {$parent?->name}",
                    'status' => 'active',
                ]
            );
            $allCategories[] = $cat;
        }

        // 2. Brands (20 items)
        $brandNames = [
            'Dell Technologies', 'HP Enterprise', 'Apple Inc.', 'Samsung Electronics',
            'Lenovo Enterprise', 'Cisco Systems', 'Logitech Commercial', 'Sony Professional',
            'Microsoft Surface', 'Intel Corp', 'AMD Processors', 'Asus Commercial',
            'Acer Professional', 'Canon Solutions', 'Epson Precision', 'Bosch Industrial',
            'Schneider Electric', 'Siemens AG', '3M Industrial', 'Zebra Technologies'
        ];

        $allBrands = [];
        foreach ($brandNames as $bName) {
            $allBrands[] = Brand::firstOrCreate(
                ['name' => $bName],
                [
                    'company_id' => $companyId,
                    'slug' => Str::slug($bName),
                    'website' => 'https://' . Str::slug($bName) . '.com',
                    'description' => "Enterprise vendor {$bName}",
                    'status' => 'active',
                ]
            );
        }

        // 3. Units (10 items)
        $unitsData = [
            ['name' => 'Piece', 'short_name' => 'pcs', 'short_code' => 'PCS', 'unit_type' => 'quantity', 'conversion_factor' => 1.0000],
            ['name' => 'Box', 'short_name' => 'box', 'short_code' => 'BOX', 'unit_type' => 'pack', 'conversion_factor' => 10.0000],
            ['name' => 'Kilogram', 'short_name' => 'kg', 'short_code' => 'KG', 'unit_type' => 'weight', 'conversion_factor' => 1.0000],
            ['name' => 'Gram', 'short_name' => 'g', 'short_code' => 'G', 'unit_type' => 'weight', 'conversion_factor' => 0.0010],
            ['name' => 'Liter', 'short_name' => 'l', 'short_code' => 'L', 'unit_type' => 'volume', 'conversion_factor' => 1.0000],
            ['name' => 'Meter', 'short_name' => 'm', 'short_code' => 'M', 'unit_type' => 'length', 'conversion_factor' => 1.0000],
            ['name' => 'Dozen', 'short_name' => 'dz', 'short_code' => 'DZ', 'unit_type' => 'pack', 'conversion_factor' => 12.0000],
            ['name' => 'Pack', 'short_name' => 'pk', 'short_code' => 'PK', 'unit_type' => 'pack', 'conversion_factor' => 5.0000],
            ['name' => 'Roll', 'short_name' => 'rl', 'short_code' => 'RL', 'unit_type' => 'length', 'conversion_factor' => 50.0000],
            ['name' => 'Set', 'short_name' => 'set', 'short_code' => 'SET', 'unit_type' => 'quantity', 'conversion_factor' => 1.0000],
        ];

        $allUnits = [];
        foreach ($unitsData as $u) {
            $allUnits[] = Unit::firstOrCreate(
                ['name' => $u['name']],
                array_merge($u, ['company_id' => $companyId, 'status' => 'active'])
            );
        }

        // 4. Warehouses (5 items)
        $warehousesData = [
            ['name' => 'Main Central Hub', 'code' => 'WH-CENTRAL', 'type' => 'main', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'manager_name' => 'Rajesh Sharma'],
            ['name' => 'North Logistics Depot', 'code' => 'WH-NORTH', 'type' => 'distribution', 'city' => 'Delhi NCR', 'state' => 'Delhi', 'manager_name' => 'Amit Verma'],
            ['name' => 'South Regional Depot', 'code' => 'WH-SOUTH', 'type' => 'branch', 'city' => 'Bengaluru', 'state' => 'Karnataka', 'manager_name' => 'Priya Nair'],
            ['name' => 'West Coast Distribution Center', 'code' => 'WH-WEST', 'type' => 'storage', 'city' => 'Ahmedabad', 'state' => 'Gujarat', 'manager_name' => 'Kavita Patel'],
            ['name' => 'East Regional Facility', 'code' => 'WH-EAST', 'type' => 'storage', 'city' => 'Kolkata', 'state' => 'West Bengal', 'manager_name' => 'Suman Ghosh'],
        ];

        $allWarehouses = [];
        foreach ($warehousesData as $wh) {
            $allWarehouses[] = Warehouse::firstOrCreate(
                ['code' => $wh['code']],
                array_merge($wh, [
                    'company_id' => $companyId,
                    'manager_id' => $userId,
                    'email' => strtolower($wh['code']) . '@falconerp.com',
                    'phone' => '+91 98' . rand(10000000, 99999999),
                    'country' => 'India',
                    'status' => 'active',
                ])
            );
        }

        // 5. Suppliers (30 items)
        $supplierNames = [
            'Reliance Industrial Supply Ltd', 'Tata Advanced Systems', 'Larsen & Toubro Material Div',
            'Adani Enterprise Procurement', 'Wipro Technologies Hardware', 'Infosys Infrastructure Supplies',
            'Dell Direct Enterprise India', 'HP Global Solutions Pvt Ltd', 'Cisco Systems India Logistics',
            'Samsung Commercial Electronics', 'Schneider Electric Hub', 'Siemens Industrial Automation',
            'Foxconn Precision Hardware', 'Redington India Distribution', 'Ingram Micro India Ltd',
            'Compuage Infocom Logistics', 'Savex Technologies Pvt Ltd', 'Supertron Electronics Ltd',
            'Iris Computers Infrastructure', 'Neoteric Infomatique Hardware', 'HCL Infosystems Distribution',
            'Godrej Tooling & Hardware', 'Bharat Forge Component Supply', 'Kirloskar Heavy Systems',
            'Blue Star Commercial Supplies', 'Voltas Industrial Solutions', 'Havells Commercial Lighting',
            'Finolex Cables & Networking', 'Polycab High-Voltage Networks', 'Orient Electric Commercial Supply'
        ];

        $allSuppliers = [];
        foreach ($supplierNames as $i => $sName) {
            $supCode = 'SUP-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
            $allSuppliers[] = Supplier::firstOrCreate(
                ['supplier_code' => $supCode],
                [
                    'company_id' => $companyId,
                    'name' => $sName,
                    'company_name' => $sName,
                    'contact_person' => 'Manager ' . Str::after($sName, ' '),
                    'email' => 'contact@' . Str::slug(Str::limit($sName, 15, '')) . '.com',
                    'phone' => '+91 91' . rand(10000000, 99999999),
                    'gst_number' => '27AAACG' . rand(1000, 9999) . 'A1Z' . rand(1, 9),
                    'city' => $warehousesData[$i % 5]['city'],
                    'state' => $warehousesData[$i % 5]['state'],
                    'country' => 'India',
                    'status' => 'active',
                ]
            );
        }

        // 6. Products (100 items)
        $productTemplates = [
            ['prefix' => 'PowerEdge Server', 'base_cost' => 125000, 'cat' => 0, 'brand' => 0],
            ['prefix' => 'ProLiant DL380 Server', 'base_cost' => 145000, 'cat' => 0, 'brand' => 1],
            ['prefix' => 'ThinkSystem SR650 Server', 'base_cost' => 135000, 'cat' => 0, 'brand' => 4],
            ['prefix' => 'Catalyst 9300 Switch', 'base_cost' => 85000, 'cat' => 3, 'brand' => 5],
            ['prefix' => 'Nexus 9000 Core Switch', 'base_cost' => 240000, 'cat' => 3, 'brand' => 5],
            ['prefix' => 'Precision 5820 Workstation', 'base_cost' => 95000, 'cat' => 0, 'brand' => 0],
            ['prefix' => 'ZBook Fury G8 Mobile Workstation', 'base_cost' => 110000, 'cat' => 0, 'brand' => 1],
            ['prefix' => 'ThinkPad P1 Gen 4 Workstation', 'base_cost' => 105000, 'cat' => 0, 'brand' => 4],
            ['prefix' => 'MacBook Pro M3 Max 16-inch', 'base_cost' => 220000, 'cat' => 0, 'brand' => 2],
            ['prefix' => 'UltraSharp 32-inch 4K Monitor', 'base_cost' => 38000, 'cat' => 0, 'brand' => 0],
            ['prefix' => 'Surface Pro 9 Commercial Tablet', 'base_cost' => 78000, 'cat' => 0, 'brand' => 8],
            ['prefix' => 'Xeon Platinum 8480+ Processor', 'base_cost' => 180000, 'cat' => 0, 'brand' => 9],
            ['prefix' => 'EPYC 9654 96-Core Server CPU', 'base_cost' => 195000, 'cat' => 0, 'brand' => 10],
            ['prefix' => 'Industrial Barcode Scanner 2D', 'base_cost' => 14000, 'cat' => 1, 'brand' => 19],
            ['prefix' => 'Enterprise RFID Gate Reader', 'base_cost' => 62000, 'cat' => 1, 'brand' => 19],
            ['prefix' => 'Smart-UPS RT On-Line 5kVA', 'base_cost' => 89000, 'cat' => 1, 'brand' => 16],
            ['prefix' => 'Industrial Hydraulic Actuator', 'base_cost' => 45000, 'cat' => 1, 'brand' => 17],
            ['prefix' => 'High-Speed CNC Servo Motor', 'base_cost' => 32000, 'cat' => 1, 'brand' => 15],
            ['prefix' => '3M Full-Face Respirator Kit', 'base_cost' => 4500, 'cat' => 1, 'brand' => 18],
            ['prefix' => 'Multi-Mode Fiber Optic Reel 500m', 'base_cost' => 28000, 'cat' => 3, 'brand' => 5],
        ];

        $allProducts = [];
        $pCount = 0;

        for ($cycle = 1; $cycle <= 5; $cycle++) {
            foreach ($productTemplates as $idx => $tmpl) {
                $pCount++;
                $sku = 'PRD-' . strtoupper(substr(Str::slug($tmpl['prefix']), 0, 3)) . '-' . str_pad($pCount, 4, '0', STR_PAD_LEFT);
                $name = $tmpl['prefix'] . " Gen {$cycle} (Model " . chr(65 + ($pCount % 6)) . ")";
                $cost = $tmpl['base_cost'] + (($pCount * 120) % 5000);
                $sell = round($cost * 1.25, 2);
                $mrp = round($sell * 1.10, 2);

                $cat = $allCategories[$tmpl['cat'] % count($allCategories)];
                $brand = $allBrands[$tmpl['brand'] % count($allBrands)];
                $unit = $allUnits[$pCount % count($allUnits)];
                $defWarehouse = $allWarehouses[$pCount % count($allWarehouses)];

                $allProducts[] = Product::firstOrCreate(
                    ['sku' => $sku],
                    [
                        'company_id' => $companyId,
                        'category_id' => $cat->id,
                        'brand_id' => $brand->id,
                        'unit_id' => $unit->id,
                        'default_warehouse_id' => $defWarehouse->id,
                        'name' => $name,
                        'slug' => Str::slug($name) . '-' . strtolower(Str::random(4)),
                        'barcode' => '890' . rand(1000000000, 9999999999),
                        'product_code' => 'PC-' . str_pad($pCount, 5, '0', STR_PAD_LEFT),
                        'cost_price' => $cost,
                        'purchase_price' => $cost,
                        'selling_price' => $sell,
                        'mrp' => $mrp,
                        'discount' => 5.0,
                        'tax_rate' => 18.0,
                        'tax_type' => 'exclusive',
                        'alert_quantity' => 10,
                        'minimum_stock' => 10,
                        'maximum_stock' => 500,
                        'reorder_level' => 20,
                        'track_inventory' => true,
                        'status' => 'active',
                        'created_by' => $userId,
                        'description' => "Enterprise grade hardware component: {$name}. Reliable, high durability, backed by official warranty.",
                    ]
                );
            }
        }

        // 7. Stock Records (1000 items: 100 products x 5 warehouses + varied stock distribution)
        $stockRecords = [];
        $totalStockMovementsCount = 0;

        foreach ($allProducts as $pIdx => $prod) {
            foreach ($allWarehouses as $wIdx => $wh) {
                // Determine realistic stock distribution (some healthy, some low, some zero)
                if ($pIdx < 12 && $wIdx === 0) {
                    // Out of stock
                    $avail = 0;
                    $reserved = 0;
                    $damaged = 0;
                } elseif ($pIdx < 35 && $wIdx === 0) {
                    // Low or critical stock
                    $avail = rand(2, 9);
                    $reserved = rand(0, 2);
                    $damaged = rand(0, 1);
                } else {
                    // Healthy stock
                    $avail = rand(25, 180);
                    $reserved = rand(0, 15);
                    $damaged = rand(0, 3);
                }

                $qty = $avail + $reserved + $damaged;
                $avgCost = (float) ($prod->purchase_price ?: $prod->cost_price);

                $stock = Stock::updateOrCreate(
                    [
                        'product_id' => $prod->id,
                        'warehouse_id' => $wh->id,
                    ],
                    [
                        'company_id' => $companyId,
                        'quantity' => $qty,
                        'available_quantity' => $avail,
                        'reserved_quantity' => $reserved,
                        'damaged_quantity' => $damaged,
                        'average_cost' => $avgCost,
                        'last_movement_at' => Carbon::now()->subDays(rand(0, 60)),
                    ]
                );
                $stockRecords[] = $stock;
            }
        }

        // 8. Stock Movements (500 records)
        $movementTypes = ['opening', 'purchase', 'sale', 'adjustment', 'transfer_in', 'transfer_out'];
        for ($m = 1; $m <= 500; $m++) {
            $prod = $allProducts[$m % count($allProducts)];
            $wh = $allWarehouses[$m % count($allWarehouses)];
            $mType = $movementTypes[$m % count($movementTypes)];
            $qty = rand(5, 50);
            $cost = (float) ($prod->purchase_price ?: $prod->cost_price);

            $isIn = in_array($mType, ['opening', 'purchase', 'transfer_in']);
            $qtyIn = $isIn ? $qty : 0;
            $qtyOut = !$isIn ? $qty : 0;

            StockMovement::create([
                'company_id' => $companyId,
                'product_id' => $prod->id,
                'warehouse_id' => $wh->id,
                'type' => $isIn ? 'in' : 'out',
                'movement_type' => $mType,
                'quantity' => $qty,
                'quantity_in' => $qtyIn,
                'quantity_out' => $qtyOut,
                'balance_quantity' => rand(50, 300),
                'unit_cost' => $cost,
                'total_cost' => round($qty * $cost, 2),
                'reference' => strtoupper(substr($mType, 0, 3)) . '-' . str_pad($m, 5, '0', STR_PAD_LEFT),
                'notes' => "Automated transaction record for {$mType}",
                'created_by' => $userId,
                'created_at' => Carbon::now()->subDays(rand(0, 90))->subHours(rand(0, 23)),
            ]);
        }

        // 9. Stock Adjustments (30 records with items)
        for ($a = 1; $a <= 30; $a++) {
            $wh = $allWarehouses[$a % count($allWarehouses)];
            $status = $a <= 20 ? 'applied' : ($a <= 25 ? 'pending' : 'draft');
            $ref = 'ADJ-2026-' . str_pad($a, 4, '0', STR_PAD_LEFT);

            $adj = StockAdjustment::firstOrCreate(
                ['reference_number' => $ref],
                [
                    'company_id' => $companyId,
                    'warehouse_id' => $wh->id,
                    'adjustment_type' => $a % 2 === 0 ? 'increase' : 'decrease',
                    'reason' => 'Quarterly physical inventory count audit - variance correction',
                    'status' => $status,
                    'notes' => 'Verified against barcode scanner audit batch',
                    'created_by' => $userId,
                    'approved_by' => $status === 'applied' ? $userId : null,
                    'approved_at' => $status === 'applied' ? Carbon::now()->subDays(rand(1, 20)) : null,
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                ]
            );

            // Add 2-3 items
            $itemCount = rand(2, 3);
            for ($it = 0; $it < $itemCount; $it++) {
                $p = $allProducts[($a * 3 + $it) % count($allProducts)];
                $sysQty = rand(50, 100);
                $diff = $a % 2 === 0 ? rand(2, 10) : -rand(2, 8);
                $actualQty = $sysQty + $diff;
                $cost = (float) ($p->purchase_price ?: $p->cost_price);

                StockAdjustmentItem::firstOrCreate(
                    [
                        'adjustment_id' => $adj->id,
                        'product_id' => $p->id,
                    ],
                    [
                        'system_quantity' => $sysQty,
                        'actual_quantity' => $actualQty,
                        'difference' => $diff,
                        'unit_cost' => $cost,
                        'notes' => 'Audited variance verified',
                    ]
                );
            }
        }

        // 10. Stock Transfers (20 records with items)
        $transferStatuses = ['draft', 'pending', 'approved', 'in_transit', 'received', 'cancelled'];
        for ($t = 1; $t <= 20; $t++) {
            $fromWh = $allWarehouses[$t % count($allWarehouses)];
            $toWh = $allWarehouses[($t + 1) % count($allWarehouses)];
            $status = $transferStatuses[$t % count($transferStatuses)];
            $ref = 'TRF-2026-' . str_pad($t, 4, '0', STR_PAD_LEFT);

            $transfer = StockTransfer::firstOrCreate(
                ['transfer_number' => $ref],
                [
                    'company_id' => $companyId,
                    'from_warehouse_id' => $fromWh->id,
                    'to_warehouse_id' => $toWh->id,
                    'status' => $status,
                    'reason' => 'Inter-depot regional replenishment to prevent stock-out',
                    'notes' => 'Handled via priority express logistics carrier',
                    'created_by' => $userId,
                    'approved_by' => in_array($status, ['approved', 'in_transit', 'received']) ? $userId : null,
                    'approved_at' => in_array($status, ['approved', 'in_transit', 'received']) ? Carbon::now()->subDays(rand(2, 15)) : null,
                    'received_by' => $status === 'received' ? $userId : null,
                    'received_at' => $status === 'received' ? Carbon::now()->subDays(rand(1, 5)) : null,
                    'created_at' => Carbon::now()->subDays(rand(1, 40)),
                ]
            );

            // Add items
            for ($it = 0; $it < 2; $it++) {
                $p = $allProducts[($t * 4 + $it) % count($allProducts)];
                $cost = (float) ($p->purchase_price ?: $p->cost_price);

                StockTransferItem::firstOrCreate(
                    [
                        'transfer_id' => $transfer->id,
                        'product_id' => $p->id,
                    ],
                    [
                        'quantity' => rand(10, 40),
                        'unit_cost' => $cost,
                        'notes' => 'Palletized carton shipment',
                    ]
                );
            }
        }
    }
}

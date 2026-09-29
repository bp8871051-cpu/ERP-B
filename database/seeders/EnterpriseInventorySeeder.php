<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\InventoryAlert;
use App\Models\InventorySnapshot;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Stock;
use App\Models\StockForecast;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EnterpriseInventorySeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first() ?? Company::create(['name' => 'Falcon LLP', 'currency' => 'INR']);
        $companyId = $company->id;
        $admin = User::first();
        $adminId = $admin?->id ?? 1;

        // 1. Ensure Warehouses have realistic Indian names and capacities
        $warehouseData = [
            ['name' => 'Main Warehouse', 'code' => 'WH-MUM-01', 'city' => 'Mumbai', 'capacity' => 12000],
            ['name' => 'Ahmedabad Warehouse', 'code' => 'WH-AMD-02', 'city' => 'Ahmedabad', 'capacity' => 8500],
            ['name' => 'Vadodara Warehouse', 'code' => 'WH-BDQ-03', 'city' => 'Vadodara', 'capacity' => 6000],
            ['name' => 'Surat Warehouse', 'code' => 'WH-ST-04', 'city' => 'Surat', 'capacity' => 9500],
            ['name' => 'Pune Logistics Hub', 'code' => 'WH-PN-05', 'city' => 'Pune', 'capacity' => 8000],
            ['name' => 'Bengaluru Tech Hub', 'code' => 'WH-BLR-06', 'city' => 'Bengaluru', 'capacity' => 10000],
            ['name' => 'Delhi NCR Depot', 'code' => 'WH-DEL-07', 'city' => 'Delhi NCR', 'capacity' => 14000],
            ['name' => 'Hyderabad Central', 'code' => 'WH-HYD-08', 'city' => 'Hyderabad', 'capacity' => 9000],
        ];

        $warehouses = [];
        foreach ($warehouseData as $idx => $wd) {
            $wh = Warehouse::where('code', $wd['code'])->orWhere('name', $wd['name'])->first();
            if (!$wh) {
                $wh = Warehouse::skip($idx)->first();
            }
            if ($wh) {
                $wh->update([
                    'name' => $wd['name'],
                    'code' => $wd['code'],
                    'city' => $wd['city'],
                    'state' => 'Gujarat',
                    'capacity' => $wd['capacity'],
                ]);
            } else {
                $wh = Warehouse::create([
                    'company_id' => $companyId,
                    'name' => $wd['name'],
                    'code' => $wd['code'],
                    'city' => $wd['city'],
                    'state' => 'Gujarat',
                    'capacity' => $wd['capacity'],
                    'status' => 'active',
                ]);
            }
            $warehouses[] = $wh;
        }

        // 2. Adjust Stocks to create realistic utilization
        // Vadodara ~91%, Main ~82%, Ahmedabad ~68%, Surat ~54%
        $utilizationTargets = [
            0 => 0.82, // Main: 82% of 12000 = ~9840
            1 => 0.68, // Ahmedabad: 68% of 8500 = ~5780
            2 => 0.91, // Vadodara: 91% of 6000 = ~5460 (triggers >85% warning!)
            3 => 0.54, // Surat: 54% of 9500 = ~5130
        ];

        $products = Product::all();
        $prodCount = $products->count();

        foreach ($utilizationTargets as $whIndex => $targetRatio) {
            if (!isset($warehouses[$whIndex])) continue;
            $wh = $warehouses[$whIndex];
            $targetUnits = (int) round($wh->capacity * $targetRatio);
            $currentWhUnits = (int) Stock::where('warehouse_id', $wh->id)->sum('quantity');

            if ($currentWhUnits < $targetUnits && $prodCount > 0) {
                $diff = $targetUnits - $currentWhUnits;
                $perProd = (int) ceil($diff / min($prodCount, 40));
                foreach ($products->take(40) as $p) {
                    $stock = Stock::where('product_id', $p->id)->where('warehouse_id', $wh->id)->first();
                    if (!$stock) {
                        $stock = Stock::create([
                            'company_id' => $companyId,
                            'product_id' => $p->id,
                            'warehouse_id' => $wh->id,
                            'quantity' => 0,
                            'available_quantity' => 0,
                            'reserved_quantity' => 0,
                            'average_cost' => $p->cost_price ?: 1200,
                        ]);
                    }
                    $stock->quantity += $perProd;
                    $stock->available_quantity = $stock->quantity;
                    $stock->save();
                }
            }
        }

        // 3. Seed Inventory Transactions
        if (InventoryTransaction::count() < 100) {
            $types = ['purchase_in', 'sales_out', 'stock_adjustment', 'stock_transfer_in', 'stock_transfer_out', 'opening_stock', 'purchase_return'];
            $suppliers = Supplier::all();

            for ($i = 0; $i < 120; $i++) {
                $p = $products->random();
                $wh = $warehouses[array_rand($warehouses)];
                $type = $types[array_rand($types)];
                $qty = rand(5, 120);
                if (in_array($type, ['sales_out', 'stock_transfer_out', 'purchase_return'])) {
                    $qty = -1 * $qty;
                }
                $cost = (float) ($p->purchase_price ?: ($p->cost_price ?: 1500));
                $date = Carbon::now()->subDays(rand(0, 90))->subHours(rand(1, 23));

                InventoryTransaction::create([
                    'company_id' => $companyId,
                    'product_id' => $p->id,
                    'warehouse_id' => $wh->id,
                    'type' => $type,
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'total_cost' => abs($qty) * $cost,
                    'reference_type' => 'StockOperation',
                    'reference' => 'TXN-' . strtoupper(substr(md5(uniqid()), 0, 8)),
                    'performed_by' => $adminId,
                    'notes' => 'Automated inventory transaction for ' . $p->name,
                    'created_at' => $date,
                    'updated_at' => $date,
                ]);
            }
        }

        // 4. Seed Inventory Alerts
        InventoryAlert::truncate();
        $alertConfigs = [
            ['type' => 'low_stock', 'severity' => 'warning', 'title' => 'Low Stock Warning', 'msg' => 'Current inventory is below safety threshold'],
            ['type' => 'out_of_stock', 'severity' => 'critical', 'title' => 'Stockout Alert', 'msg' => 'Product is completely depleted across hubs'],
            ['type' => 'capacity_warning', 'severity' => 'critical', 'title' => 'Warehouse Capacity Warning', 'msg' => 'Vadodara Warehouse has reached 91% capacity (>85% critical threshold)'],
            ['type' => 'dead_stock', 'severity' => 'warning', 'title' => 'Dead Stock Detected', 'msg' => 'No recorded sales transactions in the past 60 days'],
            ['type' => 'pending_purchase', 'severity' => 'info', 'title' => 'Pending Reorder', 'msg' => '4 Purchase orders awaiting vendor confirmation'],
        ];

        foreach ($products->take(15) as $idx => $p) {
            $cfg = $alertConfigs[$idx % count($alertConfigs)];
            $wh = $warehouses[$idx % count($warehouses)];
            InventoryAlert::create([
                'company_id' => $companyId,
                'product_id' => $p->id,
                'warehouse_id' => $wh->id,
                'type' => $cfg['type'],
                'severity' => $cfg['severity'],
                'title' => $p->name . ' - ' . $cfg['title'],
                'message' => $cfg['msg'] . ' at ' . $wh->name,
                'is_resolved' => $idx > 10,
                'created_at' => Carbon::now()->subHours(rand(1, 48)),
                'updated_at' => Carbon::now()->subHours(rand(1, 48)),
            ]);
        }

        // 5. Seed Stock Forecasts
        StockForecast::truncate();
        $suppliers = Supplier::all();
        foreach ($products->take(25) as $p) {
            $stock = (int) Stock::where('product_id', $p->id)->sum('quantity');
            $dailySales = round(rand(2, 25) + rand(1, 9) / 10, 2);
            $daysRemaining = $dailySales > 0 ? round($stock / $dailySales, 1) : 45.0;
            $predictedStockout = Carbon::now()->addDays((int) ceil($daysRemaining));
            $recommendedOrder = max(50, (int) round($dailySales * 30));
            $sup = $suppliers->isNotEmpty() ? $suppliers->random() : null;
            $unitCost = (float) ($p->purchase_price ?: ($p->cost_price ?: 1200));

            StockForecast::create([
                'company_id' => $companyId,
                'product_id' => $p->id,
                'warehouse_id' => $warehouses[0]->id,
                'current_stock' => $stock,
                'average_daily_sales' => $dailySales,
                'days_remaining' => $daysRemaining,
                'predicted_stockout_date' => $predictedStockout,
                'recommended_order_quantity' => $recommendedOrder,
                'supplier_id' => $sup?->id,
                'estimated_cost' => $recommendedOrder * $unitCost,
                'confidence_score' => rand(88, 98),
            ]);
        }

        // 6. Seed Inventory Snapshots for Past 12 Months
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->copy()->startOfMonth()->subMonths($i)->endOfMonth();
            $baseVal = 3200000 + (11 - $i) * 180000;
            InventorySnapshot::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'snapshot_date' => $date->toDateString(),
                ],
                [
                    'total_products' => $prodCount,
                    'total_units' => 22000 + (11 - $i) * 500,
                    'total_stock_value' => $baseVal,
                    'total_purchase_value' => round($baseVal * 0.72, 2),
                    'total_sales_value' => round($baseVal * 1.28, 2),
                    'low_stock_count' => rand(30, 48),
                    'out_of_stock_count' => rand(12, 22),
                    'warehouse_utilization' => round(72.0 + ($i % 5) * 1.5, 1),
                ]
            );
        }
    }
}

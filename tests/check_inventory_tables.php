<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Supplier;
use App\Models\Stock;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Unit;

echo "Products: " . Product::count() . "\n";
echo "Categories: " . Category::count() . "\n";
echo "Brands: " . Brand::count() . "\n";
echo "Units: " . Unit::count() . "\n";
echo "Warehouses: " . Warehouse::count() . "\n";
echo "Suppliers: " . Supplier::count() . "\n";
echo "Stocks: " . Stock::count() . "\n";
echo "POs: " . PurchaseOrder::count() . "\n";
echo "SOs: " . SalesOrder::count() . "\n";

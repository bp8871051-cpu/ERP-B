<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\Supplier;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class InventoryModuleTest extends TestCase
{
    protected User $user;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::first() ?? User::factory()->create();
        $this->company = Company::first() ?? Company::create([
            'name' => 'Falcon Technologies Inc.',
            'code' => 'FTI',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->user);
    }

    public function test_dashboard_api_returns_expected_kpi_and_charts(): void
    {
        $response = $this->getJson('/api/inventory/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'metrics' => [
                        'totalProducts',
                        'totalStockQuantity',
                        'totalStockValue',
                        'lowStockCount',
                        'outOfStockCount',
                        'warehousesCount',
                        'suppliersCount',
                        'turnoverRate',
                    ],
                    'stockValueTrend',
                    'stockMovementTrend',
                    'inventoryByCategory',
                    'warehouseDistribution',
                    'lowStockOverview',
                    'purchaseVsUsage',
                    'lowStockProducts',
                    'recentMovements',
                    'recentAdjustments',
                    'recentTransfers',
                ],
            ]);
    }

    public function test_product_crud_and_duplicate(): void
    {
        $category = Category::first();
        $brand = Brand::first();
        $unit = Unit::first();
        $warehouse = Warehouse::first();

        // 1. Create Product
        $sku = 'TEST-SKU-' . rand(1000, 9999);
        $createData = [
            'name' => 'High Performance Test Product',
            'sku' => $sku,
            'barcode' => 'BC' . rand(100000, 999999),
            'category_id' => $category?->id,
            'brand_id' => $brand?->id,
            'unit_id' => $unit?->id,
            'purchase_price' => 1500,
            'selling_price' => 2200,
            'mrp' => 2500,
            'minimum_stock' => 10,
            'reorder_level' => 15,
            'maximum_stock' => 100,
            'opening_stock' => 20,
            'warehouse_id' => $warehouse?->id,
            'status' => 'active',
        ];

        $res = $this->postJson('/api/inventory/products', $createData);
        $res->assertStatus(201);
        $productId = $res->json('data.id');

        // 2. Fetch Single Product
        $getRes = $this->getJson("/api/inventory/products/{$productId}");
        $getRes->assertStatus(200)
            ->assertJsonPath('data.sku', $sku);

        // 3. Update Product
        $updateRes = $this->putJson("/api/inventory/products/{$productId}", [
            'name' => 'Updated Product Name Pro',
            'selling_price' => 2400,
        ]);
        $updateRes->assertStatus(200);

        // 4. Duplicate Product
        $dupRes = $this->postJson("/api/inventory/products/{$productId}/duplicate");
        $dupRes->assertStatus(201);
        $dupId = $dupRes->json('data.id');

        // 5. Delete duplicated product
        $delRes = $this->deleteJson("/api/inventory/products/{$dupId}");
        $delRes->assertStatus(200);
    }

    public function test_category_crud_and_hierarchy(): void
    {
        $parentRes = $this->postJson('/api/inventory/categories', [
            'name' => 'Parent Category Test',
            'description' => 'Main group',
            'status' => 'active',
        ]);
        $parentRes->assertStatus(201);
        $parentId = $parentRes->json('data.id');

        // Child Category
        $childRes = $this->postJson('/api/inventory/categories', [
            'name' => 'Child Category Test',
            'parent_id' => $parentId,
            'status' => 'active',
        ]);
        $childRes->assertStatus(201);
        $childId = $childRes->json('data.id');

        $this->assertEquals($parentId, $childRes->json('data.parent_id'));

        // Delete Child
        $this->deleteJson("/api/inventory/categories/{$childId}")->assertStatus(200);
        // Delete Parent
        $this->deleteJson("/api/inventory/categories/{$parentId}")->assertStatus(200);
    }

    public function test_brand_and_unit_crud(): void
    {
        // Brand
        $brandRes = $this->postJson('/api/inventory/brands', [
            'name' => 'Brand Test ' . rand(100, 999),
            'website' => 'https://example.com',
            'status' => 'active',
        ]);
        $brandRes->assertStatus(201);
        $brandId = $brandRes->json('data.id');
        $this->deleteJson("/api/inventory/brands/{$brandId}")->assertStatus(200);

        // Unit
        $unitRes = $this->postJson('/api/inventory/units', [
            'name' => 'Milliliter Test',
            'short_code' => 'ml' . rand(10, 99),
            'unit_type' => 'volume',
            'conversion_factor' => 0.001,
            'status' => 'active',
        ]);
        $unitRes->assertStatus(201);
        $unitId = $unitRes->json('data.id');
        $this->deleteJson("/api/inventory/units/{$unitId}")->assertStatus(200);
    }

    public function test_stock_receive_issue_and_negative_prevention(): void
    {
        $stockService = app(StockService::class);
        $product = Product::first();
        $warehouse = Warehouse::first();

        $this->assertNotNull($product);
        $this->assertNotNull($warehouse);

        // Stock Receive
        $receiveRes = $this->postJson('/api/inventory/stock/receive', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 15,
            'unit_cost' => 120,
            'notes' => 'Shipment test receive',
        ]);
        $receiveRes->assertStatus(200);

        $stock = Stock::where('product_id', $product->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();
        $this->assertGreaterThanOrEqual(15, $stock->available_quantity);

        // Issue valid quantity
        $issueRes = $this->postJson('/api/inventory/stock/issue', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 5,
            'notes' => 'Customer dispatch test issue',
        ]);
        $issueRes->assertStatus(200);

        // Negative stock prevention: attempt to issue more than available
        $excessiveQty = $stock->available_quantity + 999999;
        $failedIssueRes = $this->postJson('/api/inventory/stock/issue', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => $excessiveQty,
            'notes' => 'Should fail negative prevention',
        ]);
        $failedIssueRes->assertStatus(422);
    }

    public function test_stock_adjustment_creation_and_approval(): void
    {
        $product = Product::first();
        $warehouse = Warehouse::first();

        // Create adjustment
        $adjRes = $this->postJson('/api/inventory/stock/adjustments', [
            'warehouse_id' => $warehouse->id,
            'reason' => 'Annual physical count variance',
            'notes' => 'Found damaged items in aisle 4',
            'status' => 'draft',
            'items' => [
                [
                    'product_id' => $product->id,
                    'system_quantity' => 50,
                    'actual_quantity' => 45,
                    'difference' => -5,
                    'unit_cost' => 100,
                    'notes' => 'Damaged during forklift transit',
                ],
            ],
        ]);

        $adjRes->assertStatus(201);
        $adjId = $adjRes->json('data.id');

        // Submit adjustment
        $submitRes = $this->postJson("/api/inventory/stock/adjustments/{$adjId}/submit");
        $submitRes->assertStatus(200);

        // Approve adjustment (must trigger stock ledger movements)
        $approveRes = $this->postJson("/api/inventory/stock/adjustments/{$adjId}/approve");
        $approveRes->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        // Verify movement created
        $refNum = $adjRes->json('data.reference_number');
        $movementExists = StockMovement::where('movement_type', 'adjustment')
            ->where('reference', $refNum)
            ->exists();
        $this->assertTrue($movementExists);
    }

    public function test_stock_transfer_workflow_and_validation(): void
    {
        $product = Product::first();
        $warehouses = Warehouse::take(2)->get();
        if ($warehouses->count() < 2) {
            $this->markTestSkipped('Need at least 2 warehouses for transfer test.');
        }

        $wA = $warehouses[0];
        $wB = $warehouses[1];

        // Ensure warehouse A has stock
        $this->postJson('/api/inventory/stock/receive', [
            'product_id' => $product->id,
            'warehouse_id' => $wA->id,
            'quantity' => 25,
            'unit_cost' => 100,
            'notes' => 'Transfer stock pre-fill',
        ]);

        // Validation Rule: Source warehouse != Destination warehouse
        $sameWhRes = $this->postJson('/api/inventory/stock/transfers', [
            'from_warehouse_id' => $wA->id,
            'to_warehouse_id' => $wA->id,
            'reason' => 'Invalid same warehouse transfer',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]);
        $sameWhRes->assertStatus(422);

        // Create valid transfer
        $transferRes = $this->postJson('/api/inventory/stock/transfers', [
            'from_warehouse_id' => $wA->id,
            'to_warehouse_id' => $wB->id,
            'reason' => 'Inter-branch rebalancing',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10],
            ],
        ]);
        $transferRes->assertStatus(201);
        $transferId = $transferRes->json('data.id');
        $transferNumber = $transferRes->json('data.transfer_number');

        // Approve -> Dispatch -> Receive workflow
        $this->postJson("/api/inventory/stock/transfers/{$transferId}/approve")->assertStatus(200);
        $this->postJson("/api/inventory/stock/transfers/{$transferId}/dispatch")->assertStatus(200);
        $receiveTransferRes = $this->postJson("/api/inventory/stock/transfers/{$transferId}/receive");
        $receiveTransferRes->assertStatus(200)
            ->assertJsonPath('data.status', 'received');

        // Verify IN movement exists at destination warehouse
        $inMovement = StockMovement::where('movement_type', 'transfer_in')
            ->where('warehouse_id', $wB->id)
            ->where('reference', $transferNumber)
            ->exists();
        $this->assertTrue($inMovement);
    }
}

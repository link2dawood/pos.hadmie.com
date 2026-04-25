<?php

namespace Tests\Unit;

use App\Http\Controllers\SellPosController;
use App\Product;
use App\ProductVariation;
use App\Support\Prints\PosPrintRenderer;
use App\Utils\BusinessUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use ReflectionMethod;
use Tests\TestCase;

class PosScanLookupHelpersTest extends TestCase
{
    public function test_it_normalizes_scanned_lookup_values(): void
    {
        $controller = $this->makeController();

        $normalized = $this->invokePrivateMethod($controller, 'normalizeScannedLookupValue', [
            "  ab c\t123 \n",
        ]);

        $this->assertSame('ABC123', $normalized);
    }

    public function test_it_resolves_single_variation_product_level_scans(): void
    {
        $controller = $this->makeController();

        $product = new Product([
            'name' => 'Coffee Beans',
            'type' => 'single',
        ]);

        $variation = new Variation([
            'id' => 12,
            'name' => 'DUMMY',
        ]);
        $variation->setRelation('product_variation', new ProductVariation([
            'name' => 'Regular',
        ]));
        $product->setRelation('variations', collect([$variation]));

        $resolved = $this->invokePrivateMethod($controller, 'resolveProductScanMatch', [$product, 'barcode']);

        $this->assertTrue($resolved['success']);
        $this->assertSame(12, $resolved['variation_id']);
        $this->assertSame('barcode', $resolved['match_type']);
        $this->assertSame('Coffee Beans', $resolved['product_name']);
    }

    public function test_it_rejects_multi_variation_product_level_scans(): void
    {
        $controller = $this->makeController();

        $product = new Product([
            'name' => 'T-Shirt',
            'type' => 'variable',
        ]);

        $small = new Variation(['id' => 21, 'name' => 'Small']);
        $small->setRelation('product_variation', new ProductVariation(['name' => 'Size']));

        $large = new Variation(['id' => 22, 'name' => 'Large']);
        $large->setRelation('product_variation', new ProductVariation(['name' => 'Size']));

        $product->setRelation('variations', collect([$small, $large]));

        $resolved = $this->invokePrivateMethod($controller, 'resolveProductScanMatch', [$product, 'qr']);

        $this->assertFalse($resolved['success']);
        $this->assertSame(
            'This code matches a product with multiple variations. Scan the variation SKU instead.',
            $resolved['msg']
        );
    }

    private function makeController(): SellPosController
    {
        return new SellPosController(
            $this->createMock(ContactUtil::class),
            $this->createMock(ProductUtil::class),
            $this->createMock(BusinessUtil::class),
            $this->createMock(TransactionUtil::class),
            $this->createMock(CashRegisterUtil::class),
            $this->createMock(ModuleUtil::class),
            $this->createMock(NotificationUtil::class),
            $this->createMock(PosPrintRenderer::class)
        );
    }

    private function invokePrivateMethod(object $instance, string $method, array $arguments = [])
    {
        $reflection = new ReflectionMethod($instance, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($instance, $arguments);
    }
}

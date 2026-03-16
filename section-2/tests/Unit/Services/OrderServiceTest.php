<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderNotification;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderService();
        Notification::fake();
    }

    public function test_creates_order_successfully()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $result = $this->service->create([
            'user_id' => $user->id,
            'total_amount' => 1000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'price' => 50],
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['order']);
        $this->assertDatabaseHas('orders', [
            'id' => $result['order']->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $result['order']->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        Notification::assertSentTo($user, OrderNotification::class);
    }

    public function test_fails_when_items_empty()
    {
        $result = $this->service->create([
            'user_id' => 1,
            'total_amount' => 50,
            'items' => [],
        ]);

        $this->assertFalse($result['success']);
        $this->assertNull($result['order']);
        $this->assertEquals('Order must have at least one item', $result['message']);
    }

    public function test_fails_when_items_missing()
    {
        $result = $this->service->create([
            'user_id' => 1,
            'total_amount' => 50,
        ]);

        $this->assertFalse($result['success']);
        $this->assertNull($result['order']);
    }

    public function test_generates_unique_order_number()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $result1 = $this->service->create([
            'user_id' => $user->id,
            'total_amount' => 10,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 10]],
        ]);

        // Same product, different order
        $result2 = $this->service->create([
            'user_id' => $user->id,
            'total_amount' => 20,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 20]],
        ]);

        $this->assertNotEquals(
            $result1['order']->order_number,
            $result2['order']->order_number
        );
        $this->assertStringStartsWith('ORD-', $result1['order']->order_number);
    }

    public function test_rolls_back_on_exception()
    {
        $user = User::factory()->create();

        // Create new order using invalid product_id to triggers rollback
        $result = $this->service->create([
            'user_id' => $user->id,
            'total_amount' => 10,
            'items' => [
                ['product_id' => 99999, 'quantity' => 1, 'price' => 10],
            ],
        ]);

        $this->assertFalse($result['success']);
        $this->assertDatabaseCount('orders', 0);
    }
}
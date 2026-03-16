<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderNotification;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
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
        Log::info('TEST START: test_creates_order_successfully');
        
        $user = User::factory()->create();
        Log::info('Created test user', ['user_id' => $user->id]);
        
        $product = Product::factory()->create();
        Log::info('Created test product', ['product_id' => $product->id, 'price' => $product->price ?? 'N/A']);

        $orderData = [
            'user_id' => $user->id,
            'total_amount' => 1000,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2, 'price' => 50],
            ],
        ];
        Log::info('Calling OrderService::create', $orderData);

        $result = $this->service->create($orderData);

        Log::info('OrderService::create result', [
            'success' => $result['success'],
            'order_id' => $result['order']->id ?? null,
            'order_number' => $result['order']->order_number ?? null,
        ]);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['order']);
        
        Log::info('Asserting database has order', ['order_id' => $result['order']->id]);
        $this->assertDatabaseHas('orders', [
            'id' => $result['order']->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
        ]);
        
        Log::info('Asserting database has order items');
        $this->assertDatabaseHas('order_items', [
            'order_id' => $result['order']->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        Log::info('Asserting notification was sent');
        Notification::assertSentTo($user, OrderNotification::class);
        
        Log::info('TEST PASSED: test_creates_order_successfully');
    }

    public function test_fails_when_items_empty()
    {
        Log::info('TEST START: test_fails_when_items_empty');

        $result = $this->service->create([
            'user_id' => 1,
            'total_amount' => 50,
            'items' => [],
        ]);

        Log::info('Result with empty items', [
            'success' => $result['success'],
            'message' => $result['message'] ?? 'No message',
            'order' => $result['order'] ?? null,
        ]);

        $this->assertFalse($result['success']);
        $this->assertNull($result['order']);
        $this->assertEquals('Order must have at least one item', $result['message']);
        
        Log::info('TEST PASSED: test_fails_when_items_empty');
    }

    public function test_fails_when_items_missing()
    {
        Log::info('TEST START: test_fails_when_items_missing');

        $result = $this->service->create([
            'user_id' => 1,
            'total_amount' => 50,
        ]);

        Log::info('Result with missing items', [
            'success' => $result['success'],
            'order' => $result['order'] ?? null,
        ]);

        $this->assertFalse($result['success']);
        $this->assertNull($result['order']);
        
        Log::info('TEST PASSED: test_fails_when_items_missing');
    }

    public function test_generates_unique_order_number()
    {
        Log::info('TEST START: test_generates_unique_order_number');
        
        $user = User::factory()->create();
        $product = Product::factory()->create();
        Log::info('Created user and product', ['user_id' => $user->id, 'product_id' => $product->id]);

        $result1 = $this->service->create([
            'user_id' => $user->id,
            'total_amount' => 10,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 10]],
        ]);
        Log::info('First order created', [
            'order_id' => $result1['order']->id,
            'order_number' => $result1['order']->order_number,
        ]);

        // Same product, different order
        $result2 = $this->service->create([
            'user_id' => $user->id,
            'total_amount' => 20,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'price' => 20]],
        ]);
        Log::info('Second order created', [
            'order_id' => $result2['order']->id,
            'order_number' => $result2['order']->order_number,
        ]);

        Log::info('Comparing order numbers', [
            'order_number_1' => $result1['order']->order_number,
            'order_number_2' => $result2['order']->order_number,
            'are_different' => $result1['order']->order_number !== $result2['order']->order_number,
        ]);

        $this->assertNotEquals(
            $result1['order']->order_number,
            $result2['order']->order_number
        );
        $this->assertStringStartsWith('ORD-', $result1['order']->order_number);
        
        Log::info('TEST PASSED: test_generates_unique_order_number');
    }

    public function test_rolls_back_on_exception()
    {
        Log::info('TEST START: test_rolls_back_on_exception');
        
        $user = User::factory()->create();
        Log::info('Created test user', ['user_id' => $user->id]);

        $orderData = [
            'user_id' => $user->id,
            'total_amount' => 10,
            'items' => [
                ['product_id' => 99999, 'quantity' => 1, 'price' => 10],
            ],
        ];
        Log::info('Calling OrderService::create with invalid product_id', $orderData);

        // Create new order using invalid product_id to triggers rollback
        $result = $this->service->create($orderData);

        Log::info('Result after rollback attempt', [
            'success' => $result['success'],
            'order' => $result['order'] ?? null,
            'message' => $result['message'] ?? 'No message',
        ]);

        $orderCount = \DB::table('orders')->count();
        Log::info('Verifying rollback - orders count', ['count' => $orderCount]);

        $this->assertFalse($result['success']);
        $this->assertDatabaseCount('orders', 0);
        
        Log::info('TEST PASSED: test_rolls_back_on_exception');
    }
}
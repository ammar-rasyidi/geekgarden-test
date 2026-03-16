<?php

namespace App\Services;

use App\Models\Order;
use App\Notifications\OrderNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Create a new order with items.
     *
     * @param array $data
     * @return array
     */
    public function create(array $data): array
    {
        if (empty($data['items'])) {
            return [
                'success' => false,
                'message' => 'Order must have at least one item',
                'order' => null,
            ];
        }

        try {
            // need to use transaction so if any step fails (order creation, items insert, or notification), 
            // we roll back everything and no partial orders left in DB.
            $order = DB::transaction(function () use ($data) {
                $order = Order::create([
                    'user_id' => $data['user_id'],
                    'order_number' => $this->generateOrderNumber(),
                    'total_amount' => $data['total_amount'],
                    'status' => 'pending'
                ]);

                foreach ($data['items'] as $item) {
                    $order->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                    ]);
                }

                $order->load('items');

                $order->user?->notify(new OrderNotification($order));

                return $order;
            });

            return [
                'success' => true,
                'message' => 'Order created successfully',
                'order' => $order,
            ];

        } catch (\Exception $e) {
            Log::error('Order creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $data['user_id'] ?? null,
                'items_count' => count($data['items'] ?? []),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create order. Please try again.',
                'order' => null,
            ];
        }
    }

    /**
     * Generate unique order number.
     *
     * @return string
     */
    private function generateOrderNumber(): string
    {
        return 'ORD-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
    }
}
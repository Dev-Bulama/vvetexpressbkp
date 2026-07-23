<?php

namespace Webkul\Notification\Listeners;

use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Notification\Events\CreateOrderNotification;
use Webkul\Notification\Events\UpdateOrderNotification;
use Webkul\Notification\Repositories\NotificationRepository;

class Order
{
    /**
     * Create a new listener instance.
     *
     * @return void
     */
    public function __construct(protected NotificationRepository $notificationRepository) {}

    /**
     * Create a new resource.
     *
     * @return void
     */
    public function createOrder($order)
    {
        $this->notificationRepository->create(['type' => 'order', 'order_id' => $order->id]);

        // With QUEUE_CONNECTION=sync, ShouldBroadcast events hit the
        // broadcaster (Reverb) inline. This fires from inside
        // checkout.order.save.after, still inside the order-creation
        // transaction's retry loop - a broadcaster outage must never turn
        // into a failed order, it should just mean the admin's real-time
        // notification bell misses one update.
        try {
            event(new CreateOrderNotification);
        } catch (Throwable $e) {
            Log::warning('Failed to broadcast order-created notification.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fire an Event when the order status is updated.
     *
     * @return void
     */
    public function updateOrder($order)
    {
        try {
            event(new UpdateOrderNotification([
                'id' => $order->id,
                'status' => $order->status,
            ]));
        } catch (Throwable $e) {
            Log::warning('Failed to broadcast order-updated notification.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

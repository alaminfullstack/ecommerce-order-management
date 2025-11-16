<?php

namespace App\Listeners;

use App\Jobs\SendOrderEmailJob;
use App\Events\OrderStatusChanged;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderStatusNotification implements ShouldQueue
{
    use InteractsWithQueue;
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        // Dispatch email job when order status changes
        if ($event->order->wasChanged('status')) {
            dispatch(new SendOrderEmailJob($event->order, $event->order->status));
        }
    }
}

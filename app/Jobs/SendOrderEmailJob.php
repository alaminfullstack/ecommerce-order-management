<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderEmailJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $order;
    public $type;

    public function __construct(Order $order, string $type)
    {
        $this->order = $order;
        $this->type = $type;
    }

    public function handle(): void
    {
        // Log email sending (in production, implement actual email sending)
        Log::info("Order {$this->type} email sent", [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'customer_email' => $this->order->customer->email,
            'type' => $this->type,
        ]);

        // In production, implement actual email sending:
        // Mail::to($this->order->customer->email)->send(new OrderEmail($this->order, $this->type));
    }
}

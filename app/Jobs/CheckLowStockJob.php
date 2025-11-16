<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;

class CheckLowStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $product;

    public function __construct(
         Product $product
    ) {
        $this->product = $product;
    }

    public function handle(): void
    {
        if ($this->product->is_low_stock) {
            Log::warning("Low stock alert", [
                'product_id' => $this->product->id,
                'product_name' => $this->product->name,
                'current_stock' => $this->product->stock_quantity,
                'threshold' => $this->product->low_stock_threshold,
                'vendor_email' => $this->product->vendor->email,
            ]);

            // In production, send email to vendor:
            // Mail::to($this->product->vendor->email)->send(new LowStockAlert($this->product));
        }
    }
}

<?php

namespace App\Jobs;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateInvoiceJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $order;

    public function __construct(
         Order $order
    ) {
        $this->order = $order;
    }

    public function handle(): void
    {
        $order = $this->order->load(['items.product', 'customer']);

        $pdf = Pdf::loadView('invoices.order', ['order' => $order]);

        $filename = "invoice-{$order->order_number}.pdf";
        Storage::put("invoices/{$filename}", $pdf->output());

        // Optionally update order with invoice path
        $order->update(['invoice_path' => "invoices/{$filename}"]);
    }
}

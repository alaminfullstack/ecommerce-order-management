<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        .invoice-details { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .total { font-weight: bold; font-size: 18px; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>INVOICE</h1>
        <p>Order #{{ $order->order_number }}</p>
    </div>

    <div class="invoice-details">
        <p><strong>Customer:</strong> {{ $order->customer->name }}</p>
        <p><strong>Email:</strong> {{ $order->customer->email }}</p>
        <p><strong>Order Date:</strong> {{ $order->created_at->format('Y-m-d H:i:s') }}</p>
        <p><strong>Status:</strong> {{ strtoupper($order->status) }}</p>
    </div>

    <h3>Items</h3>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th>Price</th>
                <th>Quantity</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->product_sku }}</td>
                <td>${{ number_format($item->price, 2) }}</td>
                <td>{{ $item->quantity }}</td>
                <td class="text-right">${{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 30px; text-align: right;">
        <p>Subtotal: ${{ number_format($order->subtotal, 2) }}</p>
        <p>Tax: ${{ number_format($order->tax, 2) }}</p>
        <p>Shipping: ${{ number_format($order->shipping, 2) }}</p>
        <p class="total">Total: ${{ number_format($order->total, 2) }}</p>
    </div>

    <div style="margin-top: 40px;">
        <h4>Shipping Address</h4>
        <p>{{ $order->shipping_address }}</p>
    </div>
</body>
</html>

@php use App\Support\Money; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow">
    <title>Packing slip {{ $order->reference }}</title>
    <style>
        @page { size: A4; margin: 16mm; }
        * { box-sizing: border-box; }
        body { font-family: Georgia, 'Times New Roman', serif; color: #232B22; margin: 0; padding: 24px; font-size: 13px; line-height: 1.5; }
        header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid #232B22; padding-bottom: 12px; margin-bottom: 20px; }
        h1 { font-size: 22px; font-weight: normal; margin: 0; letter-spacing: .08em; }
        h2 { font-size: 13px; font-family: system-ui, sans-serif; font-weight: 600; margin: 0 0 6px; }
        .meta { text-align: right; font-family: system-ui, sans-serif; }
        .meta strong { font-size: 16px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
        table { width: 100%; border-collapse: collapse; font-family: system-ui, sans-serif; }
        th, td { text-align: left; padding: 8px 6px; border-bottom: 1px solid #DDD6CA; vertical-align: top; }
        th { font-size: 11px; color: #615F57; font-weight: 600; }
        td.num, th.num { text-align: right; white-space: nowrap; }
        .check { width: 28px; }
        .box { display: inline-block; width: 14px; height: 14px; border: 1px solid #232B22; }
        tfoot td { border: 0; padding: 4px 6px; }
        .due { font-size: 18px; font-weight: 700; }
        .cod { margin-top: 20px; padding: 12px; border: 2px solid #232B22; font-family: system-ui, sans-serif; font-size: 14px; }
        .notes { margin-top: 12px; padding: 10px; background: #F4F2EC; font-family: system-ui, sans-serif; }
        footer { margin-top: 32px; font-family: system-ui, sans-serif; font-size: 11px; color: #615F57; }
        .actions { margin-bottom: 16px; font-family: system-ui, sans-serif; }
        @media print { .actions { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" id="print">Print</button>
    </div>
    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        document.getElementById('print').addEventListener('click', () => window.print());
    </script>

    <header>
        <div>
            <h1>{{ mb_strtoupper($store['name']) }}</h1>
            <div style="font-family:system-ui,sans-serif;font-size:12px">{{ $contact['email'] }} · {{ $contact['phone'] }}</div>
        </div>
        <div class="meta">
            <div>Order</div>
            <strong>{{ $order->reference }}</strong>
            <div>{{ $placedAt->format('j M Y, H:i') }}</div>
        </div>
    </header>

    <div class="grid">
        <section>
            <h2>Deliver to</h2>
            <div><strong>{{ $order->customer_name }}</strong></div>
            <div>{{ $phone }}</div>
            <div>{{ $order->address }}</div>
            <div>{{ $order->city }} — {{ $order->shipping_zone_name }}</div>
        </section>
        <section>
            <h2>Courier</h2>
            <div>{{ $order->courier_name ?: '—' }}</div>
            <div>{{ $order->tracking_number ? 'Tracking: '.$order->tracking_number : '' }}</div>
        </section>
    </div>

    <table>
        <thead>
            <tr>
                <th class="check">Packed</th>
                <th>Item</th>
                <th>SKU</th>
                <th class="num">Qty</th>
                <th class="num">Unit</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td class="check"><span class="box"></span></td>
                    <td>{{ $item->product_name }}@if ($item->variant_name) — {{ $item->variant_name }}@endif</td>
                    <td>{{ $item->sku ?: '—' }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ Money::format($item->unit_price_minor, $order->currency) }}</td>
                    <td class="num">{{ Money::format($item->line_total_minor, $order->currency) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="5" class="num">Subtotal</td><td class="num">{{ Money::format($order->subtotal_minor, $order->currency) }}</td></tr>
            @if ($order->discount_minor > 0)
                <tr><td colspan="5" class="num">Discount {{ $order->coupon_code ? '('.$order->coupon_code.')' : '' }}</td><td class="num">−{{ Money::format($order->discount_minor, $order->currency) }}</td></tr>
            @endif
            <tr><td colspan="5" class="num">Delivery</td><td class="num">{{ $order->shipping_minor > 0 ? Money::format($order->shipping_minor, $order->currency) : 'Free' }}</td></tr>
            <tr><td colspan="5" class="num due">To collect</td><td class="num due">{{ Money::format($order->total_minor, $order->currency) }}</td></tr>
        </tfoot>
    </table>

    <div class="cod">
        <strong>Cash on delivery.</strong> Collect {{ Money::format($order->total_minor, $order->currency) }} from the customer on delivery.
    </div>

    @if ($order->delivery_notes)
        <div class="notes"><strong>Delivery notes:</strong> {{ $order->delivery_notes }}</div>
    @endif

    <footer>{{ $store['name'] }} — {{ $store['tagline'] }}</footer>
</body>
</html>

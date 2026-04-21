<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 20px;
            color: #000;
            margin: 0;
            padding: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 20px;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            max-height: 80px;
            width: auto;
        }
        .invoice-title {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
            text-transform: uppercase;
        }
        .info-section {
            margin-bottom: 15px;
        }
        .total-section {
            margin-top: 20px;
            text-align: right;
        }
        .total-row {
            font-weight: bold;
            font-size: 22px;
        }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($receipt_details->logo))
            <img src="{{ asset($receipt_details->logo) }}" alt="Logo" class="logo">
        @endif
        <div class="invoice-title">INVOICE</div>
        <div style="font-size: 20px; margin-top: 10px;">
            @if(!empty($receipt_details->business_name))
                {{ $receipt_details->business_name }}
            @endif
            @if(!empty($receipt_details->location_name))
                <br>{{ $receipt_details->location_name }}
            @endif
            @if(!empty($receipt_details->city))
                {{ $receipt_details->city }},
            @endif
            @if(!empty($receipt_details->state))
                {{ $receipt_details->state }},
            @endif
            @if(!empty($receipt_details->country))
                {{ $receipt_details->country }}
            @endif
            @if(!empty($receipt_details->mobile))
                <br>Tel: {{ $receipt_details->mobile }}
            @endif
            @if(!empty($receipt_details->email))
                | Email: {{ $receipt_details->email }}
            @endif
        </div>
    </div>

    <div class="info-section">
        <table style="border: none;">
            <tr style="border: none;">
                <td style="border: none; width: 50%;">
                    <strong>Invoice No:</strong> {{ $receipt_details->invoice_no }}<br>
                    <strong>Date:</strong> {{ $receipt_details->invoice_date }}
                    @if(!empty($receipt_details->tax_number))
                        <br><strong>Tax No:</strong> {{ $receipt_details->tax_number }}
                    @endif
                </td>
                <td style="border: none; width: 50%;">
                    <strong>Customer:</strong><br>
                    {{ $receipt_details->customer_name ?? 'Walk-in Customer' }}<br>
                    @if(!empty($receipt_details->customer_address))
                        {{ $receipt_details->customer_address }}<br>
                    @endif
                    @if(!empty($receipt_details->customer_mobile))
                        Tel: {{ $receipt_details->customer_mobile }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 40%;">Item</th>
                <th style="width: 15%; text-align: center;">Qty</th>
                <th style="width: 20%; text-align: right;">Unit Price</th>
                <th style="width: 25%; text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($receipt_details->lines as $line)
            <tr>
                <td>
                    {{ $line['name'] }}
                    @if(!empty($line['product_variation']))
                        , {{ $line['product_variation'] }}
                    @endif
                    @if(!empty($line['variation']))
                        , {{ $line['variation'] }}
                    @endif
                    @if(!empty($line['sub_sku']))
                        <br><small>SKU: {{ $line['sub_sku'] }}</small>
                    @endif
                </td>
                <td style="text-align: center;">{{ $line['quantity'] }} {{ $line['unit'] ?? 'Pc(s)' }}</td>
                <td style="text-align: right;">{{ $line['unit_price_before_discount'] }}</td>
                <td style="text-align: right;">{{ $line['line_total'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <table style="border: none; width: 40%; margin-left: auto;">
            <tr style="border: none;">
                <td style="border: none; text-align: right; padding: 5px;">Sub Total:</td>
                <td style="border: none; text-align: right; padding: 5px;">{{ $receipt_details->subtotal ?? $receipt_details->subtotal_exc_tax }}</td>
            </tr>
            @if(!empty($receipt_details->taxes))
                @foreach($receipt_details->taxes as $k => $v)
                <tr style="border: none;">
                    <td style="border: none; text-align: right; padding: 5px;">{{ $k }}:</td>
                    <td style="border: none; text-align: right; padding: 5px;">{{ $v }}</td>
                </tr>
                @endforeach
            @elseif(!empty($receipt_details->tax))
            <tr style="border: none;">
                <td style="border: none; text-align: right; padding: 5px;">{{ $receipt_details->tax_label ?? 'Tax' }}:</td>
                <td style="border: none; text-align: right; padding: 5px;">{{ $receipt_details->tax }}</td>
            </tr>
            @endif
            @if(!empty($receipt_details->discount))
            <tr style="border: none;">
                <td style="border: none; text-align: right; padding: 5px;">{{ $receipt_details->discount_label ?? 'Discount' }}:</td>
                <td style="border: none; text-align: right; padding: 5px;">-{{ $receipt_details->discount }}</td>
            </tr>
            @endif
            <tr style="border: none;" class="total-row">
                <td style="border: none; text-align: right; padding: 8px; border-top: 2px solid #000;">Total:</td>
                <td style="border: none; text-align: right; padding: 8px; border-top: 2px solid #000;">{{ $receipt_details->total ?? $receipt_details->final_total }}</td>
            </tr>
            @if(!empty($receipt_details->total_paid))
            <tr style="border: none;">
                <td style="border: none; text-align: right; padding: 5px;">Paid:</td>
                <td style="border: none; text-align: right; padding: 5px;">{{ $receipt_details->total_paid }}</td>
            </tr>
            @endif
            @if(!empty($receipt_details->total_due))
            <tr style="border: none;" class="total-row">
                <td style="border: none; text-align: right; padding: 5px;">Due:</td>
                <td style="border: none; text-align: right; padding: 5px;">{{ $receipt_details->total_due }}</td>
            </tr>
            @endif
        </table>
    </div>

    @if(!empty($receipt_details->additional_notes))
    <div style="margin-top: 20px;">
        <strong>Notes:</strong><br>
        {!! nl2br(e($receipt_details->additional_notes)) !!}
    </div>
    @endif

    <div style="margin-top: 30px; text-align: center; font-size: 18px; color: #666;">
        <p>Thank you for your business!</p>
    </div>
</body>
</html>


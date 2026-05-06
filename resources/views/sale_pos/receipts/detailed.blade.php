<div style="width: 100%; color: #000000 !important; font-family: Arial, sans-serif;">

    <!-- Header Section with Logo -->
    <div style="margin-bottom: 30px; text-align: left;">
                    @if(!empty($receipt_details->logo))
            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                <img style="max-height: 80px; width: auto; margin-right: 15px;" src="{{ asset($receipt_details->logo) }}" alt="Company Logo">
                <div>
                    <div style="font-size: 36px; font-weight: bold; color: #e74c3c; margin-bottom: 5px;">
                        @if(!empty($receipt_details->display_name))
                            {{ $receipt_details->display_name }}
                        @else
                            {{ $receipt_details->business_name }}
                    @endif
                </div>
                </div>
            </div>
        @endif
        
        <!-- Company Contact Info -->
        <div style="font-size: 18px; color: #666; margin-bottom: 20px;">
            @if(!empty($receipt_details->location_name))
                {{ $receipt_details->location_name }}, 
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
            <br>
            @if(!empty($receipt_details->mobile))
                Telephone: {{ $receipt_details->mobile }}
                @endif
            @if(!empty($receipt_details->email))
                | Email: {{ $receipt_details->email }}
                @endif
                @if(!empty($receipt_details->website))
                | Website: {{ $receipt_details->website }}
                @endif
        </div>
    </div>

    <!-- Invoice Title -->
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="font-size: 48px; font-weight: bold; color: #000; margin: 0; text-transform: uppercase;">
            DETAILED INVOICE
        </h1>
        </div>

    <!-- Invoice Details -->
    <div style="margin-bottom: 25px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0; font-weight: bold; width: 33%; font-size: 20px;">INVOICE NUMBER</td>
                <td style="padding: 8px 0; font-weight: bold; width: 33%; font-size: 20px;">TAX No.</td>
                <td style="padding: 8px 0; font-weight: bold; width: 33%; font-size: 20px;">DATE OF ISSUE</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-size: 20px;">{{ $receipt_details->invoice_no }}</td>
                <td style="padding: 8px 0; font-size: 20px;">{{ $receipt_details->tax_number ?? 'N/A' }}</td>
                <td style="padding: 8px 0; font-size: 20px;">{{ $receipt_details->invoice_date }}</td>
            </tr>
        </table>
            </div>

    <!-- Client Information -->
    <div style="margin-bottom: 25px;">
        <div style="font-weight: bold; margin-bottom: 10px;">
            Client Name: <span style="text-transform: uppercase; font-weight: bold;">{{ $receipt_details->customer_name ?? 'Walk-in Customer' }}</span>
        </div>
        @if(!empty($receipt_details->customer_address))
            <div style="font-size: 20px; color: #666;">
                {{ $receipt_details->customer_address }}
            </div>
                @endif
        @if(!empty($receipt_details->customer_mobile))
            <div style="font-size: 20px; color: #666;">
                Mobile: {{ $receipt_details->customer_mobile }}
            </div>
                @endif
        @if(!empty($receipt_details->customer_email))
            <div style="font-size: 20px; color: #666;">
                Email: {{ $receipt_details->customer_email }}
            </div>
        @endif
    </div>

    <!-- Items Table -->
    <div style="margin-bottom: 25px;">
        <table style="width: 100%; border-collapse: collapse; border-top: 2px solid #000;">
                <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="padding: 12px 8px; text-align: left; font-weight: bold; border-bottom: 1px solid #ddd; font-size: 20px;">Item</th>
                    <th style="padding: 12px 8px; text-align: center; font-weight: bold; border-bottom: 1px solid #ddd; font-size: 20px;">Qtty</th>
                    <th style="padding: 12px 8px; text-align: right; font-weight: bold; border-bottom: 1px solid #ddd; font-size: 20px;">Unit Price</th>
                    <th style="padding: 12px 8px; text-align: right; font-weight: bold; border-bottom: 1px solid #ddd; font-size: 20px;">Discount</th>
                    <th style="padding: 12px 8px; text-align: right; font-weight: bold; border-bottom: 1px solid #ddd; font-size: 20px;">Tax</th>
                    <th style="padding: 12px 8px; text-align: right; font-weight: bold; border-bottom: 1px solid #ddd; font-size: 20px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt_details->lines as $line)
                <tr>
                    <td style="padding: 12px 8px; border-bottom: 1px solid #eee; font-size: 18px;">
                        {{ $line['name'] }}
                        @if(!empty($line['product_variation']))
                            , {{ $line['product_variation'] }}
                        @endif
                        @if(!empty($line['variation']))
                            , {{ $line['variation'] }}
                        @endif
                        @if(!empty($line['sub_sku']))
                            , {{ $line['sub_sku'] }}
                        @endif
                        @if(!empty($line['brand']))
                            , {{ $line['brand'] }}
                                @endif
                            </td>
                    <td style="padding: 12px 8px; text-align: center; border-bottom: 1px solid #eee; font-size: 18px;">
                        {{ $line['quantity'] }} {{ $line['unit'] ?? 'Pc(s)' }}
                                </td>
                    <td style="padding: 12px 8px; text-align: right; border-bottom: 1px solid #eee; font-size: 18px;">
                        {{ $line['unit_price_before_discount'] }}
                            </td>
                    <td style="padding: 12px 8px; text-align: right; border-bottom: 1px solid #eee; font-size: 18px;">
                        {{ $line['line_discount_amount'] ?? '0.00' }}
                            </td>
                    <td style="padding: 12px 8px; text-align: right; border-bottom: 1px solid #eee; font-size: 18px;">
                        {{ $line['line_tax'] ?? '0.00' }}
                                </td>
                    <td style="padding: 12px 8px; text-align: right; border-bottom: 1px solid #eee; font-size: 18px;">
                        {{ $line['line_total'] }}
                                    </td>
                                </tr>
                            @endforeach
                </tbody>
            </table>
    </div>

    <!-- Summary Section -->
    <div style="margin-bottom: 25px;">
        <table style="width: 100%; border-collapse: collapse; background-color: #f8f9fa; border: 2px solid #000;">
            <tr style="background-color: #e9ecef;">
                <td style="padding: 12px; font-weight: bold; width: 50%; font-size: 20px;">Sub Total</td>
                <td style="padding: 12px; text-align: right; font-weight: bold; font-size: 20px;">{{ $receipt_details->subtotal }}</td>
                    </tr>
            @if($receipt_details->total_tax > 0)
            <tr>
                    <td style="padding: 12px; font-size: 18px;">Total Tax</td>
                    <td style="padding: 12px; text-align: right; font-size: 18px;">{{ $receipt_details->total_tax }}</td>
                        </tr>
                    @endif
            @if($receipt_details->discount_amount > 0)
            <tr>
                    <td style="padding: 12px; font-size: 18px;">Total Discount</td>
                    <td style="padding: 12px; text-align: right; font-size: 18px;">-{{ $receipt_details->discount_amount }}</td>
                        </tr>
                    @endif
            <tr style="background-color: #000; color: #fff;">
                <td style="padding: 12px; font-weight: bold;">Total</td>
                <td style="padding: 12px; text-align: right; font-weight: bold; font-size: 20px;">{{ $receipt_details->final_total }}</td>
                        </tr>
            <tr style="background-color: #e9ecef;">
                <td style="padding: 12px; font-weight: bold;">Total Paid</td>
                <td style="padding: 12px; text-align: right; font-weight: bold; font-size: 20px;">{{ $receipt_details->total_paid }}</td>
                    </tr>
            <tr style="background-color: #e9ecef;">
                <td style="padding: 12px; font-weight: bold;">Total Due</td>
                <td style="padding: 12px; text-align: right; font-weight: bold; font-size: 20px;">{{ $receipt_details->total_due }}</td>
                        </tr>
            </table>
    </div>

    <!-- Payment Details -->
    @if(!empty($receipt_details->payment_lines))
    <div style="margin-bottom: 25px;">
        <h3 style="font-size: 18px; font-weight: bold; margin-bottom: 15px;">Payment Details</h3>
        <table style="width: 100%; border-collapse: collapse; background-color: #f8f9fa; border: 2px solid #000;">
            <thead>
                <tr style="background-color: #e9ecef;">
                    <th style="padding: 12px; font-weight: bold; text-align: left;">Payment Method</th>
                    <th style="padding: 12px; font-weight: bold; text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt_details->payment_lines as $payment)
                <tr>
                    <td style="padding: 12px; border-bottom: 1px solid #ddd;">{{ $payment['method'] }}</td>
                    <td style="padding: 12px; text-align: right; border-bottom: 1px solid #ddd;">{{ $payment['amount'] }}</td>
                    </tr>
                @endforeach
            </tbody>
            </table>
        </div>
    @endif

    <!-- Amount in Words -->
    <div style="margin-bottom: 25px;">
        <div style="font-weight: bold; margin-bottom: 10px;">Amount in words:</div>
        <div style="font-style: italic; color: #666;">
            {{ $receipt_details->amount_in_words ?? 'N/A' }}
        </div>
    </div>

    <!-- QR Code and Barcode Section -->
    <div style="text-align: center; margin: 25px auto; width: 100%;">
        @if($receipt_details->show_barcode || $receipt_details->show_qr_code)
            @if($receipt_details->show_barcode)
                <div style="margin: 0 auto 15px auto; text-align: center;">
                    <img src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2,30,array(39, 48, 54), true)}}" style="display: block; margin: 0 auto;">
                </div>
            @endif
            @if($receipt_details->show_qr_code && !empty($receipt_details->qr_code_text))
                <div style="margin: 0 auto; text-align: center;">
                    <img src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE', 3, 3, [39, 48, 54])}}" style="width: 120px; height: 120px; display: block; margin: 0 auto;">
                </div>
            @elseif(!empty($receipt_details->qr_code))
                <div style="margin: 0 auto; text-align: center;">
                    <img src="{{ $receipt_details->qr_code }}" alt="QR Code" style="width: 120px; height: 120px; display: block; margin: 0 auto;">
                </div>
            @else
                <!-- Placeholder QR Code -->
                <div style="width: 120px; height: 120px; background-color: #f0f0f0; border: 2px solid #ddd; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #666;">
                    QR Code
                </div>
            @endif
        @elseif(!empty($receipt_details->qr_code))
            <div style="margin: 0 auto; text-align: center;">
                <img src="{{ $receipt_details->qr_code }}" alt="QR Code" style="width: 120px; height: 120px; display: block; margin: 0 auto;">
            </div>
        @else
            <!-- Placeholder QR Code -->
            <div style="width: 120px; height: 120px; background-color: #f0f0f0; border: 2px solid #ddd; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #666;">
                QR Code
            </div>
        @endif
    </div>

    <!-- Footer -->
    <div style="text-align: center; margin-top: 30px; font-size: 20px;">
        <div style="font-weight: bold;">
            Served By: {{ $receipt_details->sold_by ?? 'System' }}
        </div>
        @if(!empty($receipt_details->additional_notes))
            <div style="margin-top: 15px; font-size: 18px; color: #666;">
                {{ $receipt_details->additional_notes }}
        </div>
    @endif
    </div>

    <!-- Thank You Message -->
    <div style="text-align: center; margin-top: 20px; font-size: 18px; color: #666; font-style: italic;">
        Thank you for your business!
    </div>

</div>
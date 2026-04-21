<div style="width: 100%; color: #000000 !important; font-family: Arial, sans-serif;padding: 10px;">

    <!-- Header Section with Logo -->
    <div style="margin-bottom: 30px; text-align: center;">
                    @if(!empty($receipt_details->logo))
            <div style="display: flex; align-items: center;justify-content: center; margin-bottom: 15px;">
                <img style="max-height: 80px; width: 350px; margin-right: 15px;" src="{{ asset($receipt_details->logo) }}" alt="Company Logo">
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
        <div style="font-size: 22px; font-weight:500; margin-bottom: 20px;text-align: center;">
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

    <!-- Packing Slip Title -->
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="font-size: 48px; font-weight: bold; color: #000; margin: 0; text-transform: uppercase;">
            PACKING SLIP
        </h1>
        </div>

    <!-- Packing Slip Details -->
    <div style="margin-bottom: 25px;">
        <table style="width: 100%; border-collapse: collapse;align-items: center;">
            <tr>
                <td style="padding: 8px 0; font-weight: bold; width: 33%; font-size: 20px;">PACKING SLIP NUMBER</td>
                <td style="padding: 8px 0; font-weight: bold; width: 33%; font-size: 20px;">ORDER No.</td>
                <td style="padding: 8px 0; font-weight: bold; width: 33%; font-size: 20px;">DATE OF ISSUE</td>
            </tr>
            <tr>
                <td style="padding: 8px 0; font-size: 20px;">{{ $receipt_details->invoice_no }}</td>
                <td style="padding: 8px 0; font-size: 20px;">{{ $receipt_details->ref_no ?? 'N/A' }}</td>
                <td style="padding: 8px 0; font-size: 20px;">{{ $receipt_details->invoice_date }}</td>
            </tr>
        </table>
            </div>

    <!-- Client Information -->
    <div style="margin-bottom: 25px;">
        <div style="font-weight: 500; margin-bottom: 10px;font-size: 20px;">
            Client Name: <span style="text-transform: uppercase; font-weight: 500;">{{ $receipt_details->customer_name ?? 'Walk-in Customer' }}</span>
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
    </div>

    <!-- Items Table -->
    <div style="margin-bottom: 25px;">
        <table style="width: 100%; border-collapse: collapse; border-top: 2px solid #000;">
                <thead>
                <tr style="background-color: #f8f9fa;">
                    <th style="padding: 12px 8px; text-align: left; font-weight: bold; border-bottom: 1px solid #ddd; font-size: 20px;">Item</th>
                    <th style="padding: 12px 8px; text-align: center; font-weight: bold; border-bottom: 1px solid #ddd; font-size: 20px;">Qtty</th>
                    <th style="padding: 12px 8px; text-align: center; font-weight: bold; border-bottom: 1px solid #ddd; font-size: 20px;">Packed</th>
                    <th style="padding: 12px 8px; text-align: center; font-weight: bold; border-bottom: 1px solid #ddd; font-size: 20px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt_details->lines as $line)
                <tr>
                    <td style="padding: 12px 8px; border-bottom: 1px solid #eee; font-size: 22px;">
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
                    <td style="padding: 12px 8px; text-align: center; border-bottom: 1px solid #eee; font-size: 22px;">
                        {{ $line['quantity'] }} {{ $line['unit'] ?? 'Pc(s)' }}
                                </td>
                    <td style="padding: 12px 8px; text-align: center; border-bottom: 1px solid #eee; font-size: 22px;">
                        {{ $line['quantity'] }} {{ $line['unit'] ?? 'Pc(s)' }}
                            </td>
                    <td style="padding: 12px 8px; text-align: center; border-bottom: 1px solid #eee; font-size: 22px;">
                        <span style="background-color: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-size: 22px;">PACKED</span>
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
                <td style="padding: 12px; font-weight: 500; width: 50%; font-size: 20px;">Total Items</td>
                <td style="padding: 12px; text-align: right; font-weight: bold; font-size: 22px;">{{ count($receipt_details->lines) }}</td>
            </tr>
            <tr style="background-color: #e9ecef;">
                <td style="padding: 12px; font-weight: 500;font-size: 22px;">Total Quantity</td>
                <td style="padding: 12px; text-align: right; font-weight: 500; font-size: 22px;">{{ array_sum(array_column($receipt_details->lines, 'quantity')) }}</td>
            </tr>
            <tr style="background-color: #000; color: #fff;">
                <td style="padding: 12px; font-weight: 500;font-size: 22px;">Order Total</td>
                <td style="padding: 12px; text-align: right; font-weight: 500; font-size: 22px;">{{ $receipt_details->final_total ?? '' }}</td>
            </tr>
        </table>
    </div>

    <!-- Shipping Information -->
    @if(!empty($receipt_details->shipping_address))
    <div style="margin-bottom: 25px;">
        <div style="font-weight: 500; margin-bottom: 10px;font-size:22px">Shipping Address:</div>
        <div style="font-size: 22px; color: #666; background-color: #f8f9fa; padding: 15px; border-radius: 5px;">
            {{ $receipt_details->shipping_address }}
        </div>
                                    </div>
                                @endif

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
                    <img src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE', 3, 3, [39, 48, 54])}}" style="width: 250px; height: 250px; display: block; margin: 0 auto;">
                </div>
            @elseif(!empty($receipt_details->qr_code))
                <div style="margin: 0 auto; text-align: center;">
                    <img src="{{ $receipt_details->qr_code }}" alt="QR Code" style="width: 250px; height: 250px; display: block; margin: 0 auto;">
                </div>
            @else
                <!-- Placeholder QR Code -->
                <div style="width: 120px; height: 120px; background-color: #f0f0f0; border: 2px solid #ddd; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 22px; color: #666;">
                    QR Code
                                    </div>
                                @endif
        @elseif(!empty($receipt_details->qr_code))
            <div style="margin: 0 auto; text-align: center;">
                <img src="{{ $receipt_details->qr_code }}" alt="QR Code" style="width: 250px; height: 250px; display: block; margin: 0 auto;">
                            </div>
        @else
            <!-- Placeholder QR Code -->
            <div style="width: 120px; height: 120px; background-color: #f0f0f0; border: 2px solid #ddd; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 22px; color: #666;">
                QR Code
        </div>
                    @endif
    </div>

    <!-- Footer -->
    <div style="text-align: center; margin-top: 30px; font-size: 22px;">
        <div style="font-weight: bold;">
            Packed By: {{ $receipt_details->sold_by ?? 'System' }}
        </div>
    @if(!empty($receipt_details->additional_notes))
            <div style="margin-top: 15px; font-size: 22px; color: #666;">
                {{ $receipt_details->additional_notes }}
            </div>
        @endif
        </div>

    <!-- Thank You Message -->
    <div style="text-align: center; margin-top: 20px; font-size: 22px; color: #666; font-style: italic;">
        Thank you for your business!
    </div>

</div>
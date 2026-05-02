<section class="print-section print-codes">
    @if($document['codes']['show_barcode'])
        <div class="print-code">
            <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($document['codes']['barcode_value'], 'C128', $document['codes']['barcode_scale'], $document['codes']['barcode_height'], [17, 24, 39], true) }}" alt="Barcode">
            <div class="print-lines print-lines--muted" style="margin-top: 6px;">
                <div><strong>Value:</strong> {{ $document['codes']['barcode_value'] }}</div>
                @if(!empty($document['codes']['price_value']))
                    <div><strong>{{ $document['codes']['price_label'] }}:</strong> {{ $document['codes']['price_value'] }}</div>
                @endif
            </div>
        </div>
    @endif
    @if($document['codes']['show_qr_code'])
        <div class="print-code">
            @if(!empty($document['codes']['qr_image']))
                <img src="{{ $document['codes']['qr_image'] }}" alt="QR Code" style="width: {{ $document['codes']['qr_size'] }}px; height: {{ $document['codes']['qr_size'] }}px;">
            @else
                <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($document['codes']['qr_value'], 'QRCODE,M', 5, 5, [17, 24, 39]) }}" alt="QR Code" style="width: {{ $document['codes']['qr_size'] }}px; height: {{ $document['codes']['qr_size'] }}px;">
            @endif
            <div class="print-lines print-lines--muted" style="margin-top: 6px;">
                @if(!empty($document['codes']['qr_value']))
                    <div><strong>Value:</strong> {{ $document['codes']['qr_value'] }}</div>
                @endif
                @if(!empty($document['codes']['price_value']))
                    <div><strong>{{ $document['codes']['price_label'] }}:</strong> {{ $document['codes']['price_value'] }}</div>
                @endif
            </div>
        </div>
    @endif
</section>

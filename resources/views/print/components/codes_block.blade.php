<section class="print-section print-codes">
    @if($document['codes']['show_barcode'])
        <div class="print-code">
            <img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($document['codes']['barcode_value'], 'C128', $document['codes']['barcode_scale'], $document['codes']['barcode_height'], [17, 24, 39], true) }}" alt="Barcode">
        </div>
    @endif
    @if($document['codes']['show_qr_code'])
        <div class="print-code">
            @if(!empty($document['codes']['qr_image']))
                <img src="{{ $document['codes']['qr_image'] }}" alt="QR Code" style="width: {{ $document['codes']['qr_size'] }}px; height: {{ $document['codes']['qr_size'] }}px;">
            @else
                <img src="data:image/png;base64,{{ DNS2D::getBarcodePNG($document['codes']['qr_value'], 'QRCODE', 3, 3, [17, 24, 39]) }}" alt="QR Code" style="width: {{ $document['codes']['qr_size'] }}px; height: {{ $document['codes']['qr_size'] }}px;">
            @endif
        </div>
    @endif
</section>

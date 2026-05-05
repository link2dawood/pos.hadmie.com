@php
    $custom_labels = json_decode(session('business.custom_labels'), true);
    $product_custom_fields = !empty($custom_labels['product']) ? $custom_labels['product'] : [];

    $show_barcode = !empty($print['barcode']);
    $show_barcode_text = !empty($print['barcode_text']);
    $show_qr = !empty($print['qr_code']);
    $show_qr_text = !empty($print['qr_text']);

    if (! $show_barcode && ! $show_qr) {
        $show_barcode = true;
        $show_barcode_text = true;
    }

    $barcode_value = !empty($page_product->sub_sku) ? $page_product->sub_sku : ($page_product->barcode ?: $page_product->qr_code_value);
    $qr_value = !empty($page_product->sub_sku) ? $page_product->sub_sku : ($page_product->qr_code_value ?: $page_product->barcode);
    $raw_price = $print['price_type'] === 'exclusive'
        ? (float) ($page_product->default_sell_price ?? 0)
        : (float) ($page_product->sell_price_inc_tax ?? 0);
    $formatted_price = function_exists('num_format') ? num_format($raw_price) : number_format($raw_price, 2);

    // Numeric-only types (EAN, UPC, etc.) throw if the SKU has letters/hyphens.
    // Fall back to C128 which encodes any printable ASCII safely.
    $barcode_type = $page_product->barcode_type ?: 'C128';
    $digit_only_types = ['EAN8','EAN13','UPCA','UPCE','I25','I25+','S25','S25+','MSI','MSI+','POSTNET','PLANET','CODE11'];
    if (!empty($barcode_value) && in_array($barcode_type, $digit_only_types) && !ctype_digit($barcode_value)) {
        $barcode_type = 'C128';
    }

    // Generate barcode PNG — height 60 keeps bars short. Width multiplier scales down for long SKUs.
    $barcode_img = null;
    if (!empty($barcode_value)) {
        $len = strlen($barcode_value);
        $w_mult = $len <= 12 ? 3 : ($len <= 18 ? 2 : ($len <= 26 ? 2 : 1));
        try {
            $barcode_img = DNS1D::getBarcodePNG($barcode_value, $barcode_type, $w_mult, 60, [0, 0, 0], false);
        } catch (\Throwable $e) {
            try {
                $barcode_img = DNS1D::getBarcodePNG($barcode_value, 'C128', $w_mult, 60, [0, 0, 0], false);
            } catch (\Throwable $e2) {
                $barcode_img = null;
            }
        }
    }

    // QRCODE,M = medium error correction — more tolerant when CSS stretches it to fill the label rectangle.
    $qr_img = null;
    if (!empty($qr_value)) {
        try {
            $qr_img = DNS2D::getBarcodePNG($qr_value, 'QRCODE,M', 8, 8, [0, 0, 0]);
        } catch (\Throwable $e) {
            $qr_img = null;
        }
    }
@endphp

@php
    // Card dimensions come from $barcode_details when available (print preview),
    // or from explicit $card_width/$card_height when used as an inline preview.
    $card_w = $card_width  ?? (isset($barcode_details) ? ($barcode_details->width  . 'in') : '3in');
    $card_h = $card_height ?? (isset($barcode_details) ? ($barcode_details->height . 'in') : '1.5in');

    // Cap font sizes — keep all text small so codes stay large.
    $sz_business  = min((int) ($print['business_name_size'] ?? 8),  8);
    $sz_name      = min((int) ($print['name_size']          ?? 9),  9);
    $sz_variation = min((int) ($print['variations_size']    ?? 8),  8);
    $sz_price     = min((int) ($print['price_size']         ?? 8),  8);
    $sz_meta      = min(
        (int) ($print['packing_date_size'] ?? 8),
        (int) ($print['exp_date_size']     ?? 8),
        (int) ($print['lot_number_size']   ?? 8),
        8
    );
@endphp
<div class="label-card" style="width: {{ $card_w }}; height: {{ $card_h }};">
    <div class="label-card__inner">
        @if(!empty($print['business_name']))
            <div class="label-card__business" style="font-size: {{ $sz_business }}px;">
                {{ $business_name }}
            </div>
        @endif

        @if(!empty($print['name']))
            <div class="label-card__name" style="font-size: {{ $sz_name }}px;">
                {{ $page_product->product_actual_name }}
            </div>
        @endif

        @if(!empty($print['variations']) && $page_product->is_dummy != 1)
            <div class="label-card__variation" style="font-size: {{ $sz_variation }}px;">
                {{ $page_product->product_variation_name }}: <strong>{{ $page_product->variation_name }}</strong>
            </div>
        @endif

        @if(!empty($print['price']))
            <div class="label-card__price" style="font-size: {{ $sz_price }}px;">
                <span class="label-card__price-label">@lang('lang_v1.price'):</span>
                <span class="label-card__price-value">{{ session('currency')['symbol'] ?? '' }} {{ $formatted_price }}</span>
            </div>
        @endif

        @php
            $has_meta = !empty($print['exp_date']) && !empty($page_product->exp_date);
            $has_meta = $has_meta || (!empty($print['packing_date']) && !empty($page_product->packing_date));
            $has_meta = $has_meta || (!empty($print['lot_number']) && !empty($page_product->lot_number));
        @endphp

        @if($has_meta)
            <div class="label-card__meta" style="font-size: {{ $sz_meta }}px;">
                @if(!empty($print['lot_number']) && !empty($page_product->lot_number))
                    <span class="label-card__meta-line"><strong>@lang('lang_v1.lot_number'):</strong> {{ $page_product->lot_number }}</span>
                @endif
                @if(!empty($print['exp_date']) && !empty($page_product->exp_date))
                    <span class="label-card__meta-line"><strong>@lang('product.exp_date'):</strong> {{ $page_product->exp_date }}</span>
                @endif
                @if(!empty($print['packing_date']) && !empty($page_product->packing_date))
                    <span class="label-card__meta-line"><strong>@lang('lang_v1.packing_date'):</strong> {{ $page_product->packing_date }}</span>
                @endif
            </div>
        @endif

        @foreach($product_custom_fields as $index => $cf)
            @php
                $field_name = 'product_custom_field' . $loop->iteration;
            @endphp
            @if(!empty($cf) && !empty($page_product->$field_name) && !empty($print[$field_name]))
                <div class="label-card__meta" style="font-size: {{ min((int)($print[$field_name . '_size'] ?? 8), 8) }}px;">
                    <strong>{{ $cf }}:</strong> {{ $page_product->$field_name }}
                </div>
            @endif
        @endforeach

        <div class="label-card__codes @if($show_barcode && $show_qr) label-card__codes--both @endif">
            @if($show_qr && !empty($qr_value) && $qr_img)
                <div class="label-card__code label-card__code--qr">
                    <div class="label-card__img-wrap label-card__img-wrap--qr">
                        <img
                            class="label-card__qr-image"
                            src="data:image/png;base64,{{ $qr_img }}"
                            alt="QR code">
                    </div>
                    {{-- QR text intentionally hidden --}}
                </div>
            @endif

            @if($show_barcode && !empty($barcode_value) && $barcode_img)
                <div class="label-card__code label-card__code--barcode">
                    <div class="label-card__img-wrap label-card__img-wrap--barcode">
                        <img
                            class="label-card__barcode-image"
                            src="data:image/png;base64,{{ $barcode_img }}"
                            alt="Barcode">
                    </div>
                    @if($show_barcode_text)
                        <div class="label-card__code-text label-card__code-text--barcode">{{ $barcode_value }}</div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

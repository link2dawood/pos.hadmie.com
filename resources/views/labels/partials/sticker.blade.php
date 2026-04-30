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
    $formatted_price = $print['price_type'] === 'exclusive'
        ? @num_format($page_product->default_sell_price)
        : @num_format($page_product->sell_price_inc_tax);
@endphp

<div class="label-card">
    <div class="label-card__inner">
        @if(!empty($print['business_name']))
            <div class="label-card__business" style="font-size: {{ $print['business_name_size'] }}px;">
                {{ $business_name }}
            </div>
        @endif

        @if(!empty($print['name']))
            <div class="label-card__name" style="font-size: {{ $print['name_size'] }}px;">
                {{ $page_product->product_actual_name }}
            </div>
        @endif

        @if(!empty($print['variations']) && $page_product->is_dummy != 1)
            <div class="label-card__variation" style="font-size: {{ $print['variations_size'] }}px;">
                {{ $page_product->product_variation_name }}: <strong>{{ $page_product->variation_name }}</strong>
            </div>
        @endif

        @if(!empty($print['price']))
            <div class="label-card__price" style="font-size: {{ $print['price_size'] }}px;">
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
            <div class="label-card__meta" style="font-size: {{ min((int) ($print['packing_date_size'] ?? 11), (int) ($print['exp_date_size'] ?? 11), (int) ($print['lot_number_size'] ?? 11)) }}px;">
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
                <div class="label-card__meta" style="font-size: {{ $print[$field_name . '_size'] }}px;">
                    <strong>{{ $cf }}:</strong> {{ $page_product->$field_name }}
                </div>
            @endif
        @endforeach

        <div class="label-card__codes @if($show_barcode && $show_qr) label-card__codes--both @endif">
            @if($show_qr && !empty($qr_value))
                <div class="label-card__code label-card__code--qr">
                    <img
                        class="label-card__qr-image"
                        src="data:image/png;base64,{{ DNS2D::getBarcodePNG($qr_value, 'QRCODE', 6, 6, [0, 0, 0]) }}"
                        alt="QR code">
                    <div class="label-card__code-text">{{ $qr_value }}</div>
                </div>
            @endif

            @if($show_barcode && !empty($barcode_value))
                <div class="label-card__code label-card__code--barcode">
                    <img
                        class="label-card__barcode-image"
                        src="data:image/png;base64,{{ DNS1D::getBarcodePNG($barcode_value, $page_product->barcode_type ?: 'C128', 3, 150, [0, 0, 0], false) }}"
                        alt="Barcode">
                    <div class="label-card__code-text">{{ $barcode_value }}</div>
                </div>
            @endif
        </div>
    </div>
</div>

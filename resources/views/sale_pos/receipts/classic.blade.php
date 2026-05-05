<div style="width: 100%; color: #000000 !important; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; line-height: 1.4;padding: 10px;">

    <!-- Header Section -->
    <div >
        @if(empty($receipt_details->letter_head))
            @if(!empty($receipt_details->header_text))
                <div class="text-center">{!! $receipt_details->header_text !!}</div>
            @endif
            @php
                $sub_headings = implode('<br/>', array_filter([
                    $receipt_details->sub_heading_line1 ?? '',
                    $receipt_details->sub_heading_line2 ?? '',
                    $receipt_details->sub_heading_line3 ?? '',
                    $receipt_details->sub_heading_line4 ?? '',
                    $receipt_details->sub_heading_line5 ?? ''
                ], 'strlen'));
            @endphp
            @if(!empty($sub_headings))
                <div class="text-center"><span>{!! $sub_headings !!}</span></div>
            @endif
        @endif
        
            <div class="row">
                <div class="col-md-12" style="display: flex;">
                    @if(!empty($receipt_details->invoice_heading))
                    @if(!empty($receipt_details->logo))
                        <img style="max-height: 100px; width: 350px;" src="{{ asset($receipt_details->logo) }}" class="img center-block">
                    @endif
                    @endif
                </div>
                <div style="display: flex;justify-content: center;margin:10px 0px;" class="col-md-12">
                     <p style="margin:5px 0; font-size:20px;">
    {!! collect([
        $receipt_details->location_custom_fields ?? null,
        $receipt_details->address ?? null,
        $receipt_details->contact ?? null,
        $receipt_details->website ?? null
    ])
    ->map(function ($item) {
        return $item
            ? str_replace(['<b>', '</b>'], '', $item)
            : null;
    })
    ->filter()
    ->implode(' | ') !!}
</p>

                </div>
                @if(!empty($receipt_details->invoice_heading))
                <div style="display: flex;justify-content: center;" class="col-md-12">
                    <h3 style="font-weight: bold;font-size: 50px !important;border-radius: 12px;padding: 0px;margin: 0px;">
                       <strong>{!! $receipt_details->invoice_heading !!}</strong>
                    </h3>
                </div>
                @endif
            </div>
    </div>

    <!-- Letterhead (if applicable) -->
    @if(!empty($receipt_details->letter_head))
        <div style="margin-bottom: 20px;">
            <img style="width: 100%; margin-bottom: 10px;" src="{{ asset($receipt_details->letter_head) }}">
        </div>
    @endif
    
    <div style="display: flex; justify-content: space-around; text-align: center;font-size: 22px;">
    @if(!empty($receipt_details->invoice_no_prefix))    
    <div>
            <div style="text-transform: uppercase; font-weight: 500;">Invoice No.</div>
            <div>{{$receipt_details->invoice_no}}</div>
        </div>
    @endif
        <div>
            <div style="text-transform: uppercase; font-weight: 500;">TAX No.</div>
            <div>{{ $receipt_details->customer_tax_number ?? '' }}</div>
        </div>
        @if(!empty($receipt_details->date_label))
        <div>
            <div style="text-transform: uppercase; font-weight: 500;">Date of Issue</div>
            <div>{{$receipt_details->invoice_date}}</div>
        </div>
        @endif
    </div>

    <!-- Business and Customer Info -->
    <div class="row invoice-info" style="background-color: #e8e8ea !important; padding-left: 8px; padding-right: 8px;border-bottom-width: 3px; border-bottom-color: #868688; margin-bottom: 0px !important; line-height: 24px;">
        <div class="col-md-12 tw-text-base-td-gray font-24">
            <span>
                  Client Name : {{ $receipt_details->customer_name ?? 'Walk-in Customer' }}
                @if(!empty($receipt_details->display_name))
                    <b>{{$receipt_details->display_name}}</b>
                @endif
                @if(!empty($receipt_details->tax_info1))
                 {{ $receipt_details->tax_label1}}{{ $receipt_details->tax_info1}}
               @endif
                @if(!empty($receipt_details->tax_info2))
                    <br/>{{ $receipt_details->tax_label2 }} {{ $receipt_details->tax_info2 }}
                @endif
            </span>
        </div>

        <div class="col-md-12 tw-text-base-td-gray font-24">
            <div>
                <!--@if(!empty($receipt_details->customer_label))-->
                <!--    <strong>{{ $receipt_details->customer_label }}</strong><br/>-->
                <!--@endif-->
                <!--@if(!empty($receipt_details->customer_info))-->
                <!--    {!! $receipt_details->customer_info !!}-->
                <!--@endif-->
                @if(!empty($receipt_details->client_id_label))
                    <br/><strong>{{ $receipt_details->client_id_label }}</strong> {{ $receipt_details->client_id }}
                @endif
                @if(!empty($receipt_details->customer_tax_label))
                    <br/><strong>{{ $receipt_details->customer_tax_label }}</strong> {{ $receipt_details->customer_tax_number }}
                @endif
                @if(!empty($receipt_details->customer_custom_fields))
                    <br/>{!! $receipt_details->customer_custom_fields !!}
                @endif
                @if(!empty($receipt_details->commission_agent_label))
                    <br/><strong>{{ $receipt_details->commission_agent_label }}</strong> {{ $receipt_details->commission_agent }}
                @endif
                @if(!empty($receipt_details->customer_rp_label))
                    <br/><strong>{{ $receipt_details->customer_rp_label }}</strong> {{ $receipt_details->customer_total_rp }}
                @endif
            </div>
        </div>
    </div>

    <!-- Product Table -->
    <div class="row mt-5" style="border-bottom-width: 3px; border-bottom-color: #868688;">
        <div class="col-xs-12">
            <table class="table table-no-top-cell-border table-slim mb-12" style="page-break-inside: auto;">
                <thead>
                    <tr style="background-color: none !important; color: #7f7f81 !important; font-size: 20px !important; font-weight: 400;border-bottom-width: 3px; border-bottom-color: #868688;" class="table-no-side-cell-border table-no-top-cell-border">
                        <td style="background-color: none !important; color: #7f7f81 !important; width: 6% !important; padding-top: 8px !important; padding-bottom: 8px !important;">NO.</td>
                        @php
                            $p_width = 40;
                            if ($receipt_details->show_cat_code == 1) $p_width -= 10;
                            if (!empty($receipt_details->item_discount_label)) $p_width -= 10;
                            if (!empty($receipt_details->discounted_unit_price_label)) $p_width -= 5;
                        @endphp
                        <td style="background-color: none !important; color: #7f7f81 !important; width: {{$p_width}}% !important; padding-top: 8px !important; padding-bottom: 8px !important;">Items</td>
                        @if($receipt_details->show_cat_code == 1)
                            <td style="background-color: none !important; color: #7f7f81 !important; width: 10% !important;">{{$receipt_details->cat_code_label}}</td>
                        @endif
                        <td style="background-color: none !important; color: #7f7f81 !important; width: 25% !important; padding-top: 8px !important; padding-bottom: 8px !important;">Qtty</td>
                        <td style="background-color: none !important; color: #7f7f81 !important; width: 25% !important; padding-top: 8px !important; padding-bottom: 8px !important;">Price</td>
                        @if(!empty($receipt_details->discounted_unit_price_label))
                            <td style="background-color: none !important; color: #7f7f81 !important; width: 10% !important;">{{$receipt_details->discounted_unit_price_label}}</td>
                        @endif
                        @if(!empty($receipt_details->item_discount_label))
                            <td style="background-color: none !important; color: #7f7f81 !important; width: 10% !important;">{{$receipt_details->item_discount_label}}</td>
                        @endif
                        <td colspan="2" style="background-color: none !important; color: #7f7f81 !important; width: 10% !important; padding-top: 8px !important; padding-bottom: 8px !important;">{{$receipt_details->table_subtotal_label}}</td>
                    </tr>
                </thead>
                <tbody style="page-break-inside: auto;">
                    @foreach($receipt_details->lines as $line)
                        <tr style="page-break-inside: auto; page-break-after: auto;">
                            <td style="border-bottom-width: 2px !important; border-bottom-color: #bebebe !important;" class="font-24 text-left">
                                {{$loop->iteration}}
                            </td>
                            <td style="border-bottom-width: 2px !important; border-bottom-color: #bebebe !important;" class="font-24 text-left">
                                @if(!empty($line['image']))
                                    <img src="{{ asset($line['image']) }}" alt="Image" width="50" style="float: left; margin-right: 8px;">
                                @endif
                                {{$line['name']}} {{$line['product_variation']}} {{$line['variation']}}
                                @if(!empty($line['sub_sku'])) , {{$line['sub_sku']}} @endif
                                @if(!empty($line['brand'])) , {{$line['brand']}} @endif
                                @if(!empty($line['product_custom_fields'])) , {{$line['product_custom_fields']}} @endif
                                @if(!empty($line['product_description'])) <small>{!!$line['product_description']!!}</small> @endif
                                @if(!empty($line['sell_line_note'])) <br><small>{!!$line['sell_line_note']!!}</small> @endif
                                @if(!empty($line['lot_number'])) <br>{{$line['lot_number_label']}}: {{$line['lot_number']}} @endif
                                @if(!empty($line['product_expiry'])) , {{$line['product_expiry_label']}}: {{$line['product_expiry']}} @endif
                                @if(!empty($line['warranty_name'])) <br><small>{{$line['warranty_name']}}</small> @endif
                                @if(!empty($line['warranty_exp_date'])) <small>- {{@format_date($line['warranty_exp_date'])}}</small> @endif
                                @if(!empty($line['warranty_description'])) <small>{{$line['warranty_description'] ?? ''}}</small> @endif
                                @if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
                                    <br><small>
                                        1 {{$line['units']}} = {{$line['base_unit_multiplier']}} {{$line['base_unit_name']}}<br>
                                        {{$line['base_unit_price']}} x {{$line['orig_quantity']}} = {{$line['line_total']}}
                                    </small>
                                @endif
                            </td>
                            @if($receipt_details->show_cat_code == 1)
                                <td class="font-24" style="border-bottom-width: 2px !important; border-bottom-color: #bebebe !important;">
                                    @if(!empty($line['cat_code'])) {{$line['cat_code']}} @endif
                                </td>
                            @endif
                            <td style="border-bottom-width: 2px !important; border-bottom-color: #bebebe !important;" class="font-24 text-left">
                                {{$line['quantity']}} {{$line['units']}}
                                @if($receipt_details->show_base_unit_details && $line['quantity'] && $line['base_unit_multiplier'] !== 1)
                                    <br><small>{{$line['quantity']}} x {{$line['base_unit_multiplier']}} = {{$line['orig_quantity']}} {{$line['base_unit_name']}}</small>
                                @endif
                            </td>
                            <td style="border-bottom-width: 2px !important; border-bottom-color: #bebebe !important;" class="font-24 text-left">
                                {{$line['unit_price_before_discount']}}
                            </td>
                            @if(!empty($receipt_details->discounted_unit_price_label))
                                <td class="font-24" style="border-bottom-width: 2px !important; border-bottom-color: #bebebe !important;" class="text-right">
                                    {{$line['unit_price_inc_tax']}}
                                </td>
                            @endif
                            @if(!empty($receipt_details->item_discount_label))
                                <td style="border-bottom-width: 2px !important; border-bottom-color: #bebebe !important;" class="font-24 text-right">
                                    {{$line['total_line_discount'] ?? '0.00'}}
                                    @if(!empty($line['line_discount_percent'])) ({{$line['line_discount_percent']}}%) @endif
                                </td>
                            @endif
                            <td style="border-bottom-width: 2px !important; border-bottom-color: #bebebe !important;" class="font-24 text-left">
                                {{$line['line_total_exc_tax']}}
                            </td>
                        </tr>
                        @if(!empty($line['modifiers']))
                            @foreach($line['modifiers'] as $modifier)
                                <tr style="page-break-inside: auto; page-break-after: auto;">
                                    <td style="border-bottom-width: 2px !important; border-bottom-color: #bebebe !important;" class="font-24">
                                         
                                    </td>
                                    <td style="border-bottom-width: 2px !important; border-bottom-color: #bebebe !important;">
                                        {{$modifier['name']}} {{$modifier['variation']}}
                                        @if(!empty($modifier['sub_sku'])) , {{$modifier['sub_sku']}} @endif
                                        @if(!empty($modifier['sell_line_note'])) ({!!$modifier['sell_line_note']!!}) @endif
                                    </td>
                                    @if($receipt_details->show_cat_code == 1)
                                        <td style="border-bottom-width: 2px !important; border-bottom-color: #bebebe !important;" class="font-24">
                                            @if(!empty($modifier['cat_code'])) {{$modifier['cat_code']}} @endif
                                        </td>
                                    @endif
                                    <td style="border-bottom-width: 2px !important; border-bottom-color: #bebebe !important;" class="text-right font-24">
                                        {{$modifier['quantity']}} {{$modifier['units']}}
                                    </td>
                                    <td style="border-bottom-width: 2px !important; border-bottom-color: #bebebe !important;" class="text-right font-24">
                                        {{$modifier['unit_price_exc_tax']}}
                                    </td>
                                    @if(!empty($receipt_details->discounted_unit_price_label))
                                        <td style="border-bottom-width: 2px !important; border-bottom-color: #bebebe !important;" class="text-right font-24">
                                            {{$modifier['unit_price_exc_tax']}}
                                        </td>
                                    @endif
                                    @if(!empty($receipt_details->item_discount_label))
                                        <td style="border-bottom-width: 2px !important; border-bottom-color: #bebebe !important;" class="text-right font-24">
                                            0.00
                                        </td>
                                    @endif
                                    <td style="border-bottom-width: 2px !important; border-bottom-color: #bebebe !important;" class="text-right font-24">
                                        {{$modifier['line_total']}}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @endforeach
                    @php
                        $lines = count($receipt_details->lines);
                    @endphp
                    <!--@for ($i = $lines; $i < 4; $i++)-->
                    <!--    <tr style="page-break-inside: auto; page-break-after: auto;">-->
                    <!--        <td style="padding-top: 8px !important; padding-bottom: 8px !important;"> </td>-->
                    <!--        <td style="padding-top: 8px !important; padding-bottom: 8px !important;"> </td>-->
                    <!--        @if($receipt_details->show_cat_code == 1)-->
                    <!--            <td style="padding-top: 8px !important; padding-bottom: 8px !important;"> </td>-->
                    <!--        @endif-->
                    <!--        <td style="padding-top: 8px !important; padding-bottom: 8px !important;"> </td>-->
                    <!--        <td style="padding-top: 8px !important; padding-bottom: 8px !important;"> </td>-->
                    <!--        @if(!empty($receipt_details->discounted_unit_price_label))-->
                    <!--            <td style="padding-top: 8px !important; padding-bottom: 8px !important;"> </td>-->
                    <!--        @endif-->
                    <!--        @if(!empty($receipt_details->item_discount_label))-->
                    <!--            <td style="padding-top: 8px !important; padding-bottom: 8px !important;"> </td>-->
                    <!--        @endif-->
                    <!--        <td style="padding-top: 8px !important; padding-bottom: 8px !important;"> </td>-->
                    <!--    </tr>-->
                    <!--@endfor-->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Totals and Authorized Signatory -->
    <div class="row invoice-info" style="page-break-before: avoid; page-break-inside: auto !important; margin-top: 20px;">
        <div class="col-md-12">
            <table class="table-no-side-cell-border table-no-top-cell-border width-100 table-slim bg-gray" style="color: #000;
    background-color: #e8e8ea !important;">
                <tbody>
                    @if(!empty($receipt_details->total_quantity_label))
                        <tr style="font-size:28px;">
                            <td style="width: 50%; padding: 0px 12px !important; font-weight: 500">{!! $receipt_details->total_quantity_label !!}</td>
                            <td style="padding: 0px 12px !important; font-weight: 500" class="text-right">{{$receipt_details->total_quantity}}</td>
                        </tr>
                    @endif
                    @if(!empty($receipt_details->total_items_label))
                        <tr style="font-size:28px;">
                            <td style="width: 50%; padding: 0px 12px !important; font-weight: 500">{!! $receipt_details->total_items_label !!}</td>
                            <td style="padding: 0px 12px !important; font-weight: 500" class="text-right">{{$receipt_details->total_items}}</td>
                        </tr>
                    @endif
                    <tr style="font-size:28px;">
                        <td style="width: 50%; padding: 0px 12px !important; font-weight: 500">{!! $receipt_details->subtotal_label !!}</td>
                        <td style="padding: 0px 12px !important; font-weight: 500" class="text-right">{{$receipt_details->subtotal_exc_tax}}</td>
                    </tr>
                    @if(!empty($receipt_details->shipping_charges))
                        <tr style="font-size:28px;">
                            <td style="width: 50%; padding: 0px 12px !important; font-weight: 500">{!! $receipt_details->shipping_charges_label !!}</td>
                            <td style="padding: 0px 12px !important; font-weight: 500" class="text-right">{{$receipt_details->shipping_charges}}</td>
                        </tr>
                    @endif
                    @if(!empty($receipt_details->packing_charge))
                        <tr style="font-size:28px;">
                            <td style="width: 50%; padding: 0px 12px !important; font-weight: 500">{!! $receipt_details->packing_charge_label !!}</td>
                            <td style="padding: 0px 12px !important; font-weight: 500" class="text-right">{{$receipt_details->packing_charge}}</td>
                        </tr>
                    @endif
                    @if(!empty($receipt_details->taxes))
                        @foreach($receipt_details->taxes as $k => $v)
                            <tr style="font-size:28px;">
                                <td style="padding: 0px 12px !important">{{$k}}</td>
                                <td style="padding: 0px 12px !important" class="text-right">(+) {{$v}}</td>
                            </tr>
                        @endforeach
                    @endif
                    @if(!empty($receipt_details->discount))
                        <tr style="font-size:28px;">
                            <td style="padding: 0px 12px !important; font-weight: 500">{!! $receipt_details->discount_label !!}</td>
                            <td style="padding: 0px 12px !important; font-weight: 500" class="text-right">(-) {{$receipt_details->discount}}</td>
                        </tr>
                    @endif
                    @if(!empty($receipt_details->total_line_discount))
                        <tr style="font-size:28px;">
                            <td style="padding: 0px 12px !important; font-weight: 500">{!! $receipt_details->line_discount_label !!}</td>
                            <td style="padding: 0px 12px !important; font-weight: 500" class="text-right">(-) {{$receipt_details->total_line_discount}}</td>
                        </tr>
                    @endif
                    @if(!empty($receipt_details->additional_expenses))
                        @foreach($receipt_details->additional_expenses as $key => $val)
                            <tr style="font-size:28px;">
                                <td style="padding: 0px 12px !important; font-weight: 500">{{$key}}:</td>
                                <td style="padding: 0px 12px !important; font-weight: 500" class="text-right">(+) {{$val}}</td>
                            </tr>
                        @endforeach
                    @endif
                    @if(!empty($receipt_details->reward_point_label))
                        <tr style="font-size:28px;">
                            <td style="padding: 0px 12px !important; font-weight: 500">{!! $receipt_details->reward_point_label !!}</td>
                            <td style="padding: 0px 12px !important; font-weight: 500" class="text-right">(-) {{$receipt_details->reward_point_amount}}</td>
                        </tr>
                    @endif
                    @if(!empty($receipt_details->group_tax_details))
                        @foreach($receipt_details->group_tax_details as $key => $value)
                            <tr style="font-size:28px;">
                                <td style="padding: 0px 12px !important; font-weight: 500">{!! $key !!}</td>
                                <td style="padding: 0px 12px !important; font-weight: 500" class="text-right">(+) {{$value}}</td>
                            </tr>
                        @endforeach
                    @else
                        @if(!empty($receipt_details->tax))
                            <tr style="font-size:28px;">
                                <td style="padding: 0px 12px !important; font-weight: 500">{!! $receipt_details->tax_label !!}</td>
                                <td style="padding: 0px 12px !important; font-weight: 500" class="text-right">(+) {{$receipt_details->tax}}</td>
                            </tr>
                        @endif
                    @endif
                    @if($receipt_details->round_off_amount > 0)
                        <tr style="font-size:28px;">
                            <td style="padding: 0px 12px !important; font-weight: 600">{!! $receipt_details->round_off_label !!}</td>
                            <td style="padding: 0px 12px !important; font-weight: 600" class="text-right">{{$receipt_details->round_off}}</td>
                        </tr>
                    @endif
                    <tr style="font-size:28px;">
                        <th style="width: 50%; padding: 0px 12px !important; font-weight: 500;padding:6px 12px !important;">{!! $receipt_details->total_label !!}</th>
                        <td class="text-right"style="font-size:28px;padding: 0px 12px !important">{{$receipt_details->total}}</td>
                    </tr>
                    @if(!empty($receipt_details->total_paid))
                        <tr style="font-size:28px;">
                            <td style="width: 50%; padding: 0px 12px !important; font-weight: 500">{!! $receipt_details->total_paid_label !!}</td>
                            <td style="padding: 0px 12px !important; font-weight: 500" class="text-right">{{$receipt_details->total_paid}}</td>
                        </tr>
                    @endif
                    <tr class="padding-10" style="font-size:28px;">
                        <td style="width: 50%; padding: 0px 12px !important; font-weight: 500">{!! $receipt_details->total_due_label !!}</td>
                        <td style="padding: 0px 12px !important; font-weight: 500" class="text-right">{{$receipt_details->total_due}}</td>
                    </tr>
                    @if(!empty($receipt_details->total_in_words))
                        <tr style="font-size:28px;">
                            <td style="background-color: #fff !important; padding: 6px 12px !important;align-items: center;font-size:28px;" colspan="2" class="text-center">
                                <p>({{$receipt_details->total_in_words}})</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
        <div class="col-md-12 text-center">
            <table class="table-no-side-cell-border table-no-top-cell-border width-100 table-slim">
                <tbody>
                    <tr>
                        <td style="width: 100%">
                            <div class="row text-center">
                                @if(!empty($receipt_details->footer_text))
                                    <div class="@if($receipt_details->show_barcode || $receipt_details->show_qr_code) col-xs-8 @else col-xs-12 @endif">
                                        {!! $receipt_details->footer_text !!}
                                    </div>
                                @endif
                                @if($receipt_details->show_barcode || $receipt_details->show_qr_code)
                                    <div class="@if(!empty($receipt_details->footer_text)) col-xs-4 @else col-xs-12 @endif text-center" style=" display: flex; justify-content: center; align-items: center; ">
                                        @if($receipt_details->show_barcode)
                                            <img class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($receipt_details->invoice_no, 'C128', 2, 30, [39, 48, 54], true)}}">
                                        @endif
                                        @if($receipt_details->show_qr_code && !empty($receipt_details->qr_code_text))
                                            <img style="width: 250px !important; height: auto; filter: contrast(150%) !important;" class="center-block mt-5" src="data:image/png;base64,{{DNS2D::getBarcodePNG($receipt_details->qr_code_text, 'QRCODE,M', 5, 5, [39, 48, 54])}}">
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 50%" class="font-24">
                            @if(!empty($receipt_details->sales_person_label))
                                <br>{{ $receipt_details->sales_person_label }}{{ $receipt_details->sales_person }}
                            @else
                            <div style="font-weight: 500;margin-top:10px;">
            Served By: {{ $receipt_details->sold_by ?? 'System' }}
        </div>
                            @endif
                        </td>
                    </tr>
                    @if(!empty($receipt_details->total_in_words))
                        <tr>
                            <td style="background-color: #fff !important; padding-top: 14px !important; padding-bottom: 14px !important;" colspan="2" class="font-24">
                                <p>{{__('lang_v1.authorized_signatory')}}</p>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        
    </div>

    <!-- Tax Summary -->
    @if(empty($receipt_details->hide_price) && !empty($receipt_details->tax_summary_label) && !empty($receipt_details->taxes))
        <div class="border-bottom col-md-12">
            <table class="table table-slim table-bordered">
                <tr>
                    <th colspan="2" class="text-center">{{$receipt_details->tax_summary_label}}</th>
                </tr>
                @foreach($receipt_details->taxes as $key => $val)
                    <tr>
                        <td class="text-center"><b>{{$key}}</b></td>
                        <td class="text-center">{{$val}}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    <!-- Additional Notes -->
    @if(!empty($receipt_details->additional_notes))
        <div class="row">
            <div class="col-xs-12 text-center">
                <br>
                <p>{!! nl2br($receipt_details->additional_notes) !!}</p>
            </div>
        </div>
    @endif

    <!-- Footer Image (Centered at Bottom with Top Padding) -->
    <div style="padding-top: 8px;text-align: center;display: flex;">
        <img src="https://hadmie.com/hadmieInvFtBg.png" class="img center-block" style="height: 100% !important; width: auto; object-fit: cover; object-position: 50% 100%;">
    </div>

</div>
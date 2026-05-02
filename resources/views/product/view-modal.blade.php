<div class="modal-dialog modal-xl" role="document">
	<div class="modal-content">
		<div class="modal-header">
		    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		      <h4 class="modal-title" id="modalTitle">{{$product->name}}</h4>
	    </div>
	    <div class="modal-body">
	    	@php
	    		$primary_variation = $product->variations->first();
	    		$code_price_value = !empty($primary_variation) ? $primary_variation->sell_price_inc_tax : null;

	    		// sub_sku is what the label system encodes in QR codes and what POS scanner matches
	    		$qr_scan_value = (!empty($primary_variation) && !empty($primary_variation->sub_sku))
	    			? $primary_variation->sub_sku
	    			: ($product->qr_code_value ?? $product->sku);
	    		$barcode_scan_value = !empty($product->barcode) ? $product->barcode : $qr_scan_value;

	    		$_barcode_b64 = !empty($barcode_scan_value)
	    			? DNS1D::getBarcodePNG($barcode_scan_value, $product->barcode_type ?: 'C128', 2, 60, [17, 24, 39], true)
	    			: false;
	    		$barcode_data_uri = !empty($_barcode_b64) ? 'data:image/png;base64,' . $_barcode_b64 : null;

	    		$_qr_b64 = !empty($qr_scan_value)
	    			? DNS2D::getBarcodePNG($qr_scan_value, 'QRCODE,M', 6, 6, [17, 24, 39])
	    			: false;
	    		$qr_data_uri = !empty($_qr_b64) ? 'data:image/png;base64,' . $_qr_b64 : null;
	    		$download_safe_sku = preg_replace('/[^A-Za-z0-9._-]+/', '_', $product->sku ?? ('product_'.$product->id));

	    		// Pre-format price with currency so the printed/downloaded card matches the label design
	    		$_currency     = session('currency') ?? [];
	    		$_decimal_sep  = $_currency['decimal_separator']  ?? '.';
	    		$_thousand_sep = $_currency['thousand_separator'] ?? ',';
	    		$_precision    = (int) session('business.currency_precision', 2);
	    		$_symbol       = $_currency['symbol'] ?? '';
	    		$formatted_display_price = !is_null($code_price_value)
	    			? $_symbol . ' ' . number_format((float) $code_price_value, $_precision, $_decimal_sep, $_thousand_sep)
	    			: '--';
	    	@endphp
	      		<div class="row">
	      			<div class="col-sm-9">
	      				<div class="col-sm-4 invoice-col">
	      					<b>@lang('product.sku'):</b>
						{{$product->sku }}<br>
						<b>Barcode:</b>
						{{$product->barcode ?? '--' }}<br>
						@if(!empty($barcode_data_uri))
							<img src="{{ $barcode_data_uri }}" alt="Barcode" style="max-width:160px; display:block; margin-top:4px;">
							<small class="text-muted" style="display:block;">
								<strong>Selling Price:</strong>
								@if(!is_null($code_price_value))
									<span class="display_currency" data-currency_symbol="true">{{ $code_price_value }}</span>
								@else
									--
								@endif
							</small>
							<div class="btn-group btn-group-xs no-print" style="margin-top:6px;">
								<button type="button" class="btn btn-default js-download-generated-code"
									data-image-src="{{ $barcode_data_uri }}"
									data-product-name="{{ $product->name }}"
									data-code-value="{{ $barcode_scan_value }}"
									data-price-value="{{ $formatted_display_price }}"
									data-download-name="barcode_{{ $download_safe_sku }}.png">
									<i class="fa fa-download"></i> Download
								</button>
								<button type="button" class="btn btn-default js-print-generated-code"
									data-image-src="{{ $barcode_data_uri }}"
									data-product-name="{{ $product->name }}"
									data-code-value="{{ $barcode_scan_value }}"
									data-price-value="{{ $formatted_display_price }}">
									<i class="fa fa-print"></i> Print
								</button>
							</div>
						@endif
						<b>QR value:</b>
						{{$product->qr_code_value ?? '--' }}<br>
						@if(!empty($qr_data_uri))
							<img src="{{ $qr_data_uri }}" alt="QR code" style="max-width:120px; display:block; margin-top:4px;">
							<small class="text-muted" style="display:block;">
								<strong>Selling Price:</strong>
								@if(!is_null($code_price_value))
									<span class="display_currency" data-currency_symbol="true">{{ $code_price_value }}</span>
								@else
									--
								@endif
							</small>
							<div class="btn-group btn-group-xs no-print" style="margin-top:6px;">
								<button type="button" class="btn btn-default js-download-generated-code"
									data-image-src="{{ $qr_data_uri }}"
									data-product-name="{{ $product->name }}"
									data-code-value="{{ $qr_scan_value }}"
									data-price-value="{{ $formatted_display_price }}"
									data-download-name="qrcode_{{ $download_safe_sku }}.png">
									<i class="fa fa-download"></i> Download
								</button>
								<button type="button" class="btn btn-default js-print-generated-code"
									data-image-src="{{ $qr_data_uri }}"
									data-product-name="{{ $product->name }}"
									data-code-value="{{ $qr_scan_value }}"
									data-price-value="{{ $formatted_display_price }}">
									<i class="fa fa-print"></i> Print
								</button>
								<a href="{{ url('/labels/show?product_id=' . $product->id) }}" target="_blank" class="btn btn-default btn-xs" style="margin-top:4px;display:inline-block;">
									<i class="fa fa-tag"></i> Print Label Sheet
								</a>
							</div>
						@endif
						<b>@lang('product.brand'): </b>
						{{$product->brand->name ?? '--' }}<br>
						<b>@lang('product.unit'): </b>
						{{$product->unit->short_name ?? '--' }}<br>
						<b>@lang('product.barcode_type'): </b>
						{{$product->barcode_type ?? '--' }}
						@php 
    						$custom_labels = json_decode(session('business.custom_labels'), true);
						@endphp

                        @for($i = 1; $i <= 20; $i++)
                            @php
                                $db_field = 'product_custom_field' . $i;
                                $label = 'custom_field_' .$i;
                            @endphp

                            @if(!empty($product->$db_field))
                                <br/>
                                <b>{{ $custom_labels['product'][$label] ?? '' }}: </b>
                                {{$product->$db_field }}
                            @endif
                        @endfor
						
						<br>
						<strong>@lang('lang_v1.available_in_locations'):</strong>
						@if(count($product->product_locations) > 0)
							{{implode(', ', $product->product_locations->pluck('name')->toArray())}}
						@else
							@lang('lang_v1.none')
						@endif
						@if(!empty($product->media->first())) <br>
							<strong>@lang('lang_v1.product_brochure'):</strong>
							<a href="{{$product->media->first()->display_url}}" download="{{$product->media->first()->display_name}}">
								<span class="label label-info">
									<i class="fas fa-download"></i>
									{{$product->media->first()->display_name}}
								</span>
							</a>
						@endif
	      			</div>

	      			<div class="col-sm-4 invoice-col">
						<b>@lang('product.category'): </b>
						{{$product->category->name ?? '--' }}<br>
						<b>@lang('product.sub_category'): </b>
						{{$product->sub_category->name ?? '--' }}<br>	
						
						<b>@lang('product.manage_stock'): </b>
						@if($product->enable_stock)
							@lang('messages.yes')
						@else
							@lang('messages.no')
						@endif
						<br>
						@if($product->enable_stock)
							<b>@lang('product.alert_quantity'): </b>
							{{$product->alert_quantity ?? '--' }}
						@endif

						@if(!empty($product->warranty))
							<br>
							<b>@lang('lang_v1.warranty'): </b>
							{{$product->warranty->display_name }}
						@endif
	      			</div>
					
	      			<div class="col-sm-4 invoice-col">
	      				<b>@lang('product.expires_in'): </b>
	      				@php
	  						$expiry_array = ['months'=>__('product.months'), 'days'=>__('product.days'), '' =>__('product.not_applicable') ];
	  					@endphp
	      				@if(!empty($product->expiry_period) && !empty($product->expiry_period_type))
							{{$product->expiry_period}} {{$expiry_array[$product->expiry_period_type]}}
						@else
							{{$expiry_array['']}}
	      				@endif
	      				<br>
						@if($product->weight)
							<b>@lang('lang_v1.weight'): </b>
							{{$product->weight }}<br>
						@endif
						<b>@lang('product.applicable_tax'): </b>
						{{$product->product_tax->name ?? __('lang_v1.none') }}<br>
						@php
							$tax_type = ['inclusive' => __('product.inclusive'), 'exclusive' => __('product.exclusive')];
						@endphp
						<b>@lang('product.selling_price_tax_type'): </b>
						{{$tax_type[$product->tax_type]  }}<br>
						<b>@lang('product.product_type'): </b>
						@lang('lang_v1.' . $product->type)
						
	      			</div>
	      			<div class="clearfix"></div>
	      			<br>
      				<div class="col-sm-12">
      					{!! $product->product_description !!}
      				</div>
	      		</div>
      			<div class="col-sm-3 col-md-3 invoice-col">
      				<div class="thumbnail">
      					<img src="{{$product->image_url}}" alt="Product image">
      				</div>
      			</div>
      		</div>
      		@if($rack_details->count())
      		@if(session('business.enable_racks') || session('business.enable_row') || session('business.enable_position'))
      			<div class="row">
      				<div class="col-md-12">
      					<h4>@lang('lang_v1.rack_details'):</h4>
      				</div>
      				<div class="col-md-12">
      					<div class="table-responsive">
      					<table class="table table-condensed bg-gray">
      						<tr class="bg-green">
      							<th>@lang('business.location')</th>
      							@if(session('business.enable_racks'))
      								<th>@lang('lang_v1.rack')</th>
      							@endif
      							@if(session('business.enable_row'))
      								<th>@lang('lang_v1.row')</th>
      							@endif
      							@if(session('business.enable_position'))
      								<th>@lang('lang_v1.position')</th>
      							@endif
      							</tr>
      						@foreach($rack_details as $rd)
      							<tr>
	      							<td>{{$rd->name}}</td>
	      							@if(session('business.enable_racks'))
	      								<td>{{$rd->rack}}</td>
	      							@endif
	      							@if(session('business.enable_row'))
	      								<td>{{$rd->row}}</td>
	      							@endif
	      							@if(session('business.enable_position'))
	      								<td>{{$rd->position}}</td>
	      							@endif
      							</tr>
      						@endforeach
      					</table>
      					</div>
      				</div>
      			</div>
      		@endif
      		@endif
      		@if($product->type == 'single')
      			@include('product.partials.single_product_details')
      		@elseif($product->type == 'variable')
      			@include('product.partials.variable_product_details')
      		@elseif($product->type == 'combo')
      			@include('product.partials.combo_product_details')
      		@endif
      		@if($product->enable_stock == 1)
	      		<div class="row">
	      			<div class="col-md-12">
	      				<strong>@lang('lang_v1.product_stock_details')</strong>
	      			</div>
	      			<div class="col-md-12" id="view_product_stock_details" data-product_id="{{$product->id}}">
	      			</div>
	      		</div>
      		@endif
      	</div>
      	<div class="modal-footer">
      		<button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print" 
	        aria-label="Print" 
	          onclick="$(this).closest('div.modal').printThis();">
	        <i class="fa fa-print"></i> @lang( 'messages.print' )
	      </button>
	      	<button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
	    </div>
	</div>
</div>

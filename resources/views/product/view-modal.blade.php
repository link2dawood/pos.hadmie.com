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

	    		$qr_scan_value     = (!empty($primary_variation) && !empty($primary_variation->sub_sku))
	    			? $primary_variation->sub_sku
	    			: ($product->qr_code_value ?? $product->sku);
	    		$barcode_scan_value = !empty($product->barcode) ? $product->barcode : $qr_scan_value;
	    		$download_safe_sku  = preg_replace('/[^A-Za-z0-9._-]+/', '_', $product->sku ?? ('product_'.$product->id));

	    		// Build a synthetic $print and $page_product for the shared sticker partial.
	    		$_modal_print = [
	    			'business_name'      => true,
	    			'business_name_size' => 9,
	    			'name'               => true,
	    			'name_size'          => 11,
	    			'variations'         => false,
	    			'variations_size'    => 9,
	    			'price'              => !is_null($code_price_value),
	    			'price_size'         => 10,
	    			'price_type'         => 'inclusive',
	    			'barcode'            => !empty($barcode_scan_value),
	    			'barcode_text'       => true,
	    			'qr_code'            => !empty($qr_scan_value),
	    			'qr_text'            => false,
	    			'exp_date'           => false,
	    			'packing_date'       => false,
	    			'lot_number'         => false,
	    		];
	    		$_modal_product = (object)[
	    			'product_actual_name'    => $product->name,
	    			'sub_sku'                => $primary_variation->sub_sku ?? null,
	    			'barcode'                => $product->barcode,
	    			'qr_code_value'          => $product->qr_code_value,
	    			'barcode_type'           => $product->barcode_type ?: 'C128',
	    			'sell_price_inc_tax'     => $code_price_value,
	    			'default_sell_price'     => $code_price_value,
	    			'is_dummy'               => 0,
	    			'product_variation_name' => null,
	    			'variation_name'         => null,
	    			'exp_date'               => null,
	    			'packing_date'           => null,
	    			'lot_number'             => null,
	    		];
	    	@endphp

	    	{{-- Inject label card styles for this AJAX-loaded modal --}}
	    	@include('labels.partials.label_card_styles')
	    	<style>
	    		.modal-label-preview { display:flex; justify-content:center; padding:12px 0 8px; }
	    		.modal-label-actions { display:flex; gap:6px; flex-wrap:wrap; justify-content:center; margin-bottom:10px; }
	    		/* Fix img-wrap heights inside the modal card */
	    	</style>
	    	<script>
	    		document.addEventListener('DOMContentLoaded', function() {
	    			setTimeout(function() {
	    				document.querySelectorAll('.modal-label-preview .label-card__code').forEach(function(code) {
	    					var wrap = code.querySelector('.label-card__img-wrap');
	    					if (!wrap) return;
	    					var h = code.getBoundingClientRect().height;
	    					var t = code.querySelector('.label-card__code-text');
	    					var th = t ? t.getBoundingClientRect().height + 2 : 0;
	    					if (h - th > 0) { wrap.style.height = (h - th) + 'px'; wrap.style.flex = 'none'; }
	    				});
	    			}, 80);
	    		});
	    	</script>

	      		<div class="row">
	      			<div class="col-sm-9">
	      				<div class="col-sm-4 invoice-col">
	      					<b>@lang('product.sku'):</b> {{$product->sku }}<br>
						<b>Barcode:</b> {{$product->barcode ?? '--' }}<br>
						<b>QR value:</b> {{$product->qr_code_value ?? '--' }}<br>

	      					{{-- Label card preview (shared sticker partial) --}}
	      					<div class="modal-label-preview">
	      						@include('labels.partials.sticker', [
	      							'page_product'  => $_modal_product,
	      							'print'         => $_modal_print,
	      							'business_name' => session('business.name', ''),
	      							'card_width'    => '280px',
	      							'card_height'   => '140px',
	      						])
	      					</div>

	      					<div class="modal-label-actions no-print">
								<a href="{{ url('/labels/show?product_id=' . $product->id) }}" target="_blank" class="btn btn-default btn-xs">
									<i class="fa fa-tag"></i> Print Label Sheet
								</a>
	      					</div>

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

@extends('layouts.app')
@section('title', __('lang_v1.add_purchase_requisition'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.add_purchase_requisition')</h1>
</section>

<!-- Main content -->
<section class="content">
	{!! Form::open(['url' => action([\App\Http\Controllers\PurchaseRequisitionController::class, 'store']), 'method' => 'post', 'id' => 'add_purchase_requisition_form' ]) !!}
	@component('components.widget', ['class' => 'box-solid'])
		<div class="row">
			@if(count($business_locations) == 1)
				@php 
					$default_location = current(array_keys($business_locations->toArray()));
					$search_disable = false; 
				@endphp
			@else
				@php $default_location = null;
				$search_disable = true;
				@endphp
			@endif
			<div class="col-md-6 col-lg-4 col-sm-12">
				<div class="form-group">
					{!! Form::label('location_id', __('purchase.business_location').':') !!}
					{!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select')]); !!}
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-9 col-sm-12">
				<div class="form-group">
					<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-search"></i>
						</span>
						{!! Form::text('search_product', null, ['class' => 'form-control mousetrap', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'), 'disabled' => $search_disable]); !!}
					</div>
				</div>
			</div>
			<div class="col-md-3 col-sm-12">
				<div class="form-group">
					<button tabindex="-1" type="button" class="btn btn-link btn-modal tw-px-0" data-href="{{action([\App\Http\Controllers\ProductController::class, 'quickAdd'])}}" 
            	data-container=".quick_add_product_modal"><i class="fa fa-plus"></i> @lang( 'product.add_new_product' ) </button>
				</div>
			</div>
		</div>
	@endcomponent

	@component('components.widget', ['class' => 'box-solid'])
		<div class="row">
			<div class="col-sm-4">
				<div class="form-group">
					{!! Form::label('ref_no', __('purchase.ref_no').':') !!}
					@show_tooltip(__('lang_v1.leave_empty_to_autogenerate'))
					{!! Form::text('ref_no', null, ['class' => 'form-control']); !!}
				</div>
			</div>
			<div class="col-sm-4">
				<div class="form-group">
					{!! Form::label('delivery_date', __('lang_v1.required_by_date') . ':') !!}
					<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-calendar"></i>
						</span>
						{!! Form::text('delivery_date', null, ['class' => 'form-control', 'readonly']); !!}
					</div>
				</div>
			</div>
		</div>	
	@endcomponent

	@component('components.widget', ['class' => 'box-solid'])
		<div class="row">
			<div class="col-md-12">
				<table class="table" id="products_list">
					<thead>
						<tr>
							<th width="40%">@lang('sale.product')</th>
							<th width="20%">@lang('product.alert_quantity')</th>
							<th width="35%">@lang('lang_v1.required_quantity')</th>
							<th width="5%"><i class="text-danger fas fa-trash"></i></th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
	@endcomponent

	<div class="row">
		<div class="col-sm-12 text-center">
			<button type="button" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white" id="submit_pr_form">@lang('messages.save')</button>
		</div>
	</div>

{!! Form::close() !!}
</section>
@endsection

@section('javascript')
	<script type="text/javascript">
		$(document).ready( function(){
      		__page_leave_confirmation('#add_purchase_requisition_form');
      		$('#delivery_date').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true,
            });

			// Enable/disable search based on location selection
			function toggle_search_input() {
				var loc = $('#location_id').val();
				if (loc && loc.length > 0) {
					$('#search_product').prop('disabled', false);
				} else {
					$('#search_product').prop('disabled', true);
				}
			}
			toggle_search_input();

			// Product autocomplete like purchase order
			if($('#search_product').length > 0){
				$('#search_product').autocomplete({
					source: function(request, response){
						$.getJSON('/purchases/get_products', { location_id: $('#location_id').val(), term: request.term }, response);
					},
					minLength: 2,
					response: function(event, ui){
						if(ui.content.length == 1){
							ui.item = ui.content[0];
							$(this).data('ui-autocomplete')._trigger('select', 'autocompleteselect', ui);
							$(this).autocomplete('close');
						} else if (ui.content.length == 0){
							var term = $(this).data('ui-autocomplete').term;
							swal({
								title: LANG.no_products_found,
								text: __translate('add_name_as_new_product', { term: term }),
								buttons: [LANG.cancel, LANG.ok],
							}).then(value => {
								if(value){
									var container = $('.quick_add_product_modal');
									$.ajax({
										url: '/products/quick_add?product_name=' + term,
										dataType: 'html',
										success: function(result){
											$(container).html(result).modal('show');
										}
									});
								}
							});
						}
					},
					select: function(event, ui){
						$(this).val(null);
						get_requisition_product_row(ui.item.product_id, ui.item.variation_id);
					}
				}).autocomplete('instance')._renderItem = function(ul, item){
					return $('<li>')
						.append('<div>' + item.text + '</div>')
						.appendTo(ul);
				};
			}

			if($('#location_id').length){
				$('#location_id').on('change', function(){
					toggle_search_input();
				});
			}
    	});

		var prev_location;

		$('#location_id').on('select2:selecting', function(){
		    prev_location = $(this).val();
		})

		$('#location_id').on('select2:select', function(){
			if ($('#products_list tbody').find('tr').length > 0){
        		swal({
		            title: LANG.sure,
		            text: '{{__("lang_v1.all_added_products_will_be_removed")}}',
		            icon: 'warning',
		            buttons: true,
		            dangerMode: true,
		        }).then(willDelete => {
		            if (willDelete) {
		                $('#products_list tbody').html('');
		            } else {
		        		$('#location_id').val(prev_location);
		        		$('#location_id').change();
		        		return false;
		        	}
		        });
        	}
			// update search enabled state after select
			if($('#search_product').length){
				var loc = $('#location_id').val();
				$('#search_product').prop('disabled', !(loc && loc.length > 0));
			}
		});

    	$(document).on('click', 'button.remove_product_line', function(){
    		$(this).closest('tr').remove();
    	})

    	$(document).on('click', 'button#submit_pr_form', function(e){
    		e.preventDefault();
    		if ($('#products_list tbody').find('tr').length == 0){
    			toastr.warning(LANG.no_products_added);
    			return false;
    		}
    		if ($('form#add_purchase_requisition_form').valid()) {
    			$('form#add_purchase_requisition_form').submit();
    		}
    		
    	})

		// append requisition product row
		function get_requisition_product_row(product_id, variation_id){
			if(!product_id){ return; }
			var data = {
				product_id: product_id,
				variation_id: variation_id,
				location_id: $('#location_id').val()
			};
			$.ajax({
				method: 'POST',
				url: '{{ url('/purchase-requisition/get-product-row') }}',
				dataType: 'html',
				data: data,
				success: function(result){
					var row = $(result);
					var row_variation_id = row.attr('data-variation_id');
					if ($('tr[data-variation_id="' + row_variation_id + '"]').length == 0) {
						$('#products_list tbody').append(row);
					}
				}
			});
		}
	</script>
    <script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
@endsection

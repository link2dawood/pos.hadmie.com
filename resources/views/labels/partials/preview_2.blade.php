@php
    $cells_per_row = max((int) $barcode_details->stickers_in_one_row, 1);
    $remainder = count($page_products) % $cells_per_row;
@endphp

<div class="label-sheet" @if(!$barcode_details->is_continuous && $paper_height > 0) style="min-height: {{ $paper_height }}in;" @endif>
    <table align="center" class="label-sheet__table" style="border-spacing: {{ $barcode_details->col_distance * 1 }}in {{ $barcode_details->row_distance * 1 }}in;">
        @foreach($page_products as $page_product)
            @if($loop->index % $cells_per_row == 0)
                <tr>
            @endif

            <td class="label-sheet__cell" align="center" valign="center">
                @include('labels.partials.sticker', [
                    'page_product' => $page_product,
                    'print' => $print,
                    'business_name' => $business_name,
                    'barcode_details' => $barcode_details,
                ])
            </td>

            @if($loop->iteration % $cells_per_row == 0)
                </tr>
            @endif
        @endforeach

        @if($remainder !== 0)
            @for($i = $remainder; $i < $cells_per_row; $i++)
                <td class="label-sheet__cell"></td>
            @endfor
            </tr>
        @endif
    </table>
</div>

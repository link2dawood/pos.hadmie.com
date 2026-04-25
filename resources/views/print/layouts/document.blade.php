@php
    $sectionPartials = [
        'brand_header' => 'print.components.brand_header',
        'company' => 'print.components.company_block',
        'party' => 'print.components.party_block',
        'document_meta' => 'print.components.document_meta_block',
        'items' => 'print.components.items_table',
        'totals' => 'print.components.totals_section',
        'notes' => 'print.components.notes_terms_block',
        'terms' => 'print.components.notes_terms_block',
        'signatures' => 'print.components.signature_block',
        'codes' => 'print.components.codes_block',
        'footer' => 'print.components.footer_block',
    ];
@endphp
@if(!empty($standalone))
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @include('print.styles.tokens', ['document' => $document])
        @include('print.styles.standard', ['document' => $document])
        @include('print.styles.thermal', ['document' => $document])
    </style>
</head>
<body>
@else
<style>
    @include('print.styles.tokens', ['document' => $document])
    @include('print.styles.standard', ['document' => $document])
    @include('print.styles.thermal', ['document' => $document])
</style>
@endif
<div class="{{ $document['root_classes'] }}" @if(!empty($document['token_override_style'])) style="{{ $document['token_override_style'] }}" @endif>
    <div class="print-sheet">
        @foreach($document['section_order'] as $section)
            @if(!empty($document['sections'][$section]) && !empty($sectionPartials[$section]))
                @include($sectionPartials[$section], ['document' => $document, 'section' => $section])
            @endif
        @endforeach
    </div>
</div>
@if(!empty($standalone))
</body>
</html>
@endif

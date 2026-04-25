<section class="print-section print-brand">
    <div class="print-brand__meta">
        @if(!empty($document['brand']['name']))
            <p class="print-brand__name">{{ $document['brand']['name'] }}</p>
        @endif
        @if(!empty($document['brand']['title']))
            <p class="print-brand__title">{{ $document['brand']['title'] }}</p>
        @endif
        @if(!empty($document['brand']['subtitle_lines']))
            <div class="print-lines print-lines--muted">
                @foreach($document['brand']['subtitle_lines'] as $line)
                    <div>{!! $line !!}</div>
                @endforeach
            </div>
        @endif
    </div>
    @if(!empty($document['brand']['logo']))
        <img class="print-brand__logo" src="{{ $document['brand']['logo'] }}" alt="Logo">
    @endif
</section>

<section class="print-section print-card print-party">
    <p class="print-heading">{{ $document['party']['heading'] }}</p>
    <div class="print-party__name">{{ $document['party']['name'] }}</div>
    @if(!empty($document['party']['lines']))
        <div class="print-lines print-lines--muted" style="margin-top: var(--print-space-2);">
            @foreach($document['party']['lines'] as $line)
                <div>{!! $line !!}</div>
            @endforeach
        </div>
    @endif
    @if(!empty($document['party']['secondary_heading']) || !empty($document['party']['secondary_lines']))
        <div class="print-divider"></div>
        @if(!empty($document['party']['secondary_heading']))
            <p class="print-heading">{{ $document['party']['secondary_heading'] }}</p>
        @endif
        @if(!empty($document['party']['secondary_name']))
            <div class="print-party__name">{{ $document['party']['secondary_name'] }}</div>
        @endif
        @if(!empty($document['party']['secondary_lines']))
            <div class="print-lines print-lines--muted" style="margin-top: var(--print-space-2);">
                @foreach($document['party']['secondary_lines'] as $line)
                    <div>{!! $line !!}</div>
                @endforeach
            </div>
        @endif
    @endif
</section>

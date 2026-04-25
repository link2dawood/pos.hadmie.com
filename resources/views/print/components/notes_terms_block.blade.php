@php
    $content = $document[$section];
@endphp
<section class="print-section print-note-block">
    <p class="print-heading">{{ $content['title'] }}</p>
    <div class="print-lines print-lines--muted">
        @foreach($content['lines'] as $line)
            <div>{!! nl2br(e(strip_tags($line))) !!}</div>
        @endforeach
    </div>
</section>

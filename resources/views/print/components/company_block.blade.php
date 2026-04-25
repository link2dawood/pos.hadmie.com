<section class="print-section print-card">
    <p class="print-heading">{{ $document['company']['heading'] }}</p>
    <div class="print-lines">
        @foreach($document['company']['lines'] as $line)
            <div>{!! $line !!}</div>
        @endforeach
    </div>
</section>

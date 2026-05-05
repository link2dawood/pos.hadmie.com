<section class="print-section print-footer">
    <div class="print-lines">
        @foreach($document['footer']['lines'] as $line)
            <div>{!! $line !!}</div>
        @endforeach
    </div>
</section>

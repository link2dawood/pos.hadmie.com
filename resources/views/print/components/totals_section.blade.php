<section class="print-section">
    <div class="print-totals">
        @foreach($document['totals']['rows'] as $row)
            <div class="print-totals__row @if(!empty($row['emphasis'])) print-totals__row--strong @endif">
                <span>{{ $row['label'] }}</span>
                <span>{{ $row['value'] }}</span>
            </div>
        @endforeach
    </div>
</section>

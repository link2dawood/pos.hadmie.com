<section class="print-section">
    <div class="print-meta-grid">
        @foreach($document['document']['meta'] as $meta)
            <div class="print-card">
                <div class="print-meta__label">{{ $meta['label'] }}</div>
                <div class="print-meta__value-strong">{{ $meta['value'] }}</div>
            </div>
        @endforeach
    </div>
</section>

<section class="print-section">
    <p class="print-heading">{{ $document['signatures']['title'] }}</p>
    <div class="print-signatures">
        @foreach($document['signatures']['blocks'] as $block)
            <div>
                <div class="print-signature"></div>
                <div class="print-signature__label">{{ $block['label'] }}</div>
            </div>
        @endforeach
    </div>
</section>

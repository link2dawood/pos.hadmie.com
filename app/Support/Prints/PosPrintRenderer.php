<?php

namespace App\Support\Prints;

use App\InvoiceLayout;

class PosPrintRenderer
{
    public function __construct(protected PosPrintViewModelFactory $viewModelFactory)
    {
    }

    public function render(object $receiptDetails, ?InvoiceLayout $invoiceLayout, string $documentType = 'invoice', bool $standalone = false, array $overrides = []): array
    {
        $document = $this->viewModelFactory->make($receiptDetails, $invoiceLayout, $documentType, $overrides);
        $viewName = ($document['style_family'] === 'standard'
            ? 'print.templates.standard.'
            : 'print.templates.pos.')
            .$documentType;

        return [
            'document' => $document,
            'html' => view($viewName, [
                'document' => $document,
                'standalone' => $standalone,
            ])->render(),
        ];
    }
}

<?php

namespace App\Support\Prints;

use App\InvoiceLayout;
use App\Transaction;

class PurchaseOrderPrintRenderer
{
    public function __construct(protected PurchaseOrderPrintViewModelFactory $viewModelFactory)
    {
    }

    public function render(Transaction $purchase, ?InvoiceLayout $invoiceLayout, bool $standalone = false, array $overrides = []): array
    {
        $document = $this->viewModelFactory->make($purchase, $invoiceLayout, $overrides);

        return [
            'document' => $document,
            'html' => view('print.templates.standard.purchase_order', [
                'document' => $document,
                'standalone' => $standalone,
            ])->render(),
        ];
    }
}

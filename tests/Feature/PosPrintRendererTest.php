<?php

namespace Tests\Feature;

use App\InvoiceLayout;
use App\Support\Prints\PaperSizeResolver;
use App\Support\Prints\PosPrintRenderer;
use App\Support\Prints\PosPrintViewModelFactory;
use App\Support\Prints\PrintConfigResolver;
use stdClass;
use Tests\TestCase;

class PosPrintRendererTest extends TestCase
{
    public function test_it_renders_a4_invoice_with_shared_sections(): void
    {
        $renderer = $this->makeRenderer();
        $invoiceLayout = new InvoiceLayout([
            'design' => 'classic',
            'common_settings' => [
                'print' => [
                    'paper_size' => 'a4',
                    'standard_template' => 'ledger',
                ],
            ],
        ]);

        $rendered = $renderer->render($this->makeReceiptDetails(), $invoiceLayout, 'invoice');

        $this->assertStringContainsString('print-paper-a4', $rendered['html']);
        $this->assertStringContainsString('print-style-standard', $rendered['html']);
        $this->assertStringContainsString('Notes', $rendered['html']);
        $this->assertStringContainsString('Served By: Alex Cashier', $rendered['html']);
        $this->assertStringContainsString('data:image/png;base64,', $rendered['html']);
    }

    public function test_it_renders_thermal_receipts_for_80mm_and_58mm(): void
    {
        $renderer = $this->makeRenderer();

        $thermal80 = $renderer->render($this->makeReceiptDetails(), new InvoiceLayout([
            'design' => 'slim',
            'common_settings' => [
                'print' => [
                    'thermal_template' => 'clean',
                ],
            ],
        ]), 'invoice');

        $thermal58 = $renderer->render($this->makeReceiptDetails(), new InvoiceLayout([
            'design' => 'slim2',
            'common_settings' => [
                'print' => [
                    'thermal_template' => 'compact',
                ],
            ],
        ]), 'invoice');

        $this->assertStringContainsString('print-paper-thermal-80', $thermal80['html']);
        $this->assertStringContainsString('print-style-thermal', $thermal80['html']);
        $this->assertStringContainsString('print-paper-thermal-58', $thermal58['html']);
        $this->assertStringContainsString('print-variant-compact', $thermal58['html']);
        $this->assertStringNotContainsString('<th>#</th>', $thermal80['html']);
    }

    public function test_it_renders_standalone_delivery_note_with_signature_blocks(): void
    {
        $renderer = $this->makeRenderer();
        $invoiceLayout = new InvoiceLayout([
            'design' => 'classic',
            'common_settings' => [
                'print' => [
                    'paper_size' => 'a4',
                ],
            ],
        ]);

        $rendered = $renderer->render($this->makeReceiptDetails(), $invoiceLayout, 'delivery_note', true);

        $this->assertStringContainsString('<!DOCTYPE html>', $rendered['html']);
        $this->assertStringContainsString('Customer Signature', $rendered['html']);
        $this->assertStringContainsString('Driver Signature', $rendered['html']);
    }

    public function test_it_renders_standard_quotation_template_without_signature_block_by_default(): void
    {
        $renderer = $this->makeRenderer();
        $invoiceLayout = new InvoiceLayout([
            'design' => 'classic',
            'common_settings' => [
                'print' => [
                    'paper_size' => 'a4',
                    'standard_template' => 'ledger',
                ],
            ],
        ]);

        $receipt = $this->makeReceiptDetails();
        $receipt->invoice_heading = 'Quotation';

        $rendered = $renderer->render($receipt, $invoiceLayout, 'quotation');

        $this->assertStringContainsString('print-template-quotation', $rendered['html']);
        $this->assertStringContainsString('Quotation', $rendered['html']);
        $this->assertStringNotContainsString('Authorized Signature', $rendered['html']);
    }

    private function makeRenderer(): PosPrintRenderer
    {
        return new PosPrintRenderer(
            new PosPrintViewModelFactory(
                new PrintConfigResolver(
                    new PaperSizeResolver()
                )
            )
        );
    }

    private function makeReceiptDetails(): object
    {
        $receiptDetails = new stdClass();
        $receiptDetails->logo = 'https://example.com/logo.png';
        $receiptDetails->display_name = 'Hadmie POS';
        $receiptDetails->business_name = 'Hadmie POS';
        $receiptDetails->invoice_heading = 'Tax Invoice';
        $receiptDetails->header_text = 'Premium receipt output';
        $receiptDetails->sub_heading_line1 = 'Main Street Branch';
        $receiptDetails->address = 'Plot 17, Main Street, Karachi';
        $receiptDetails->contact = '<b>Mobile:</b> 0300-0000000';
        $receiptDetails->website = 'https://pos.hadmie.com';
        $receiptDetails->location_custom_fields = 'NTN 123';
        $receiptDetails->tax_label1 = 'NTN:';
        $receiptDetails->tax_info1 = '1234567-8';
        $receiptDetails->customer_label = 'Customer';
        $receiptDetails->customer_name = 'Aisha Khan';
        $receiptDetails->customer_info = 'Suite 2, Clifton';
        $receiptDetails->customer_custom_fields = 'Membership: Gold';
        $receiptDetails->client_id_label = 'Client ID';
        $receiptDetails->client_id = 'C-102';
        $receiptDetails->customer_tax_label = 'Tax No';
        $receiptDetails->customer_tax_number = '998877';
        $receiptDetails->invoice_no_prefix = 'Invoice No.';
        $receiptDetails->invoice_no = 'INV-1001';
        $receiptDetails->date_label = 'Date';
        $receiptDetails->invoice_date = '2026-04-22 12:30';
        $receiptDetails->sold_by = 'Alex Cashier';
        $receiptDetails->table_product_label = 'Item';
        $receiptDetails->table_qty_label = 'Qty';
        $receiptDetails->table_unit_price_label = 'Unit Price';
        $receiptDetails->table_subtotal_label = 'Subtotal';
        $receiptDetails->item_discount_label = 'Discount';
        $receiptDetails->show_cat_code = 0;
        $receiptDetails->hide_price = false;
        $receiptDetails->lines = [
            [
                'name' => 'Espresso Beans',
                'product_variation' => '500g',
                'variation' => '',
                'quantity' => '2',
                'quantity_uf' => 2,
                'units' => 'bag',
                'unit_price_before_discount' => '1,200.00',
                'line_total_exc_tax' => '2,400.00',
                'total_line_discount' => '50.00',
                'product_description' => 'Dark roast blend',
                'sell_line_note' => 'Grind coarse',
                'modifiers' => [],
            ],
        ];
        $receiptDetails->subtotal_label = 'Subtotal:';
        $receiptDetails->subtotal = '2,400.00';
        $receiptDetails->taxes = ['GST' => '360.00'];
        $receiptDetails->discount_label = 'Discount';
        $receiptDetails->discount = '50.00';
        $receiptDetails->total_label = 'Total:';
        $receiptDetails->total = '2,710.00';
        $receiptDetails->total_paid_label = 'Paid';
        $receiptDetails->total_paid = '2,000.00';
        $receiptDetails->total_due_label = 'Due';
        $receiptDetails->total_due = '710.00';
        $receiptDetails->additional_notes = 'Thank you for your business.';
        $receiptDetails->shipping_details = 'Handle with care.';
        $receiptDetails->footer_text = 'Exchange within 7 days with receipt.';
        $receiptDetails->show_barcode = true;
        $receiptDetails->barcode = 'INV-1001';
        $receiptDetails->show_qr_code = true;
        $receiptDetails->qr_code_text = 'INV-1001|2710.00';
        $receiptDetails->shipping_address = 'Warehouse 5, DHA, Karachi';
        $receiptDetails->ref_no = 'SO-22';

        return $receiptDetails;
    }
}

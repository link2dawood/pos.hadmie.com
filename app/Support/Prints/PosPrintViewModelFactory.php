<?php

namespace App\Support\Prints;

use App\InvoiceLayout;
use Illuminate\Support\Str;

class PosPrintViewModelFactory
{
    public function __construct(protected PrintConfigResolver $configResolver)
    {
    }

    public function make(object $receiptDetails, ?InvoiceLayout $invoiceLayout, string $documentType = 'invoice', array $overrides = []): array
    {
        $config = $this->configResolver->resolve($invoiceLayout, array_merge($overrides, [
            'document_type' => $documentType,
        ]));
        $paperProfile = $config['paper_size_profile'];
        $sections = $config['sections'];
        $visibility = $config['document_visibility'] ?? [];

        $document = [
            'document_type' => $documentType,
            'paper_size' => $config['paper_size'],
            'style_family' => $config['style_family'],
            'template_variant' => $config['template_variant'],
            'section_order' => $config['section_order'],
            'sections' => $sections,
            'paper_profile' => $paperProfile,
            'root_classes' => implode(' ', [
                'print-document',
                'print-paper-'.$config['paper_size'],
                'print-style-'.$config['style_family'],
                'print-template-'.$documentType,
                'print-variant-'.$config['template_variant'],
            ]),
            'token_override_style' => $this->tokenOverrideStyle($config['tokens'] ?? []),
            'brand' => $this->buildBrand($receiptDetails, $documentType),
            'company' => $this->buildCompany($receiptDetails),
            'party' => $this->buildParty($receiptDetails, $documentType),
            'document' => $this->buildDocumentMeta($receiptDetails, $documentType),
            'items' => $this->buildItems($receiptDetails, $documentType, $config),
            'totals' => $this->buildTotals($receiptDetails, $documentType, $visibility),
            'notes' => $this->buildNotes($receiptDetails),
            'terms' => $this->buildTerms($receiptDetails, $documentType),
            'signatures' => $this->buildSignatures($documentType, $visibility),
            'codes' => $this->buildCodes($receiptDetails, $paperProfile),
            'footer' => $this->buildFooter($receiptDetails),
            'config' => $config,
        ];

        if (empty($visibility['recipient_shipping'])) {
            $document['sections']['party'] = false;
        }
        if (empty($visibility['totals'])) {
            $document['sections']['totals'] = false;
        }
        if (empty($visibility['notes'])) {
            $document['sections']['notes'] = false;
        }
        if (empty($document['notes']['lines'])) {
            $document['sections']['notes'] = false;
        }
        if (empty($document['terms']['lines'])) {
            $document['sections']['terms'] = false;
        }
        if (empty($document['signatures']['blocks'])) {
            $document['sections']['signatures'] = false;
        }
        if (! $document['codes']['show_barcode'] && ! $document['codes']['show_qr_code']) {
            $document['sections']['codes'] = false;
        }
        if (empty($document['footer']['lines'])) {
            $document['sections']['footer'] = false;
        }

        return $document;
    }

    protected function buildBrand(object $receiptDetails, string $documentType): array
    {
        $titleMap = [
            'invoice' => $receiptDetails->invoice_heading ?: 'Invoice',
            'packing_slip' => 'Packing Slip',
            'delivery_note' => 'Delivery Note',
            'quotation' => $receiptDetails->invoice_heading ?: 'Quotation',
            'sale_order' => $receiptDetails->invoice_heading ?: 'Sales Order',
        ];

        return [
            'logo' => $receiptDetails->logo ?? null,
            'name' => $receiptDetails->display_name ?: ($receiptDetails->business_name ?? ''),
            'title' => $titleMap[$documentType] ?? 'Invoice',
            'subtitle_lines' => array_values(array_filter([
                $receiptDetails->header_text ?? null,
                $receiptDetails->sub_heading_line1 ?? null,
                $receiptDetails->sub_heading_line2 ?? null,
                $receiptDetails->sub_heading_line3 ?? null,
                $receiptDetails->sub_heading_line4 ?? null,
                $receiptDetails->sub_heading_line5 ?? null,
            ])),
        ];
    }

    protected function buildCompany(object $receiptDetails): array
    {
        $taxInfo = trim(implode(' ', array_filter([
            $receiptDetails->tax_label1 ?? null,
            $receiptDetails->tax_info1 ?? null,
            $receiptDetails->tax_label2 ?? null,
            $receiptDetails->tax_info2 ?? null,
        ])));

        return [
            'heading' => 'Company',
            'lines' => array_values(array_filter([
                $receiptDetails->address ?? null,
                $receiptDetails->contact ?? null,
                $receiptDetails->website ?? null,
                $receiptDetails->location_custom_fields ?? null,
                $taxInfo ?: null,
            ])),
        ];
    }

    protected function buildParty(object $receiptDetails, string $documentType): array
    {
        $headingMap = [
            'invoice' => $receiptDetails->customer_label ?: 'Customer',
            'packing_slip' => 'Ship To',
            'delivery_note' => 'Deliver To',
            'quotation' => $receiptDetails->customer_label ?: 'Customer',
            'sale_order' => $receiptDetails->customer_label ?: 'Customer',
        ];

        return [
            'heading' => $headingMap[$documentType] ?? 'Customer',
            'name' => $receiptDetails->customer_name ?? 'Walk-in Customer',
            'lines' => array_values(array_filter([
                $receiptDetails->customer_info ?? null,
                $receiptDetails->customer_custom_fields ?? null,
                ! empty($receiptDetails->client_id) ? trim(($receiptDetails->client_id_label ?? 'Client ID').': '.$receiptDetails->client_id) : null,
                ! empty($receiptDetails->customer_tax_number) ? trim(($receiptDetails->customer_tax_label ?? 'Tax No').': '.$receiptDetails->customer_tax_number) : null,
            ])),
            'secondary_heading' => ! empty($receiptDetails->shipping_address) ? ($documentType === 'delivery_note' ? 'Shipping Details' : 'Shipping Address') : null,
            'secondary_name' => null,
            'secondary_lines' => array_values(array_filter([
                $receiptDetails->shipping_address ?? null,
            ])),
        ];
    }

    protected function buildDocumentMeta(object $receiptDetails, string $documentType): array
    {
        $meta = [
            [
                'label' => $receiptDetails->invoice_no_prefix ?: 'Document No.',
                'value' => $receiptDetails->invoice_no ?? '',
            ],
            [
                'label' => $receiptDetails->date_label ?: 'Date',
                'value' => $receiptDetails->invoice_date ?? '',
            ],
        ];

        if (! empty($receiptDetails->ref_no) && in_array($documentType, ['packing_slip', 'delivery_note'], true)) {
            $meta[] = [
                'label' => 'Order No.',
                'value' => $receiptDetails->ref_no,
            ];
        }

        if (! empty($receiptDetails->due_date)) {
            $meta[] = [
                'label' => $receiptDetails->due_date_label ?: 'Due Date',
                'value' => $receiptDetails->due_date,
            ];
        }

        if (! empty($receiptDetails->sold_by)) {
            $meta[] = [
                'label' => 'Served By',
                'value' => $receiptDetails->sold_by,
            ];
        }

        return [
            'title' => $receiptDetails->invoice_heading ?: ucfirst(str_replace('_', ' ', $documentType)),
            'meta' => $meta,
        ];
    }

    protected function buildItems(object $receiptDetails, string $documentType, array $config): array
    {
        $paperSize = $config['paper_size'];
        $visibility = $config['document_visibility'] ?? [];
        $hidePrice = ! empty($receiptDetails->hide_price) || empty($visibility['prices']);
        $templateVariant = $config['template_variant'] ?? 'ledger';
        $rows = [];

        foreach ($receiptDetails->lines ?? [] as $index => $line) {
            $descriptionLines = array_values(array_filter([
                $line['product_description'] ?? null,
                $line['sell_line_note'] ?? null,
                $line['product_custom_fields'] ?? null,
                $line['warranty_name'] ?? null,
            ]));

            if (! empty($line['modifiers'])) {
                foreach ($line['modifiers'] as $modifier) {
                    $descriptionLines[] = trim('Modifier: '.implode(' ', array_filter([
                        $modifier['name'] ?? null,
                        $modifier['variation'] ?? null,
                    ])));
                }
            }

            $rows[] = [
                'index' => $index + 1,
                'item' => trim(implode(' ', array_filter([
                    $line['name'] ?? null,
                    $line['product_variation'] ?? null,
                    $line['variation'] ?? null,
                ]))),
                'cat_code' => $line['cat_code'] ?? null,
                'quantity' => trim(implode(' ', array_filter([
                    $line['quantity'] ?? null,
                    $line['units'] ?? ($line['unit'] ?? null),
                ]))),
                'unit_price' => $line['unit_price_before_discount'] ?? null,
                'discount' => $line['total_line_discount'] ?? ($line['line_discount_amount'] ?? null),
                'subtotal' => $line['line_total_exc_tax'] ?? ($line['line_total'] ?? null),
                'status' => $documentType === 'packing_slip' ? 'Packed' : ($documentType === 'delivery_note' ? 'Delivered' : null),
                'description_lines' => $descriptionLines,
            ];
        }

        $columns = [];
        if ($paperSize === 'a4') {
            $columns[] = ['key' => 'index', 'label' => '#', 'align' => 'left'];
        }
        $columns[] = ['key' => 'item', 'label' => $receiptDetails->table_product_label ?? 'Item', 'align' => 'left'];

        if (! empty($receiptDetails->show_cat_code) && $documentType === 'invoice' && $paperSize === 'a4') {
            $columns[] = ['key' => 'cat_code', 'label' => $receiptDetails->cat_code_label ?? 'Code', 'align' => 'left'];
        }

        $columns[] = ['key' => 'quantity', 'label' => $receiptDetails->table_qty_label ?? 'Qty', 'align' => 'right'];

        if (in_array($documentType, ['invoice', 'quotation', 'sale_order'], true) && ! $hidePrice) {
            if ($paperSize === 'a4') {
                $columns[] = ['key' => 'unit_price', 'label' => $receiptDetails->table_unit_price_label ?? 'Unit Price', 'align' => 'right'];
                if (! empty($receiptDetails->item_discount_label)) {
                    $columns[] = ['key' => 'discount', 'label' => $receiptDetails->item_discount_label, 'align' => 'right'];
                }
            } elseif ($templateVariant === 'clean') {
                $columns[] = ['key' => 'subtotal', 'label' => $receiptDetails->table_subtotal_label ?? 'Subtotal', 'align' => 'right'];
            }

            if ($paperSize === 'a4' || $templateVariant === 'compact') {
                $columns[] = ['key' => 'subtotal', 'label' => $receiptDetails->table_subtotal_label ?? 'Subtotal', 'align' => 'right'];
            }
        }

        if (in_array($documentType, ['packing_slip', 'delivery_note'], true)) {
            $columns[] = ['key' => 'status', 'label' => 'Status', 'align' => 'center'];
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'empty_state' => 'No items',
        ];
    }

    protected function buildTotals(object $receiptDetails, string $documentType, array $visibility = []): array
    {
        $rows = [];

        if (in_array($documentType, ['packing_slip', 'delivery_note'], true)) {
            $totalQuantity = 0;
            foreach ($receiptDetails->lines ?? [] as $line) {
                $totalQuantity += (float) ($line['quantity_uf'] ?? $line['quantity'] ?? 0);
            }

            $rows[] = ['label' => 'Total Items', 'value' => (string) count($receiptDetails->lines ?? []), 'emphasis' => false];
            $rows[] = ['label' => 'Total Quantity', 'value' => (string) $totalQuantity, 'emphasis' => false];
            if (! empty($receiptDetails->total)) {
                $rows[] = ['label' => 'Order Total', 'value' => $receiptDetails->total, 'emphasis' => true];
            }

            return ['rows' => $rows];
        }

        if (! empty($receiptDetails->subtotal)) {
            $rows[] = ['label' => trim(strip_tags($receiptDetails->subtotal_label ?? 'Subtotal')), 'value' => $receiptDetails->subtotal, 'emphasis' => false];
        }

        if (! empty($visibility['tax'])) {
            foreach ((array) ($receiptDetails->taxes ?? []) as $label => $value) {
                $rows[] = ['label' => $label, 'value' => $value, 'emphasis' => false];
            }
        }

        if (! empty($receiptDetails->discount)) {
            $rows[] = ['label' => trim(strip_tags($receiptDetails->discount_label ?? 'Discount')), 'value' => $receiptDetails->discount, 'emphasis' => false];
        }

        if (! empty($receiptDetails->shipping_charges)) {
            $rows[] = ['label' => $receiptDetails->shipping_charges_label ?? 'Shipping Charges', 'value' => $receiptDetails->shipping_charges, 'emphasis' => false];
        }

        if (! empty($receiptDetails->packing_charge)) {
            $rows[] = ['label' => $receiptDetails->packing_charge_label ?? 'Packing Charge', 'value' => $receiptDetails->packing_charge, 'emphasis' => false];
        }

        if (! empty($receiptDetails->round_off_amount)) {
            $rows[] = ['label' => trim(strip_tags($receiptDetails->round_off_label ?? 'Round Off')), 'value' => $receiptDetails->round_off, 'emphasis' => false];
        }

        if (! empty($receiptDetails->total)) {
            $rows[] = ['label' => trim(strip_tags($receiptDetails->total_label ?? 'Total')), 'value' => $receiptDetails->total, 'emphasis' => true];
        }

        if (isset($receiptDetails->total_paid)) {
            $rows[] = ['label' => trim(strip_tags($receiptDetails->total_paid_label ?? 'Total Paid')), 'value' => $receiptDetails->total_paid, 'emphasis' => false];
        }

        if (isset($receiptDetails->total_due)) {
            $rows[] = ['label' => trim(strip_tags($receiptDetails->total_due_label ?? 'Total Due')), 'value' => $receiptDetails->total_due, 'emphasis' => true];
        }

        return ['rows' => $rows];
    }

    protected function buildNotes(object $receiptDetails): array
    {
        return [
            'title' => 'Notes',
            'lines' => array_values(array_filter([
                $receiptDetails->additional_notes ?? null,
            ])),
        ];
    }

    protected function buildTerms(object $receiptDetails, string $documentType): array
    {
        $lines = [];

        if (! empty($receiptDetails->shipping_details)) {
            $lines[] = $receiptDetails->shipping_details;
        }

        if ($documentType === 'delivery_note') {
            $lines[] = 'Please check and sign for all delivered items on receipt.';
        }

        return [
            'title' => $documentType === 'delivery_note' ? 'Delivery Terms' : 'Terms',
            'lines' => $lines,
        ];
    }

    protected function buildSignatures(string $documentType, array $visibility = []): array
    {
        if (empty($visibility['signatures'])) {
            return [
                'title' => 'Signatures',
                'blocks' => [],
            ];
        }

        if ($documentType === 'delivery_note') {
            return [
                'title' => 'Signatures',
                'blocks' => [
                    ['label' => 'Customer Signature'],
                    ['label' => 'Driver Signature'],
                ],
            ];
        }

        return [
            'title' => 'Signatures',
            'blocks' => [
                ['label' => $documentType === 'packing_slip' ? 'Packed By' : 'Authorized Signature'],
                ['label' => $documentType === 'packing_slip' ? 'Received By' : 'Recipient Signature'],
            ],
        ];
    }

    protected function buildCodes(object $receiptDetails, array $paperProfile): array
    {
        $priceLabel = trim(strip_tags($receiptDetails->total_label ?? 'Total'));

        return [
            'show_barcode' => ! empty($receiptDetails->show_barcode) && ! empty($receiptDetails->barcode),
            'barcode_value' => $receiptDetails->barcode ?? null,
            'show_qr_code' => ! empty($receiptDetails->show_qr_code) && (! empty($receiptDetails->qr_code_text) || ! empty($receiptDetails->qr_code)),
            'qr_value' => $receiptDetails->qr_code_text ?? null,
            'qr_image' => $receiptDetails->qr_code ?? null,
            'price_label' => $priceLabel !== '' ? $priceLabel : 'Total',
            'price_value' => $receiptDetails->total ?? null,
            'barcode_scale' => $paperProfile['barcode_scale'],
            'barcode_height' => $paperProfile['barcode_height'],
            'qr_size' => $paperProfile['qr_size'],
        ];
    }

    protected function buildFooter(object $receiptDetails): array
    {
        $lines = array_values(array_filter([
            ! empty($receiptDetails->sold_by) ? 'Served By: '.$receiptDetails->sold_by : null,
            $receiptDetails->footer_text ?? null,
        ]));

        return [
            'lines' => $lines,
        ];
    }

    protected function tokenOverrideStyle(array $tokens): string
    {
        $styles = [];
        foreach ($tokens as $key => $value) {
            if (! is_scalar($value) || $value === '') {
                continue;
            }

            $cssKey = Str::startsWith((string) $key, '--') ? $key : '--print-'.Str::of((string) $key)->replace('_', '-');
            $styles[] = $cssKey.': '.$value;
        }

        return implode('; ', $styles);
    }
}

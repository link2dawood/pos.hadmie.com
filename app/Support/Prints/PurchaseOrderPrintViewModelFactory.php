<?php

namespace App\Support\Prints;

use App\InvoiceLayout;
use App\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PurchaseOrderPrintViewModelFactory
{
    public function __construct(protected PrintConfigResolver $configResolver)
    {
    }

    public function make(Transaction $purchase, ?InvoiceLayout $invoiceLayout, array $overrides = []): array
    {
        $config = $this->configResolver->resolve($invoiceLayout, array_merge($overrides, [
            'document_type' => 'purchase_order',
            'paper_size' => $overrides['paper_size'] ?? 'a4',
        ]));
        $paperProfile = $config['paper_size_profile'];
        $visibility = $config['document_visibility'] ?? [];

        $supplierLines = array_values(array_filter([
            $purchase->contact->supplier_business_name ?? null,
            $purchase->contact->mobile ?? null,
            $purchase->contact->email ?? null,
            $purchase->contact->address_line_1 ?? null,
            $purchase->contact->address_line_2 ?? null,
            trim(implode(', ', array_filter([
                $purchase->contact->city ?? null,
                $purchase->contact->state ?? null,
                $purchase->contact->country ?? null,
                $purchase->contact->zip_code ?? null,
            ]))),
            ! empty($purchase->contact->tax_number) ? 'Tax No: '.$purchase->contact->tax_number : null,
        ]));

        $deliveryLines = array_values(array_filter([
            $purchase->shipping_address ?? null,
            $purchase->shipping_details ?? null,
            $purchase->delivered_to ?? null,
            ! empty($purchase->delivery_date) ? __('purchase.delivery_date').': '.$this->formatDate($purchase->delivery_date) : null,
        ]));

        $items = [];
        foreach ($purchase->purchase_lines as $index => $line) {
            $items[] = [
                'index' => $index + 1,
                'item' => trim(implode(' ', array_filter([
                    $line->product->name ?? null,
                    ! empty($line->variations) && ! empty($line->variations->product_variation) && ! $line->variations->product_variation->is_dummy ? $line->variations->product_variation->name : null,
                    ! empty($line->variations) && $line->variations->name !== 'DUMMY' ? $line->variations->name : null,
                ]))),
                'quantity' => trim(implode(' ', array_filter([
                    $this->formatQuantity($line->quantity),
                    $line->sub_unit->short_name ?? $line->product->unit->short_name ?? null,
                ]))),
                'unit_price' => $this->formatMoney($line->pp_without_discount),
                'tax' => $this->formatMoney($line->item_tax),
                'subtotal' => $this->formatMoney($line->purchase_price_inc_tax * $line->quantity),
                'description_lines' => array_values(array_filter([
                    $line->item_description ?? null,
                    ! empty($line->product->sku) ? 'SKU: '.$line->product->sku : null,
                ])),
            ];
        }

        $document = [
            'document_type' => 'purchase_order',
            'paper_size' => $config['paper_size'],
            'style_family' => $config['style_family'],
            'template_variant' => $config['template_variant'],
            'section_order' => $config['section_order'],
            'sections' => $config['sections'],
            'paper_profile' => $paperProfile,
            'root_classes' => implode(' ', [
                'print-document',
                'print-paper-'.$config['paper_size'],
                'print-style-'.$config['style_family'],
                'print-template-purchase_order',
                'print-variant-'.$config['template_variant'],
            ]),
            'token_override_style' => $this->tokenOverrideStyle($config['tokens'] ?? []),
            'brand' => [
                'logo' => ! empty($invoiceLayout?->logo) && file_exists(public_path('uploads/invoice_logos/'.$invoiceLayout->logo))
                    ? asset('uploads/invoice_logos/'.$invoiceLayout->logo)
                    : null,
                'name' => $purchase->business->name ?? ($purchase->location->name ?? ''),
                'title' => __('lang_v1.purchase_order'),
                'subtitle_lines' => array_values(array_filter([
                    $purchase->location->name ?? null,
                    $purchase->location->mobile ?? null,
                ])),
            ],
            'company' => [
                'heading' => __('business.business'),
                'lines' => array_values(array_filter([
                    $purchase->location->name ?? null,
                    $purchase->location->landmark ?? null,
                    trim(implode(', ', array_filter([
                        $purchase->location->city ?? null,
                        $purchase->location->state ?? null,
                        $purchase->location->country ?? null,
                        $purchase->location->zip_code ?? null,
                    ]))),
                    $purchase->location->mobile ?? null,
                    $purchase->location->email ?? null,
                ])),
            ],
            'party' => [
                'heading' => __('purchase.supplier'),
                'name' => $purchase->contact->name ?? '',
                'lines' => $supplierLines,
                'secondary_heading' => __('lang_v1.shipping_address'),
                'secondary_name' => $purchase->delivered_to ?? '',
                'secondary_lines' => $deliveryLines,
            ],
            'document' => [
                'title' => __('lang_v1.purchase_order'),
                'meta' => array_values(array_filter([
                    ['label' => __('purchase.ref_no'), 'value' => $purchase->ref_no],
                    ['label' => __('purchase.purchase_date'), 'value' => $this->formatDateTime($purchase->transaction_date)],
                    ! empty($purchase->status) ? ['label' => __('sale.status'), 'value' => ucfirst(str_replace('_', ' ', $purchase->status))] : null,
                    ! empty($purchase->shipping_status) ? ['label' => __('lang_v1.shipping_status'), 'value' => ucfirst(str_replace('_', ' ', $purchase->shipping_status))] : null,
                ])),
            ],
            'items' => [
                'columns' => array_values(array_filter([
                    ['key' => 'index', 'label' => '#', 'align' => 'left'],
                    ['key' => 'item', 'label' => __('sale.product'), 'align' => 'left'],
                    ['key' => 'quantity', 'label' => __('sale.qty'), 'align' => 'right'],
                    ! empty($visibility['prices']) ? ['key' => 'unit_price', 'label' => __('sale.unit_price'), 'align' => 'right'] : null,
                    ! empty($visibility['tax']) ? ['key' => 'tax', 'label' => __('sale.tax'), 'align' => 'right'] : null,
                    ! empty($visibility['prices']) ? ['key' => 'subtotal', 'label' => __('sale.subtotal'), 'align' => 'right'] : null,
                ])),
                'rows' => $items,
                'empty_state' => 'No items',
            ],
            'totals' => [
                'rows' => array_values(array_filter([
                    ! empty($visibility['prices']) ? ['label' => __('sale.subtotal'), 'value' => $this->formatMoney($purchase->total_before_tax), 'emphasis' => false] : null,
                    ! empty($visibility['tax']) ? ['label' => __('sale.order_tax'), 'value' => $this->formatMoney($purchase->tax_amount), 'emphasis' => false] : null,
                    ! empty($purchase->shipping_charges) ? ['label' => __('sale.shipping_charges'), 'value' => $this->formatMoney($purchase->shipping_charges), 'emphasis' => false] : null,
                    ! empty($visibility['totals']) ? ['label' => __('sale.total'), 'value' => $this->formatMoney($purchase->final_total), 'emphasis' => true] : null,
                ])),
            ],
            'notes' => [
                'title' => 'Notes',
                'lines' => array_values(array_filter([$purchase->additional_notes ?? null])),
            ],
            'terms' => [
                'title' => 'Terms',
                'lines' => array_values(array_filter([$purchase->shipping_details ?? null])),
            ],
            'signatures' => [
                'title' => 'Signatures',
                'blocks' => ! empty($visibility['signatures']) ? [
                    ['label' => 'Prepared By'],
                    ['label' => 'Supplier Confirmation'],
                ] : [],
            ],
            'codes' => [
                'show_barcode' => false,
                'barcode_value' => null,
                'show_qr_code' => false,
                'qr_value' => null,
                'qr_image' => null,
                'barcode_scale' => $paperProfile['barcode_scale'],
                'barcode_height' => $paperProfile['barcode_height'],
                'qr_size' => $paperProfile['qr_size'],
            ],
            'footer' => [
                'lines' => array_values(array_filter([
                    ! empty($purchase->pay_term_number) && ! empty($purchase->pay_term_type)
                        ? __('purchase.pay_term').': '.$purchase->pay_term_number.' '.$purchase->pay_term_type
                        : null,
                ])),
            ],
            'config' => $config,
        ];

        if (empty($visibility['recipient_shipping'])) {
            $document['sections']['party'] = false;
        }
        if (empty($visibility['totals']) || empty($document['totals']['rows'])) {
            $document['sections']['totals'] = false;
        }
        if (empty($visibility['notes']) || empty($document['notes']['lines'])) {
            $document['sections']['notes'] = false;
        }
        if (empty($document['terms']['lines'])) {
            $document['sections']['terms'] = false;
        }
        if (empty($document['signatures']['blocks'])) {
            $document['sections']['signatures'] = false;
        }
        $document['sections']['codes'] = false;
        if (empty($document['footer']['lines'])) {
            $document['sections']['footer'] = false;
        }

        return $document;
    }

    protected function formatMoney($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', ',');
    }

    protected function formatQuantity($value): string
    {
        $formatted = number_format((float) $value, 2, '.', ',');

        return rtrim(rtrim($formatted, '0'), '.');
    }

    protected function formatDate($value): string
    {
        return Carbon::parse($value)->format('Y-m-d');
    }

    protected function formatDateTime($value): string
    {
        return Carbon::parse($value)->format('Y-m-d H:i');
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

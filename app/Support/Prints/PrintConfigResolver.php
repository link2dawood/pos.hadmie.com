<?php

namespace App\Support\Prints;

use App\InvoiceLayout;

class PrintConfigResolver
{
    public function __construct(protected PaperSizeResolver $paperSizeResolver)
    {
    }

    public function resolve(?InvoiceLayout $invoiceLayout, array $overrides = []): array
    {
        $commonSettings = is_array($invoiceLayout?->common_settings) ? $invoiceLayout->common_settings : [];
        $printSettings = is_array(data_get($commonSettings, 'print')) ? data_get($commonSettings, 'print') : [];
        $legacyDesign = $invoiceLayout?->design ?? 'classic';
        $documentType = $overrides['document_type'] ?? 'invoice';
        $paperProfile = $this->paperSizeResolver->resolve(data_get($printSettings, 'paper_size'), $legacyDesign);

        $defaults = [
            'legacy_design' => $legacyDesign,
            'paper_size' => $paperProfile['paper_size'],
            'style_family' => $paperProfile['style_family'],
            'standard_template' => 'ledger',
            'thermal_template' => 'clean',
            'template_variant' => $paperProfile['style_family'] === 'thermal' ? 'clean' : 'ledger',
            'section_order' => [
                'brand_header',
                'company',
                'party',
                'document_meta',
                'items',
                'totals',
                'notes',
                'terms',
                'signatures',
                'codes',
                'footer',
            ],
            'sections' => [
                'brand_header' => true,
                'company' => true,
                'party' => true,
                'document_meta' => true,
                'items' => true,
                'totals' => true,
                'notes' => true,
                'terms' => true,
                'signatures' => true,
                'codes' => true,
                'footer' => true,
            ],
            'items_table' => [
                'preset' => $paperProfile['default_items_preset'],
                'show_line_notes' => true,
                'show_product_description' => true,
            ],
            'documents' => $this->documentDefaults(),
            'tokens' => [],
        ];

        $resolved = array_replace_recursive($defaults, $printSettings, $overrides);

        if (is_string($resolved['section_order'] ?? null)) {
            $resolved['section_order'] = array_values(array_filter(array_map('trim', explode(',', $resolved['section_order']))));
        }

        $resolved['paper_size_profile'] = $this->paperSizeResolver->resolve($resolved['paper_size'] ?? null, $legacyDesign);
        $resolved['paper_size'] = $resolved['paper_size_profile']['paper_size'];

        if (empty($resolved['style_family'])) {
            $resolved['style_family'] = $resolved['paper_size_profile']['style_family'];
        }

        if (empty($resolved['template_variant'])) {
            $resolved['template_variant'] = $resolved['style_family'] === 'thermal'
                ? ($resolved['thermal_template'] ?? 'clean')
                : ($resolved['standard_template'] ?? 'ledger');
        }

        $documentDefaults = $this->documentDefaults();
        $resolved['documents'] = array_replace_recursive($documentDefaults, is_array($resolved['documents'] ?? null) ? $resolved['documents'] : []);
        $resolved['document_type'] = $documentType;
        $resolved['document_visibility'] = $resolved['documents'][$documentType] ?? $documentDefaults['invoice'];

        foreach (array_keys($resolved['sections']) as $sectionKey) {
            if (! in_array($sectionKey, $resolved['section_order'], true)) {
                $resolved['section_order'][] = $sectionKey;
            }
        }

        return $resolved;
    }

    protected function documentDefaults(): array
    {
        return [
            'invoice' => [
                'prices' => true,
                'tax' => true,
                'totals' => true,
                'notes' => true,
                'signatures' => false,
                'recipient_shipping' => true,
            ],
            'delivery_note' => [
                'prices' => false,
                'tax' => false,
                'totals' => false,
                'notes' => false,
                'signatures' => true,
                'recipient_shipping' => true,
            ],
            'packing_slip' => [
                'prices' => false,
                'tax' => false,
                'totals' => false,
                'notes' => false,
                'signatures' => false,
                'recipient_shipping' => true,
            ],
            'quotation' => [
                'prices' => true,
                'tax' => true,
                'totals' => true,
                'notes' => true,
                'signatures' => false,
                'recipient_shipping' => true,
            ],
            'sale_order' => [
                'prices' => true,
                'tax' => true,
                'totals' => true,
                'notes' => true,
                'signatures' => false,
                'recipient_shipping' => true,
            ],
            'purchase_order' => [
                'prices' => true,
                'tax' => true,
                'totals' => true,
                'notes' => true,
                'signatures' => false,
                'recipient_shipping' => true,
            ],
        ];
    }
}

<?php

namespace Tests\Unit;

use App\InvoiceLayout;
use App\Support\Prints\PaperSizeResolver;
use App\Support\Prints\PrintConfigResolver;
use PHPUnit\Framework\TestCase;

class PrintConfigResolverTest extends TestCase
{
    public function test_it_maps_legacy_thermal_designs_to_new_paper_profiles(): void
    {
        $resolver = new PrintConfigResolver(new PaperSizeResolver());
        $invoiceLayout = new InvoiceLayout([
            'design' => 'slim',
            'common_settings' => [],
        ]);

        $config = $resolver->resolve($invoiceLayout);

        $this->assertSame('thermal-80', $config['paper_size']);
        $this->assertSame('thermal', $config['style_family']);
        $this->assertSame('clean', $config['template_variant']);
    }

    public function test_it_merges_explicit_print_settings_and_parses_section_order(): void
    {
        $resolver = new PrintConfigResolver(new PaperSizeResolver());
        $invoiceLayout = new InvoiceLayout([
            'design' => 'classic',
            'common_settings' => [
                'print' => [
                    'paper_size' => 'thermal-58',
                    'standard_template' => 'ledger',
                    'thermal_template' => 'compact',
                    'template_variant' => 'compact',
                    'section_order' => 'items, totals, codes',
                    'sections' => [
                        'notes' => false,
                        'footer' => false,
                    ],
                ],
            ],
        ]);

        $config = $resolver->resolve($invoiceLayout);

        $this->assertSame('thermal-58', $config['paper_size']);
        $this->assertSame('compact', $config['template_variant']);
        $this->assertSame(['items', 'totals', 'codes', 'brand_header', 'company', 'party', 'document_meta', 'notes', 'terms', 'signatures', 'footer'], $config['section_order']);
        $this->assertFalse($config['sections']['notes']);
        $this->assertFalse($config['sections']['footer']);
    }

    public function test_it_resolves_document_specific_visibility_overrides(): void
    {
        $resolver = new PrintConfigResolver(new PaperSizeResolver());
        $invoiceLayout = new InvoiceLayout([
            'design' => 'classic',
            'common_settings' => [
                'print' => [
                    'documents' => [
                        'delivery_note' => [
                            'notes' => true,
                            'signatures' => false,
                        ],
                    ],
                ],
            ],
        ]);

        $config = $resolver->resolve($invoiceLayout, ['document_type' => 'delivery_note']);

        $this->assertSame('delivery_note', $config['document_type']);
        $this->assertTrue($config['document_visibility']['notes']);
        $this->assertFalse($config['document_visibility']['signatures']);
        $this->assertFalse($config['document_visibility']['prices']);
    }
}

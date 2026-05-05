<?php

namespace App\Support\Prints;

class PaperSizeResolver
{
    protected array $profiles = [
        'a4' => [
            'paper_size' => 'a4',
            'style_family' => 'standard',
            'pdf_format' => 'A4',
            'pdf_margin_top' => 8,
            'pdf_margin_bottom' => 8,
            'pdf_margin_left' => 8,
            'pdf_margin_right' => 8,
            'document_width' => '210mm',
            'content_width' => '186mm',
            'barcode_scale' => 2,
            'barcode_height' => 40,
            'qr_size' => 120,
            'default_items_preset' => 'standard',
        ],
        'thermal-80' => [
            'paper_size' => 'thermal-80',
            'style_family' => 'thermal',
            'pdf_format' => [80, 297],
            'pdf_margin_top' => 4,
            'pdf_margin_bottom' => 4,
            'pdf_margin_left' => 3,
            'pdf_margin_right' => 3,
            'document_width' => '80mm',
            'content_width' => '72mm',
            'barcode_scale' => 1.5,
            'barcode_height' => 28,
            'qr_size' => 84,
            'default_items_preset' => 'thermal',
        ],
        'thermal-58' => [
            'paper_size' => 'thermal-58',
            'style_family' => 'thermal',
            'pdf_format' => [58, 240],
            'pdf_margin_top' => 3,
            'pdf_margin_bottom' => 3,
            'pdf_margin_left' => 2,
            'pdf_margin_right' => 2,
            'document_width' => '58mm',
            'content_width' => '52mm',
            'barcode_scale' => 1.2,
            'barcode_height' => 24,
            'qr_size' => 72,
            'default_items_preset' => 'thermal-compact',
        ],
    ];

    protected array $legacyDesignMap = [
        'classic' => 'a4',
        'elegant' => 'a4',
        'elegant_modified' => 'a4',
        'detailed' => 'a4',
        'columnize-taxes' => 'a4',
        'slim' => 'thermal-80',
        'slim2' => 'thermal-58',
    ];

    public function resolve(?string $paperSize, ?string $legacyDesign = null): array
    {
        $resolvedPaperSize = $paperSize;
        if (empty($resolvedPaperSize) || $resolvedPaperSize === 'auto') {
            $resolvedPaperSize = $this->legacyDesignMap[$legacyDesign ?? ''] ?? 'a4';
        }

        return $this->profile($resolvedPaperSize);
    }

    public function profile(?string $paperSize): array
    {
        $resolvedPaperSize = $paperSize ?? 'a4';

        return $this->profiles[$resolvedPaperSize] ?? $this->profiles['a4'];
    }

    public function options(): array
    {
        return array_keys($this->profiles);
    }
}

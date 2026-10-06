<?php

namespace App\Services;

use Mpdf\Mpdf;
use Illuminate\Http\Response;

class PdfService
{
    /**
     * Standard default configuration for mPDF according to project standards.
     */
    public static function defaultConfig(array $overrides = []): array
    {
        return array_merge([
            'mode'                 => 'utf-8',
            'format'               => 'A4',
            'default_font'         => 'Helvetica',
            'margin_top'           => 12,
            'margin_bottom'        => 12,
            'margin_left'          => 12,
            'margin_right'         => 12,
            'autoMarginPadding'    => 0,
            'bleed_margin'         => 0,
            'cross_mark_margin'    => 0,
            'crop_mark_margin'     => 0,
            'non_printable_margin' => 0,
            'margin_header'        => 0,
            'margin_footer'        => 0,
            'tempDir'              => storage_path('app/mpdf'),
        ], $overrides);
    }

    /**
     * Instantiate and configure an mPDF instance.
     */
    public static function createInstance(array $config = []): Mpdf
    {
        $tempDir = storage_path('app/mpdf');
        if (!file_exists($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        $options = self::defaultConfig($config);
        return new Mpdf($options);
    }

    /**
     * Render a blade view directly into an mPDF HTTP response.
     */
    public static function viewToResponse(
        string $view,
        array $data = [],
        string $filename = 'document.pdf',
        array $config = [],
        bool $download = false
    ): Response {
        $html = view($view, $data)->render();
        return self::htmlToResponse($html, $filename, $config, $download);
    }

    /**
     * Render raw HTML string into an mPDF HTTP response.
     */
    public static function htmlToResponse(
        string $html,
        string $filename = 'document.pdf',
        array $config = [],
        bool $download = false
    ): Response {
        $mpdf = self::createInstance($config);
        $mpdf->WriteHTML($html);

        $pdfBinary = $mpdf->Output($filename, 'S');
        $disposition = $download ? 'attachment' : 'inline';

        return response($pdfBinary, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\"",
        ]);
    }
}

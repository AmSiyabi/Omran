<?php

namespace App\Finance\Reports;

use Mpdf\Mpdf;

/**
 * Arabic-shaped PDF output (spec §8.10): RTL, brand fonts, OpenType layout
 * enabled so lam-alef ligatures and diacritics render correctly — verified
 * by the Phase 6 shaping test before this library was adopted.
 */
class PdfRenderer
{
    public function render(string $html, string $title): string
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'directionality' => 'rtl',
            'default_font' => 'tajawal',
            'margin_top' => 18,
            'margin_bottom' => 18,
            'margin_right' => 14,
            'margin_left' => 14,
            'tempDir' => storage_path('app/mpdf'),
            'fontDir' => [resource_path('fonts/pdf')],
            // Tajawal فقط: خط El Messiri يحوي MarkGlyphSets التي لا يدعمها
            // محلل OTL في mPDF (D-045) — تقارير جدولية، الخط الواحد يكفي
            'fontdata' => [
                'tajawal' => [
                    'R' => 'tajawal-regular.ttf',
                    'B' => 'tajawal-bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
        ]);

        $mpdf->SetTitle($title);
        $mpdf->SetAuthor(__('common.center_name'));
        $mpdf->WriteHTML($this->wrap($html, $title));

        return $mpdf->OutputBinaryData();
    }

    protected function wrap(string $html, string $title): string
    {
        $logo = public_path('images/brand/logo-navy-sm.webp');
        $date = now()->timezone(config('app.display_timezone'))->format('Y-m-d');

        return <<<HTML
        <html lang="ar" dir="rtl">
        <head>
        <style>
            body { font-family: tajawal; color: #16202f; font-size: 10.5pt; }
            h1, h2, h3 { font-family: tajawal; font-weight: bold; color: #16202f; }
            h1 { font-size: 17pt; margin: 0 0 2pt 0; }
            .meta { color: #6a7383; font-size: 9pt; margin-bottom: 12pt; }
            table { width: 100%; border-collapse: collapse; margin-top: 8pt; }
            th { background: #16202f; color: #f1e7d2; padding: 5pt 7pt; font-size: 9.5pt; text-align: right; }
            td { border-bottom: 0.4pt solid #e4dcc9; padding: 5pt 7pt; }
            .num { direction: ltr; text-align: left; font-family: tajawal; }
            .total td { background: #f4efe3; font-weight: bold; }
            .rule { border-bottom: 2pt solid #cda34f; width: 60pt; margin: 4pt 0 10pt 0; }
            .footer { color: #6a7383; font-size: 8pt; }
        </style>
        </head>
        <body>
            <table style="margin-top:0"><tr>
                <td style="border:none; padding:0"><h1>{$title}</h1><div class="rule"></div></td>
                <td style="border:none; padding:0; text-align:left"><img src="{$logo}" style="height:36pt"></td>
            </tr></table>
            <p class="meta">مركز عمران للتدريب والاستشارات · {$date}</p>
            {$html}
            <div class="footer" style="margin-top:14pt">وثيقة داخلية — تُولَّد آلياً من نظام عمران المالي.</div>
        </body>
        </html>
        HTML;
    }
}

<?php

use App\Finance\Reports\PdfRenderer;

/**
 * Spec Phase 6 gate: prove Arabic shaping (lam-alef + diacritics) before
 * committing to a PDF library. Run: php artisan tinker scripts/shaping-test.php
 */
$html = <<<'HTML'
<h2>اختبار التشكيل العربي</h2>
<p><b>لام-ألف:</b> لا، الأوقاف، الإسلامية، لآلئ، للأمانة</p>
<p><b>تشكيل وحركات:</b> رَبِّ اجْعَلْنِي مُقِيمَ الصَّلَاةِ وَمِنْ ذُرِّيَّتِي</p>
<p><b>خط العناوين:</b> <span style="font-size:14pt; font-weight:bold">العُمران يبدأ بالإنسان</span></p>
<p><b>أرقام مالية:</b> <span class="num">640.500</span> ر.ع.</p>
<table>
<tr><th>البند</th><th>المبلغ (ر.ع.)</th></tr>
<tr><td>إيرادات الدورات والورش</td><td class="num">1,000.000</td></tr>
<tr class="total"><td>الصافي القابل للتوزيع</td><td class="num">850.000</td></tr>
</table>
HTML;

$pdf = app(PdfRenderer::class)->render($html, 'اختبار تشكيل الخط العربي');
file_put_contents(storage_path('app/shaping-test.pdf'), $pdf);
echo 'PDF written: '.filesize(storage_path('app/shaping-test.pdf')).' bytes'.PHP_EOL;

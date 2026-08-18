<?php

namespace App\Finance\Reports;

use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * XLSX exports with a right-to-left sheet (spec §8.10 acceptance) — the
 * owners hand these to an accountant.
 */
class XlsxExport
{
    /**
     * @param  iterable<int, array<string, string|int|float>>  $rows
     */
    public static function stream(string $filename, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($filename, $rows): void {
            $writer = SimpleExcelWriter::streamDownload($filename);

            $spout = $writer->getWriter();

            if ($spout instanceof Writer) {
                $spout->getCurrentSheet()->setSheetView(
                    (new SheetView)->setRightToLeft(true),
                );
            }

            $writer->setHeaderStyle((new Style)->setFontBold());

            foreach ($rows as $row) {
                $writer->addRow($row);
            }

            $writer->close();
        }, $filename);
    }
}

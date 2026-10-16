<?php

namespace App\Libraries;

use CodeIgniter\HTTP\ResponseInterface;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Merender HTML laporan menjadi PDF A4 dengan Dompdf (tanpa akses internet)
 * dan mengirimkannya sebagai unduhan.
 */
class PdfLaporan
{
    public static function render(string $html, bool $lanskap = false): string
    {
        $folderFont = WRITEPATH . 'fonts';
        if (! is_dir($folderFont)) {
            mkdir($folderFont, 0775, true);
        }

        $opsi = new Options();
        $opsi->setChroot([FCPATH, WRITEPATH]);
        $opsi->setIsRemoteEnabled(false);
        $opsi->setDefaultFont('DejaVu Sans');
        $opsi->setFontDir($folderFont);
        $opsi->setFontCache($folderFont);
        $opsi->setTempDir(WRITEPATH . 'cache');
        $opsi->setDpi(96);

        $pdf = new Dompdf($opsi);
        $pdf->setBasePath(FCPATH);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', $lanskap ? 'landscape' : 'portrait');
        $pdf->render();

        // Nomor halaman & catatan dokumen internal di kaki setiap halaman
        $kanvas = $pdf->getCanvas();
        $font   = $pdf->getFontMetrics()->getFont('Source Sans 3') ?? $pdf->getFontMetrics()->getFont('DejaVu Sans');
        $lebar  = $kanvas->get_width();
        $tinggi = $kanvas->get_height();
        $kanvas->page_text(36, $tinggi - 28, nama_perusahaan() . ' · Dokumen internal · Dicetak ' . tgl_indo(date('Y-m-d')) . ' ' . date('H:i'), $font, 7, [0.45, 0.5, 0.53]);
        $kanvas->page_text($lebar - 110, $tinggi - 28, 'Halaman {PAGE_NUM} dari {PAGE_COUNT}', $font, 7, [0.45, 0.5, 0.53]);

        return $pdf->output();
    }

    public static function kirim(ResponseInterface $response, string $html, string $namaFile, bool $lanskap = false): ResponseInterface
    {
        return $response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $namaFile . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody(self::render($html, $lanskap));
    }
}

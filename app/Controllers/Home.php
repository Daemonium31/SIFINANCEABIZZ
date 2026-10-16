<?php

namespace App\Controllers;

use App\Libraries\PdfLaporan;
use App\Libraries\SiklusAkuntansi;

/**
 * Dashboard bergaya widget Accurate: ringkasan laba rugi, arus kas, tren,
 * komposisi beban dan posisi keuangan untuk satu periode, siap dipresentasikan.
 */
class Home extends BaseController
{
    public function dashboard()
    {
        $data = $this->ringkasan($this->periode());

        return view('dashboard', $data + ['nav' => 'dashboard', 'judul' => 'Dashboard']);
    }

    /** Ringkasan eksekutif PDF; grafik dikirim browser sebagai gambar PNG. */
    public function pdf()
    {
        $data   = $this->ringkasan($this->periode('post'));
        $gambar = [];
        foreach (['labarugi', 'tren', 'kas_saldo', 'kas_mutasi', 'posisi'] as $kunci) {
            $png = (string) $this->request->getPost('grafik_' . $kunci);
            if (strpos($png, 'data:image/png;base64,') === 0 && strlen($png) < 4_000_000
                && base64_decode(substr($png, 22), true) !== false) {
                $gambar[$kunci] = $png;
            }
        }

        $html = view('dashboard_pdf', $data + ['gambar' => $gambar, 'pembuat' => (string) session('user_name')]);
        $nama = \App\Libraries\Identitas::slug() . '_Ringkasan-Keuangan_' . $data['filter']['start'] . ($data['filter']['start'] !== $data['filter']['end'] ? '_' . $data['filter']['end'] : '') . '.pdf';

        return PdfLaporan::kirim($this->response, $html, $nama);
    }

    private function periode(string $sumber = 'get'): array
    {
        $ambil = static fn ($k) => (string) ($sumber === 'post' ? service('request')->getPost($k) : service('request')->getGet($k));
        $start = $ambil('start');
        $end   = $ambil('end');
        $start = preg_match('/^\d{4}-\d{2}$/', $start) ? $start : date('Y-m');
        $end   = preg_match('/^\d{4}-\d{2}$/', $end) ? $end : $start;
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        return ['start' => $start, 'end' => $end];
    }

    private function ringkasan(array $filter): array
    {
        $siklus = SiklusAkuntansi::untukBulan($filter['start'], $filter['end']);

        // Periode pembanding: jumlah bulan yang sama tepat sebelum periode ini
        $keBulan      = static fn (string $ym) => (int) substr($ym, 0, 4) * 12 + (int) substr($ym, 5, 2);
        $jumlahBulan  = $keBulan($filter['end']) - $keBulan($filter['start']) + 1;
        $sebelumAkhir = date('Y-m', strtotime($filter['start'] . '-01 -1 month'));
        $sebelumMulai = date('Y-m', strtotime($filter['start'] . '-01 -' . $jumlahBulan . ' months'));
        $sebelum      = SiklusAkuntansi::untukBulan($sebelumMulai, $sebelumAkhir);

        $lr   = $siklus->labaRugi();
        $lrS  = $sebelum->labaRugi();
        $pk   = $siklus->posisiKeuangan();
        $kas  = $siklus->arusKas();
        $tren = SiklusAkuntansi::trenBulanan($filter['end'], 6);

        // Komposisi beban: lima terbesar, sisanya digabung "Lainnya"
        $beban = $lr['beban']['items'];
        $komposisi = array_slice($beban, 0, 5);
        $sisa = array_sum(array_column(array_slice($beban, 5), 'nilai'));
        if ($sisa > 0) {
            $komposisi[] = ['nama_akun' => 'Lainnya (' . (count($beban) - 5) . ' akun)', 'nilai' => $sisa];
        }

        $rasio = [
            'lancar' => $pk['utang_pendek']['total'] > 0 ? $pk['aset_lancar']['total'] / $pk['utang_pendek']['total'] : null,
            'der'    => $pk['modal'] > 0 ? $pk['total_utang'] / $pk['modal'] : null,
            'margin' => $lr['margin'],
        ];

        return [
            'filter'      => $filter,
            'periode'     => periode_indo($siklus->mulai, $siklus->selesai),
            'pembanding'  => periode_indo($sebelum->mulai, $sebelum->selesai),
            'adaData'     => $siklus->adaTransaksi(),
            'lr'          => $lr,
            'lrSebelum'   => $lrS,
            'pk'          => $pk,
            'kas'         => $kas,
            'tren'        => $tren,
            'komposisi'   => $komposisi,
            'rasio'       => $rasio,
            'selesai'     => $siklus->selesai,
        ];
    }
}

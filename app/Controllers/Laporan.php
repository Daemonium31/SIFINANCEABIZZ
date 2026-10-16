<?php

namespace App\Controllers;

use App\Libraries\PdfLaporan;
use App\Libraries\SiklusAkuntansi;
use App\Models\AkunModel;

/**
 * Pusat laporan siklus akuntansi: sepuluh tahapan, masing-masing dapat
 * ditampilkan di layar dan diunduh sebagai PDF.
 */
class Laporan extends BaseController
{
    /** kunci => [judul, keterangan singkat] dalam urutan siklus */
    public const DAFTAR = [
        'jurnal_umum'        => ['Jurnal Umum', 'Transaksi dicatat berurutan'],
        'buku_besar'         => ['Buku Besar', 'Posting ke tiap akun'],
        'neraca_saldo'       => ['Neraca Saldo', 'Sebelum penyesuaian'],
        'jurnal_penyesuaian' => ['Jurnal Penyesuaian', 'Penyesuaian akhir periode'],
        'neraca_penyesuaian' => ['Neraca Penyesuaian', 'Neraca saldo setelah penyesuaian'],
        'neraca_lajur'       => ['Neraca Lajur', 'Kertas kerja 10 kolom'],
        'laporan_keuangan'   => ['Laporan Keuangan', 'Laba rugi, ekuitas, posisi keuangan'],
        'jurnal_penutup'     => ['Jurnal Penutup', 'Menutup akun nominal'],
        'neraca_penutupan'   => ['Neraca Saldo Setelah Penutupan', 'Akun riil saja'],
        'jurnal_balik'       => ['Jurnal Balik', 'Awal periode berikutnya'],
    ];

    public function index()
    {
        $filter = $this->filter();
        $siklus = SiklusAkuntansi::untukBulan($filter['start'], $filter['end']);

        return view('laporan/index', [
            'nav'      => 'laporan',
            'judul'    => 'Siklus Akuntansi',
            'filter'   => $filter,
            'daftar'   => self::DAFTAR,
            'lembar'   => $this->lembar($filter['jenis'], $siklus, $filter),
            'akunList' => (new AkunModel())->orderBy('no_akun')->findAll(),
            'siklus'   => $siklus,
        ]);
    }

    public function pdf()
    {
        $filter = $this->filter();
        $siklus = SiklusAkuntansi::untukBulan($filter['start'], $filter['end']);
        $semua  = $this->request->getGet('jenis') === 'semua';

        $lembar = [];
        $jenisList = $semua ? array_keys(self::DAFTAR) : [$filter['jenis']];
        foreach ($jenisList as $jenis) {
            $lembar = array_merge($lembar, $this->lembar($jenis, $siklus, $filter));
        }

        $lanskap = $semua || in_array($filter['jenis'], ['neraca_lajur', 'buku_besar'], true);
        $nama    = $semua ? 'Paket-Laporan-Siklus' : str_replace(' ', '-', self::DAFTAR[$filter['jenis']][0]);
        $periode = $filter['start'] === $filter['end'] ? $filter['start'] : $filter['start'] . '_' . $filter['end'];

        $html = view('laporan/pdf', [
            'lembar'  => $lembar,
            'judul'   => $semua ? 'Paket Laporan Siklus Akuntansi' : self::DAFTAR[$filter['jenis']][0],
            'periode' => periode_indo($siklus->mulai, $siklus->selesai),
            'sampul'  => $semua,
            'daftar'  => self::DAFTAR,
        ]);

        return PdfLaporan::kirim($this->response, $html, \App\Libraries\Identitas::slug() . "_{$nama}_{$periode}.pdf", $lanskap);
    }

    /** Membaca filter periode dan jenis laporan dari query string. */
    private function filter(): array
    {
        $bulanIni = date('Y-m');
        $start    = (string) $this->request->getGet('start');
        $end      = (string) $this->request->getGet('end');
        $start    = preg_match('/^\d{4}-\d{2}$/', $start) ? $start : $bulanIni;
        $end      = preg_match('/^\d{4}-\d{2}$/', $end) ? $end : $start;
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        $jenis = (string) $this->request->getGet('jenis');
        $tahap = (string) $this->request->getGet('tahap');

        return [
            'start' => $start,
            'end'   => $end,
            'jenis' => isset(self::DAFTAR[$jenis]) ? $jenis : 'jurnal_umum',
            'akun'  => trim((string) $this->request->getGet('akun')),
            'tahap' => in_array($tahap, ['ju', 'jp', 'tutup'], true) ? $tahap : 'jp',
        ];
    }

    /**
     * Lembar-lembar laporan untuk satu jenis. Setiap lembar punya judul,
     * keterangan periode, status keseimbangan dan partial yang menggambarnya.
     */
    private function lembar(string $jenis, SiklusAkuntansi $s, array $filter): array
    {
        $periode  = 'Periode ' . periode_indo($s->mulai, $s->selesai);
        $per      = 'Per ' . tgl_indo($s->selesai);
        $berakhir = 'Untuk periode yang berakhir ' . tgl_indo($s->selesai);
        $status   = static fn (array $d) => ['seimbang' => $d['seimbang'], 'selisih' => ($d['total_debit'] ?? $d['total']['debit']) - ($d['total_kredit'] ?? $d['total']['kredit'])];

        switch ($jenis) {
            case 'jurnal_umum':
                $d = $s->jurnalUmum();

                return [$this->buat('Jurnal Umum', $periode, 'jurnal', $d, $status($d), ['kosong' => 'Belum ada jurnal umum pada periode ini.', 'tambah' => '/jurnal_umum'])];

            case 'buku_besar':
                $tahap = ['ju' => 'Posting jurnal umum', 'jp' => 'Termasuk jurnal penyesuaian', 'tutup' => 'Termasuk jurnal penutup'][$filter['tahap']];

                return [$this->buat('Buku Besar', $periode . ' · ' . $tahap, 'buku_besar', $s->bukuBesar($filter['tahap'], $filter['akun'] ?: null), null)];

            case 'neraca_saldo':
                $d = $s->neracaSaldo();

                return [$this->buat('Neraca Saldo', $per, 'neraca_saldo', $d, $status($d))];

            case 'jurnal_penyesuaian':
                $d = $s->jurnalPenyesuaian();

                return [$this->buat('Jurnal Penyesuaian', $periode, 'jurnal', $d, $status($d), ['kosong' => 'Belum ada jurnal penyesuaian pada periode ini.', 'tambah' => '/jurnal_penyesuaian'])];

            case 'neraca_penyesuaian':
                $d = $s->neracaSaldoDisesuaikan();

                return [$this->buat('Neraca Saldo Setelah Penyesuaian', $per, 'neraca_saldo', $d, $status($d))];

            case 'neraca_lajur':
                $d = $s->neracaLajur();

                return [$this->buat('Neraca Lajur', $berakhir, 'neraca_lajur', $d, ['seimbang' => $d['seimbang'], 'selisih' => $d['total_akhir']['nrc_d'] - $d['total_akhir']['nrc_k']], ['lanskap' => true])];

            case 'laporan_keuangan':
                $pk = $s->posisiKeuangan();

                return [
                    $this->buat('Laporan Laba Rugi', $berakhir, 'laba_rugi', $s->labaRugi(), null),
                    $this->buat('Laporan Perubahan Ekuitas', $berakhir, 'perubahan_ekuitas', $s->perubahanEkuitas(), null),
                    $this->buat('Laporan Posisi Keuangan', $per, 'posisi_keuangan', $pk, ['seimbang' => $pk['seimbang'], 'selisih' => $pk['total_aktiva'] - $pk['total_pasiva'], 'label' => 'Aktiva = Pasiva']),
                ];

            case 'jurnal_penutup':
                $d = $s->jurnalPenutup();

                return [$this->buat('Jurnal Penutup', $per, 'jurnal', $d, $status($d), ['kosong' => 'Tidak ada akun pendapatan, beban, atau prive yang perlu ditutup pada periode ini.'])];

            case 'neraca_penutupan':
                $d = $s->neracaSaldoPenutupan();

                return [$this->buat('Neraca Saldo Setelah Penutupan', $per, 'neraca_saldo', $d, $status($d))];

            case 'jurnal_balik':
                $d = $s->jurnalBalik();

                return [$this->buat('Jurnal Balik', 'Per ' . tgl_indo($d['tanggal']), 'jurnal_balik', $d, $status($d))];
        }

        return [];
    }

    private function buat(string $judul, string $periode, string $partial, array $data, ?array $status, array $opsi = []): array
    {
        return compact('judul', 'periode', 'partial', 'data', 'status', 'opsi');
    }
}

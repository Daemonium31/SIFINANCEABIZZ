<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * Mesin siklus akuntansi.
 *
 * Menghitung seluruh tahapan siklus untuk satu periode dari tabel jurnal_umum
 * dan jurnal_penyesuaian: jurnal umum, buku besar, neraca saldo, jurnal
 * penyesuaian, neraca lajur, laporan keuangan, jurnal penutup, neraca saldo
 * setelah penutupan dan jurnal balik.
 *
 * Jurnal penutup dan jurnal balik dibentuk otomatis (tidak disimpan ke database),
 * sama seperti proses tutup buku pada software akuntansi.
 *
 * Semua saldo disimpan sebagai angka bersih (debit - kredit).
 */
class SiklusAkuntansi
{
    public const IKHTISAR = '__ikhtisar';
    public const MODAL    = '__modal';

    public string $mulai;
    public string $selesai;

    private BaseConnection $db;

    /** @var array<string, array> no_akun => info akun */
    private array $akun = [];

    /** @var array<string, int|float> saldo bersih sebelum periode dimulai */
    private array $saldoAwal = [];

    private array $entriJU = [];
    private array $entriJP = [];
    private array $cache = [];

    public function __construct(string $mulai, string $selesai, ?BaseConnection $db = null)
    {
        if ($mulai > $selesai) {
            [$mulai, $selesai] = [$selesai, $mulai];
        }

        $this->mulai   = $mulai;
        $this->selesai = $selesai;
        $this->db      = $db ?? db_connect();

        $this->muatAkun();
        $this->muatSaldoAwal();
        $this->entriJU = $this->muatJurnal('ju');
        $this->entriJP = $this->muatJurnal('jp');
    }

    /**
     * Membuat siklus dari rentang bulan (format Y-m), mis. 2026-01 s.d. 2026-03.
     */
    public static function untukBulan(string $bulanMulai, string $bulanSelesai): self
    {
        $mulai   = date('Y-m-01', strtotime($bulanMulai . '-01'));
        $selesai = date('Y-m-t', strtotime($bulanSelesai . '-01'));

        return new self($mulai, $selesai);
    }

    // ------------------------------------------------------------------
    // Klasifikasi akun
    // ------------------------------------------------------------------

    /**
     * Mengelompokkan akun berdasarkan digit pertama/kedua nomor akun
     * (1 aset, 2 utang, 3 modal, 4 pendapatan, 5 beban) dan namanya.
     */
    public static function klasifikasi(string $noAkun, string $nama, string $keterangan = ''): array
    {
        $digit = preg_replace('/\D/', '', $noAkun) . '00';
        $nama  = strtolower($nama);
        $ket   = strtolower($keterangan);

        $kontra = false;
        switch ($digit[0]) {
            case '1':
                $kelompok = $digit[1] === '1' ? 'aset_lancar' : 'aset_tetap';
                $kontra   = strpos($nama, 'akumulasi') !== false
                    || strpos($nama, 'akm') === 0
                    || strpos($nama, 'cadangan') !== false
                    || strpos($nama, 'penyisihan') !== false
                    || strpos($ket, 'kontra') !== false;
                break;
            case '2':
                $kelompok = ($digit[1] >= '2' || strpos($ket, 'jangka panjang') !== false || strpos($nama, 'jangka panjang') !== false)
                    ? 'utang_panjang' : 'utang_pendek';
                break;
            case '3':
                if (strpos($nama, 'prive') !== false || strpos($nama, 'pengambilan') !== false) {
                    $kelompok = 'prive';
                } elseif (strpos($nama, 'ikhtisar') !== false) {
                    $kelompok = 'ikhtisar';
                } else {
                    $kelompok = 'modal';
                }
                break;
            case '4':
            case '7':
                $kelompok = 'pendapatan';
                break;
            default:
                $kelompok = 'beban';
        }

        return [
            'kelompok' => $kelompok,
            'kontra'   => $kontra,
            'nominal'  => in_array($kelompok, ['pendapatan', 'beban'], true),
        ];
    }

    public function infoAkun(string $noAkun): array
    {
        if (isset($this->akun[$noAkun])) {
            return $this->akun[$noAkun];
        }

        if ($noAkun === self::IKHTISAR) {
            return ['no_akun' => '', 'nama_akun' => 'Ikhtisar Laba Rugi', 'kelompok' => 'ikhtisar', 'kontra' => false, 'nominal' => false];
        }
        if ($noAkun === self::MODAL) {
            return ['no_akun' => '', 'nama_akun' => 'Modal', 'kelompok' => 'modal', 'kontra' => false, 'nominal' => false];
        }

        // Akun ada di jurnal tetapi sudah dihapus dari daftar akun
        $info = ['no_akun' => $noAkun, 'nama_akun' => 'Akun ' . $noAkun] + self::klasifikasi($noAkun, '');
        $this->akun[$noAkun] = $info;

        return $info;
    }

    private function muatAkun(): void
    {
        $rows = $this->db->table('akun')->orderBy('no_akun')->get()->getResultArray();
        foreach ($rows as $row) {
            $this->akun[$row['no_akun']] = [
                'no_akun'   => $row['no_akun'],
                'nama_akun' => $row['nama_akun'],
            ] + self::klasifikasi($row['no_akun'], $row['nama_akun'], (string) ($row['keterangan'] ?? ''));
        }
    }

    /** Akun pertama dari suatu kelompok, atau akun virtual bila tidak ada. */
    private function akunKelompok(string $kelompok, string $cadangan): string
    {
        foreach ($this->akun as $no => $info) {
            if ($info['kelompok'] === $kelompok) {
                return (string) $no;
            }
        }

        return $cadangan;
    }

    public function akunModal(): string
    {
        return $this->akunKelompok('modal', self::MODAL);
    }

    public function akunIkhtisar(): string
    {
        return $this->akunKelompok('ikhtisar', self::IKHTISAR);
    }

    /** @return string[] nomor akun kas & bank */
    public function akunKas(): array
    {
        $kas = [];
        foreach ($this->akun as $no => $info) {
            $nama = strtolower($info['nama_akun']);
            if ($info['kelompok'] === 'aset_lancar' && ! $info['kontra']
                && (strpos($nama, 'kas') !== false || strpos($nama, 'bank') !== false)) {
                $kas[] = (string) $no;
            }
        }

        return $kas;
    }

    // ------------------------------------------------------------------
    // Pemuatan data
    // ------------------------------------------------------------------

    /**
     * Saldo awal = semua jurnal sebelum periode. Akun nominal dan prive
     * periode sebelumnya dianggap sudah ditutup ke akun modal.
     */
    private function muatSaldoAwal(): void
    {
        $sql = 'SELECT no_akun, SUM(debit) AS debit, SUM(kredit) AS kredit FROM (
                    SELECT no_akun, debit, kredit FROM jurnal_umum WHERE tgl_transaksi < ?
                    UNION ALL
                    SELECT no_akun, debit, kredit FROM jurnal_penyesuaian WHERE tgl_penyesuaian < ?
                ) AS semua GROUP BY no_akun';

        $rows  = $this->db->query($sql, [$this->mulai, $this->mulai])->getResultArray();
        $modal = $this->akunModal();

        foreach ($rows as $row) {
            $no   = (string) $row['no_akun'];
            $net  = $row['debit'] - $row['kredit'];
            $info = $this->infoAkun($no);

            $tujuan = ($info['nominal'] || in_array($info['kelompok'], ['prive', 'ikhtisar'], true)) ? $modal : $no;
            $this->saldoAwal[$tujuan] = ($this->saldoAwal[$tujuan] ?? 0) + $net;
        }

        $this->saldoAwal = array_filter($this->saldoAwal, static fn ($v) => $v != 0);
    }

    /**
     * Baris-baris jurnal digabung menjadi satu entri berdasarkan tanggal dan
     * keterangan yang sama, dengan baris debit ditulis lebih dulu.
     */
    private function muatJurnal(string $sumber): array
    {
        if ($sumber === 'ju') {
            [$tabel, $id, $tgl, $ket, $prefix] = ['jurnal_umum', 'no_transaksi', 'tgl_transaksi', 'keterangan_transaksi', 'JU'];
        } else {
            [$tabel, $id, $tgl, $ket, $prefix] = ['jurnal_penyesuaian', 'id_penyesuaian', 'tgl_penyesuaian', 'keterangan_penyesuaian', 'JP'];
        }

        $rows = $this->db->table($tabel)
            ->select("{$id} AS id, {$tgl} AS tanggal, {$ket} AS keterangan, no_akun, debit, kredit")
            ->where("{$tgl} >=", $this->mulai)
            ->where("{$tgl} <=", $this->selesai)
            ->orderBy($tgl)
            ->orderBy($id)
            ->get()->getResultArray();

        $grup = [];
        foreach ($rows as $row) {
            $kunci = $row['tanggal'] . '|' . strtolower(trim($row['keterangan']));
            $grup[$kunci]['tanggal']    = $row['tanggal'];
            $grup[$kunci]['keterangan'] = trim($row['keterangan']);
            $grup[$kunci]['baris'][]    = $this->baris((string) $row['no_akun'], (int) $row['debit'], (int) $row['kredit']) + ['id' => $row['id']];
        }

        $entri = [];
        $nomor = 1;
        foreach ($grup as $g) {
            $entri[] = $this->entri($sumber, $prefix . '-' . $nomor++, $g['tanggal'], $g['keterangan'], $g['baris']);
        }

        return $entri;
    }

    private function baris(string $noAkun, $debit, $kredit): array
    {
        $info = $this->infoAkun($noAkun);

        return [
            'kunci'     => $noAkun,
            'no_akun'   => $info['no_akun'],
            'nama_akun' => $info['nama_akun'],
            'debit'     => $debit,
            'kredit'    => $kredit,
        ];
    }

    private function entri(string $sumber, string $kode, string $tanggal, string $keterangan, array $baris): array
    {
        usort($baris, static fn ($a, $b) => ($b['debit'] > 0) <=> ($a['debit'] > 0));

        return [
            'sumber'       => $sumber,
            'kode'         => $kode,
            'tanggal'      => $tanggal,
            'keterangan'   => $keterangan,
            'baris'        => $baris,
            'total_debit'  => array_sum(array_column($baris, 'debit')),
            'total_kredit' => array_sum(array_column($baris, 'kredit')),
        ];
    }

    // ------------------------------------------------------------------
    // Saldo
    // ------------------------------------------------------------------

    private function terapkan(array $saldo, array $entri): array
    {
        foreach ($entri as $e) {
            foreach ($e['baris'] as $b) {
                $saldo[$b['kunci']] = ($saldo[$b['kunci']] ?? 0) + $b['debit'] - $b['kredit'];
            }
        }

        return $saldo;
    }

    /** Saldo setelah jurnal umum (dasar neraca saldo). */
    public function saldoNeracaSaldo(): array
    {
        return $this->cache['ns'] ??= $this->terapkan($this->saldoAwal, $this->entriJU);
    }

    /** Saldo setelah jurnal penyesuaian. */
    public function saldoDisesuaikan(): array
    {
        return $this->cache['nsd'] ??= $this->terapkan($this->saldoNeracaSaldo(), $this->entriJP);
    }

    /** Saldo setelah jurnal penutup. */
    public function saldoSetelahPenutupan(): array
    {
        return $this->cache['nsp'] ??= $this->terapkan($this->saldoDisesuaikan(), $this->jurnalPenutup()['entri']);
    }

    private function urutkanAkun(array $kunci): array
    {
        $kunci = array_values(array_unique(array_map('strval', $kunci)));
        usort($kunci, function ($a, $b) {
            $na = $this->infoAkun($a)['no_akun'] ?: '9999' . $a;
            $nb = $this->infoAkun($b)['no_akun'] ?: '9999' . $b;

            return strnatcmp($na, $nb);
        });

        return $kunci;
    }

    private function daftarSaldo(array $saldo, ?callable $filter = null): array
    {
        $baris = [];
        $total = ['debit' => 0, 'kredit' => 0];

        foreach ($this->urutkanAkun(array_keys($saldo)) as $kunci) {
            $net  = $saldo[$kunci];
            $info = $this->infoAkun($kunci);
            if ($net == 0 || ($filter && ! $filter($info))) {
                continue;
            }

            $debit  = $net > 0 ? $net : 0;
            $kredit = $net < 0 ? -$net : 0;
            $baris[] = ['no_akun' => $info['no_akun'], 'nama_akun' => $info['nama_akun'], 'debit' => $debit, 'kredit' => $kredit];
            $total['debit']  += $debit;
            $total['kredit'] += $kredit;
        }

        return ['baris' => $baris, 'total' => $total, 'seimbang' => $total['debit'] == $total['kredit']];
    }

    private function ringkasJurnal(array $entri): array
    {
        return [
            'entri'        => $entri,
            'total_debit'  => array_sum(array_column($entri, 'total_debit')),
            'total_kredit' => array_sum(array_column($entri, 'total_kredit')),
            'seimbang'     => array_sum(array_column($entri, 'total_debit')) == array_sum(array_column($entri, 'total_kredit')),
        ];
    }

    // ------------------------------------------------------------------
    // 1. Jurnal umum & 4. Jurnal penyesuaian
    // ------------------------------------------------------------------

    public function jurnalUmum(): array
    {
        return $this->ringkasJurnal($this->entriJU);
    }

    public function jurnalPenyesuaian(): array
    {
        return $this->ringkasJurnal($this->entriJP);
    }

    // ------------------------------------------------------------------
    // 2. Buku besar
    // ------------------------------------------------------------------

    /**
     * @param string      $tahap 'ju' (jurnal umum saja), 'jp' (+ penyesuaian) atau 'tutup' (+ penutup)
     * @param string|null $akun  batasi ke satu nomor akun
     */
    public function bukuBesar(string $tahap = 'jp', ?string $akun = null): array
    {
        $entri = $this->entriJU;
        if ($tahap === 'jp' || $tahap === 'tutup') {
            $entri = array_merge($entri, $this->entriJP);
        }
        if ($tahap === 'tutup') {
            $entri = array_merge($entri, $this->jurnalPenutup()['entri']);
        }

        $urutan = ['ju' => 0, 'jp' => 1, 'tutup' => 2];
        usort($entri, static fn ($a, $b) => [$a['tanggal'], $urutan[$a['sumber']]] <=> [$b['tanggal'], $urutan[$b['sumber']]]);

        $mutasi = [];
        foreach ($entri as $e) {
            foreach ($e['baris'] as $b) {
                $mutasi[$b['kunci']][] = [
                    'tanggal'    => $e['tanggal'],
                    'keterangan' => $e['keterangan'],
                    'ref'        => $e['kode'],
                    'debit'      => $b['debit'],
                    'kredit'     => $b['kredit'],
                ];
            }
        }

        $daftar = [];
        foreach ($this->urutkanAkun(array_merge(array_keys($this->saldoAwal), array_keys($mutasi))) as $kunci) {
            $info = $this->infoAkun($kunci);
            if ($akun !== null && $akun !== '' && $info['no_akun'] !== $akun) {
                continue;
            }

            $saldo = $this->saldoAwal[$kunci] ?? 0;
            $baris = [];
            $total = ['debit' => 0, 'kredit' => 0];

            foreach ($mutasi[$kunci] ?? [] as $m) {
                $saldo += $m['debit'] - $m['kredit'];
                $total['debit']  += $m['debit'];
                $total['kredit'] += $m['kredit'];
                $baris[] = $m + ['saldo' => $saldo];
            }

            $daftar[] = [
                'no_akun'      => $info['no_akun'],
                'nama_akun'    => $info['nama_akun'],
                'saldo_awal'   => $this->saldoAwal[$kunci] ?? 0,
                'baris'        => $baris,
                'total'        => $total,
                'saldo_akhir'  => $saldo,
            ];
        }

        return $daftar;
    }

    // ------------------------------------------------------------------
    // 3. Neraca saldo
    // ------------------------------------------------------------------

    public function neracaSaldo(): array
    {
        return $this->daftarSaldo($this->saldoNeracaSaldo());
    }

    // ------------------------------------------------------------------
    // 5. Neraca saldo setelah penyesuaian
    // ------------------------------------------------------------------

    public function neracaSaldoDisesuaikan(): array
    {
        return $this->daftarSaldo($this->saldoDisesuaikan());
    }

    // ------------------------------------------------------------------
    // 6. Neraca lajur (kertas kerja 10 kolom)
    // ------------------------------------------------------------------

    public function neracaLajur(): array
    {
        $ns  = $this->saldoNeracaSaldo();
        $nsd = $this->saldoDisesuaikan();

        $penyesuaian = [];
        foreach ($this->entriJP as $e) {
            foreach ($e['baris'] as $b) {
                $penyesuaian[$b['kunci']]['debit']  = ($penyesuaian[$b['kunci']]['debit'] ?? 0) + $b['debit'];
                $penyesuaian[$b['kunci']]['kredit'] = ($penyesuaian[$b['kunci']]['kredit'] ?? 0) + $b['kredit'];
            }
        }

        $kolom = ['ns_d', 'ns_k', 'jp_d', 'jp_k', 'nsd_d', 'nsd_k', 'lr_d', 'lr_k', 'nrc_d', 'nrc_k'];
        $total = array_fill_keys($kolom, 0);
        $baris = [];

        foreach ($this->urutkanAkun(array_merge(array_keys($ns), array_keys($penyesuaian), array_keys($nsd))) as $kunci) {
            $nNs  = $ns[$kunci] ?? 0;
            $nNsd = $nsd[$kunci] ?? 0;
            $jp   = $penyesuaian[$kunci] ?? ['debit' => 0, 'kredit' => 0];
            if ($nNs == 0 && $nNsd == 0 && $jp['debit'] == 0 && $jp['kredit'] == 0) {
                continue;
            }

            $info = $this->infoAkun($kunci);
            $row  = array_fill_keys($kolom, 0);
            $row['ns_d']  = max($nNs, 0);
            $row['ns_k']  = max(-$nNs, 0);
            $row['jp_d']  = $jp['debit'];
            $row['jp_k']  = $jp['kredit'];
            $row['nsd_d'] = max($nNsd, 0);
            $row['nsd_k'] = max(-$nNsd, 0);

            $tujuan = $info['nominal'] ? 'lr' : 'nrc';
            $row[$tujuan . '_d'] = $row['nsd_d'];
            $row[$tujuan . '_k'] = $row['nsd_k'];

            foreach ($kolom as $k) {
                $total[$k] += $row[$k];
            }

            $baris[] = ['no_akun' => $info['no_akun'], 'nama_akun' => $info['nama_akun']] + $row;
        }

        $laba  = $total['lr_k'] - $total['lr_d'];
        $akhir = $total;
        if ($laba >= 0) {
            $akhir['lr_d']  += $laba;
            $akhir['nrc_k'] += $laba;
        } else {
            $akhir['lr_k']  += -$laba;
            $akhir['nrc_d'] += -$laba;
        }

        return [
            'baris'       => $baris,
            'total'       => $total,
            'laba'        => $laba,
            'total_akhir' => $akhir,
            'seimbang'    => $akhir['lr_d'] == $akhir['lr_k'] && $akhir['nrc_d'] == $akhir['nrc_k']
                && $total['ns_d'] == $total['ns_k'] && $total['nsd_d'] == $total['nsd_k'],
        ];
    }

    // ------------------------------------------------------------------
    // 7. Laporan keuangan
    // ------------------------------------------------------------------

    private function itemKelompok(array $saldo, array $kelompok, int $tanda): array
    {
        $items = [];
        foreach ($this->urutkanAkun(array_keys($saldo)) as $kunci) {
            $info = $this->infoAkun($kunci);
            if ($saldo[$kunci] == 0 || ! in_array($info['kelompok'], $kelompok, true)) {
                continue;
            }
            $items[] = [
                'no_akun'   => $info['no_akun'],
                'nama_akun' => $info['nama_akun'],
                'kontra'    => $info['kontra'],
                'nilai'     => $tanda * $saldo[$kunci],
            ];
        }

        return ['items' => $items, 'total' => array_sum(array_column($items, 'nilai'))];
    }

    public function labaRugi(): array
    {
        return $this->cache['lr'] ??= (function () {
            $nsd        = $this->saldoDisesuaikan();
            $pendapatan = $this->itemKelompok($nsd, ['pendapatan'], -1);
            $beban      = $this->itemKelompok($nsd, ['beban'], 1);
            usort($beban['items'], static fn ($a, $b) => $b['nilai'] <=> $a['nilai']);

            $laba = $pendapatan['total'] - $beban['total'];

            return [
                'pendapatan'  => $pendapatan,
                'beban'       => $beban,
                'laba_bersih' => $laba,
                'margin'      => $pendapatan['total'] != 0 ? $laba / $pendapatan['total'] * 100 : null,
            ];
        })();
    }

    public function perubahanEkuitas(): array
    {
        return $this->cache['pe'] ??= (function () {
            $nsd   = $this->saldoDisesuaikan();
            $modal = $this->itemKelompok($nsd, ['modal'], -1);
            $prive = $this->itemKelompok($nsd, ['prive'], 1);
            $awal  = $this->itemKelompok($this->saldoAwal, ['modal'], -1);
            $laba  = $this->labaRugi()['laba_bersih'];

            return [
                'nama_modal'        => $this->infoAkun($this->akunModal())['nama_akun'],
                'modal_awal'        => $modal['total'],
                'saldo_awal_modal'  => $awal['total'],
                'setoran_periode'   => $modal['total'] - $awal['total'],
                'laba_bersih'       => $laba,
                'prive'             => $prive['total'],
                'perubahan'         => $laba - $prive['total'],
                'modal_akhir'       => $modal['total'] + $laba - $prive['total'],
            ];
        })();
    }

    public function posisiKeuangan(): array
    {
        return $this->cache['pk'] ??= (function () {
            $nsd          = $this->saldoDisesuaikan();
            $asetLancar   = $this->itemKelompok($nsd, ['aset_lancar'], 1);
            $asetTetap    = $this->itemKelompok($nsd, ['aset_tetap'], 1);
            $utangPendek  = $this->itemKelompok($nsd, ['utang_pendek'], -1);
            $utangPanjang = $this->itemKelompok($nsd, ['utang_panjang'], -1);
            $ekuitas      = $this->perubahanEkuitas();

            $totalAktiva = $asetLancar['total'] + $asetTetap['total'];
            $totalUtang  = $utangPendek['total'] + $utangPanjang['total'];
            $totalPasiva = $totalUtang + $ekuitas['modal_akhir'];

            return [
                'aset_lancar'   => $asetLancar,
                'aset_tetap'    => $asetTetap,
                'utang_pendek'  => $utangPendek,
                'utang_panjang' => $utangPanjang,
                'nama_modal'    => $ekuitas['nama_modal'],
                'modal'         => $ekuitas['modal_akhir'],
                'total_aktiva'  => $totalAktiva,
                'total_utang'   => $totalUtang,
                'total_pasiva'  => $totalPasiva,
                'seimbang'      => $totalAktiva == $totalPasiva,
            ];
        })();
    }

    // ------------------------------------------------------------------
    // 8. Jurnal penutup
    // ------------------------------------------------------------------

    public function jurnalPenutup(): array
    {
        return $this->cache['tutup'] ??= (function () {
            $nsd      = $this->saldoDisesuaikan();
            $ikhtisar = $this->akunIkhtisar();
            $modal    = $this->akunModal();
            $entri    = [];
            $nomor    = 1;

            $pendapatan = [];
            $beban      = [];
            $prive      = [];
            foreach ($this->urutkanAkun(array_keys($nsd)) as $kunci) {
                $info = $this->infoAkun($kunci);
                if ($nsd[$kunci] == 0) {
                    continue;
                }
                if ($info['kelompok'] === 'pendapatan') {
                    $pendapatan[$kunci] = $nsd[$kunci];
                } elseif ($info['kelompok'] === 'beban') {
                    $beban[$kunci] = $nsd[$kunci];
                } elseif ($info['kelompok'] === 'prive') {
                    $prive[$kunci] = $nsd[$kunci];
                }
            }

            // Setiap akun ditutup dengan memposting kebalikan saldonya,
            // selisihnya dibebankan ke akun lawan (ikhtisar / modal).
            $tutup = function (array $saldo, string $lawan, string $keterangan, bool $lawanDulu) use (&$entri, &$nomor) {
                if (! $saldo) {
                    return;
                }
                $baris = [];
                foreach ($saldo as $kunci => $net) {
                    $baris[] = $this->baris((string) $kunci, $net < 0 ? -$net : 0, $net > 0 ? $net : 0);
                }
                $sum        = array_sum($saldo);
                $barisLawan = $this->baris($lawan, $sum > 0 ? $sum : 0, $sum < 0 ? -$sum : 0);
                $lawanDulu ? array_unshift($baris, $barisLawan) : $baris[] = $barisLawan;

                $e = $this->entri('tutup', 'JPP-' . $nomor++, $this->selesai, $keterangan, $baris);
                $e['baris'] = $baris; // pertahankan urutan buku teks
                $entri[] = $e;
            };

            $tutup($pendapatan, $ikhtisar, 'Menutup akun pendapatan ke Ikhtisar Laba Rugi', false);
            $tutup($beban, $ikhtisar, 'Menutup akun beban ke Ikhtisar Laba Rugi', true);

            $saldoIkhtisar = $this->terapkan([$ikhtisar => $nsd[$ikhtisar] ?? 0], $entri)[$ikhtisar] ?? 0;
            if ($saldoIkhtisar != 0) {
                $tutup([$ikhtisar => $saldoIkhtisar], $modal, $saldoIkhtisar < 0
                    ? 'Memindahkan laba bersih ke Modal'
                    : 'Memindahkan rugi bersih ke Modal', $saldoIkhtisar > 0);
            }

            $tutup($prive, $modal, 'Menutup akun prive ke Modal', true);

            return $this->ringkasJurnal($entri);
        })();
    }

    // ------------------------------------------------------------------
    // 9. Neraca saldo setelah penutupan
    // ------------------------------------------------------------------

    public function neracaSaldoPenutupan(): array
    {
        return $this->daftarSaldo($this->saldoSetelahPenutupan(), static fn ($info) => ! $info['nominal']);
    }

    // ------------------------------------------------------------------
    // 10. Jurnal balik
    // ------------------------------------------------------------------

    /**
     * Jurnal penyesuaian dibalik pada awal periode berikutnya bila
     * memunculkan akun aset/utang baru (akrual atau pendekatan beban/pendapatan),
     * yaitu akun riil yang belum bersaldo sebelum penyesuaian. Penyusutan dan
     * penyesuaian atas akun yang sudah bersaldo (pendekatan neraca) tidak dibalik.
     */
    public function jurnalBalik(): array
    {
        $ns        = $this->saldoNeracaSaldo();
        $tanggal   = date('Y-m-d', strtotime($this->selesai . ' +1 day'));
        $dibalik   = [];
        $tidak     = [];
        $nomor     = 1;
        $riil      = ['aset_lancar', 'aset_tetap', 'utang_pendek', 'utang_panjang'];

        foreach ($this->entriJP as $e) {
            $akunBaru = null;
            $alasan   = null;
            $adaNominal = false;

            foreach ($e['baris'] as $b) {
                $info = $this->infoAkun($b['kunci']);
                $adaNominal = $adaNominal || $info['nominal'];
                if (! in_array($info['kelompok'], $riil, true)) {
                    continue;
                }
                if ($info['kontra']) {
                    $alasan = $info['nama_akun'] . ' adalah akun kontra (penyusutan/penyisihan), tidak dibalik.';
                } elseif (($ns[$b['kunci']] ?? 0) == 0) {
                    $akunBaru = $info;
                } else {
                    $alasan ??= $info['nama_akun'] . ' sudah bersaldo sebelum penyesuaian (pendekatan neraca), tidak perlu dibalik.';
                }
            }

            if ($akunBaru && $adaNominal) {
                $baris = array_map(static fn ($b) => ['debit' => $b['kredit'], 'kredit' => $b['debit']] + $b, $e['baris']);
                $balik = $this->entri('balik', 'JB-' . $nomor++, $tanggal, 'Jurnal balik ' . $e['kode'] . ': ' . $e['keterangan'], $baris);
                $balik['asal']   = $e['kode'];
                $balik['alasan'] = 'Akun ' . $akunBaru['nama_akun'] . ' muncul karena penyesuaian (akrual), dibalik agar pencatatan transaksi berikutnya tetap normal.';
                $dibalik[] = $balik;
            } else {
                $tidak[] = [
                    'kode'       => $e['kode'],
                    'keterangan' => $e['keterangan'],
                    'alasan'     => $alasan ?? 'Tidak memunculkan akun aset/utang baru.',
                ];
            }
        }

        return $this->ringkasJurnal($dibalik) + ['tanggal' => $tanggal, 'tidak_dibalik' => $tidak];
    }

    // ------------------------------------------------------------------
    // Data grafik
    // ------------------------------------------------------------------

    /** Saldo awal, mutasi harian/bulanan dan saldo akhir akun kas & bank. */
    public function arusKas(): array
    {
        $kas      = array_flip($this->akunKas());
        $saldo    = array_sum(array_intersect_key($this->saldoAwal, $kas));
        $awal     = $saldo;
        $hari     = (strtotime($this->selesai) - strtotime($this->mulai)) / 86400 + 1;
        $format   = $hari > 62 ? 'Y-m' : 'Y-m-d';
        $titik    = [];

        for ($t = strtotime($this->mulai); $t <= strtotime($this->selesai); $t = strtotime($format === 'Y-m' ? '+1 month' : '+1 day', $t)) {
            $titik[date($format, $t)] = ['masuk' => 0, 'keluar' => 0];
        }

        foreach (array_merge($this->entriJU, $this->entriJP) as $e) {
            $k = date($format, strtotime($e['tanggal']));
            foreach ($e['baris'] as $b) {
                if (isset($kas[$b['kunci']]) && isset($titik[$k])) {
                    $titik[$k]['masuk']  += $b['debit'];
                    $titik[$k]['keluar'] += $b['kredit'];
                }
            }
        }

        $hasil = [];
        foreach ($titik as $label => $m) {
            $saldo  += $m['masuk'] - $m['keluar'];
            $hasil[] = ['label' => $label] + $m + ['saldo' => $saldo];
        }

        return [
            'satuan'      => $format === 'Y-m' ? 'bulan' : 'hari',
            'saldo_awal'  => $awal,
            'saldo_akhir' => $saldo,
            'masuk'       => array_sum(array_column($hasil, 'masuk')),
            'keluar'      => array_sum(array_column($hasil, 'keluar')),
            'titik'       => $hasil,
        ];
    }

    /**
     * Pendapatan dan beban per bulan untuk $jumlah bulan yang berakhir pada $bulanAkhir (Y-m).
     */
    public static function trenBulanan(string $bulanAkhir, int $jumlah = 6, ?BaseConnection $db = null): array
    {
        $db      = $db ?? db_connect();
        $akhir   = date('Y-m-t', strtotime($bulanAkhir . '-01'));
        $mulai   = date('Y-m-01', strtotime($bulanAkhir . '-01 -' . ($jumlah - 1) . ' months'));

        $sql = "SELECT SUBSTR(tgl, 1, 7) AS bulan, semua.no_akun, akun.nama_akun, akun.keterangan,
                       SUM(debit) AS debit, SUM(kredit) AS kredit
                FROM (
                    SELECT tgl_transaksi AS tgl, no_akun, debit, kredit FROM jurnal_umum WHERE tgl_transaksi BETWEEN ? AND ?
                    UNION ALL
                    SELECT tgl_penyesuaian AS tgl, no_akun, debit, kredit FROM jurnal_penyesuaian WHERE tgl_penyesuaian BETWEEN ? AND ?
                ) AS semua
                LEFT JOIN akun ON akun.no_akun = semua.no_akun
                GROUP BY SUBSTR(tgl, 1, 7), semua.no_akun, akun.nama_akun, akun.keterangan";

        $tren = [];
        for ($i = 0; $i < $jumlah; $i++) {
            $tren[date('Y-m', strtotime($mulai . " +{$i} months"))] = ['pendapatan' => 0, 'beban' => 0];
        }

        foreach ($db->query($sql, [$mulai, $akhir, $mulai, $akhir])->getResultArray() as $row) {
            $k = self::klasifikasi((string) $row['no_akun'], (string) $row['nama_akun'], (string) $row['keterangan']);
            if ($k['kelompok'] === 'pendapatan') {
                $tren[$row['bulan']]['pendapatan'] += $row['kredit'] - $row['debit'];
            } elseif ($k['kelompok'] === 'beban') {
                $tren[$row['bulan']]['beban'] += $row['debit'] - $row['kredit'];
            }
        }

        $hasil = [];
        foreach ($tren as $bulan => $v) {
            $hasil[] = ['bulan' => $bulan] + $v + ['laba' => $v['pendapatan'] - $v['beban']];
        }

        return $hasil;
    }

    public function adaTransaksi(): bool
    {
        return $this->entriJU || $this->entriJP || $this->saldoAwal;
    }
}

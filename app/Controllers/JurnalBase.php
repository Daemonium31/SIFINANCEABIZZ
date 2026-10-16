<?php

namespace App\Controllers;

use App\Libraries\SiklusAkuntansi;
use App\Models\AkunModel;

/**
 * Pencatatan jurnal berbaris banyak (satu jurnal = beberapa akun, debit = kredit).
 * Dipakai bersama oleh Jurnal Umum dan Jurnal Penyesuaian; tiap baris tetap
 * disimpan satu baris tabel seperti struktur lama.
 */
abstract class JurnalBase extends BaseController
{
    /** @var array{tabel:string,id:string,tgl:string,ket:string,maks_ket:int,prefix:string,url:string,judul:string,nav:string,sumber:string} */
    protected array $cfg;

    public function index()
    {
        $bulan = date('Y-m');
        $start = (string) $this->request->getGet('start');
        $end   = (string) $this->request->getGet('end');
        $start = preg_match('/^\d{4}-\d{2}$/', $start) ? $start : $bulan;
        $end   = preg_match('/^\d{4}-\d{2}$/', $end) ? $end : $start;
        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }
        $cari = trim((string) $this->request->getGet('cari'));

        $siklus = SiklusAkuntansi::untukBulan($start, $end);
        $jurnal = $this->cfg['sumber'] === 'ju' ? $siklus->jurnalUmum() : $siklus->jurnalPenyesuaian();

        if ($cari !== '') {
            $jurnal['entri'] = array_values(array_filter($jurnal['entri'], static function ($e) use ($cari) {
                $teks = $e['keterangan'] . ' ' . $e['kode'] . ' ' . implode(' ', array_map(static fn ($b) => $b['no_akun'] . ' ' . $b['nama_akun'], $e['baris']));

                return stripos($teks, $cari) !== false;
            }));
        }

        return view('jurnal', [
            'nav'    => $this->cfg['nav'],
            'judul'  => $this->cfg['judul'],
            'cfg'    => $this->cfg,
            'filter' => ['start' => $start, 'end' => $end, 'cari' => $cari],
            'periode' => periode_indo($siklus->mulai, $siklus->selesai),
            'jurnal' => $jurnal,
            'akuns'  => (new AkunModel())->orderBy('no_akun')->findAll(),
        ]);
    }

    /** Menyimpan jurnal baru, atau mengganti jurnal lama bila ids[] dikirim. */
    public function simpan()
    {
        $kembali = $this->cfg['url'] . '?' . http_build_query(['start' => $this->request->getPost('start'), 'end' => $this->request->getPost('end')]);
        $tanggal = (string) $this->request->getPost('tanggal');
        $ket     = trim((string) $this->request->getPost('keterangan'));
        $idLama  = array_filter((array) $this->request->getPost('ids'), 'is_string');
        $akun    = (array) $this->request->getPost('akun');
        $debit   = (array) $this->request->getPost('debit');
        $kredit  = (array) $this->request->getPost('kredit');

        $err = [];
        $t = \DateTime::createFromFormat('Y-m-d', $tanggal);
        if (! $t || $t->format('Y-m-d') !== $tanggal) {
            $err[] = 'Tanggal jurnal belum diisi dengan benar.';
        }
        if ($ket === '') {
            $err[] = 'Keterangan jurnal wajib diisi.';
        } elseif (mb_strlen($ket) > $this->cfg['maks_ket']) {
            $err[] = 'Keterangan terlalu panjang, maksimal ' . $this->cfg['maks_ket'] . ' karakter.';
        }

        $daftarAkun = array_column((new AkunModel())->findAll(), 'nama_akun', 'no_akun');
        $baris = [];
        foreach ($akun as $i => $no) {
            $no = trim((string) $no);
            $d  = $this->angka($debit[$i] ?? '');
            $k  = $this->angka($kredit[$i] ?? '');
            if ($no === '' && $d === 0 && $k === 0) {
                continue;
            }
            $n = count($baris) + 1;
            if (! isset($daftarAkun[$no])) {
                $err[] = "Baris {$n}: pilih akun.";
            }
            if ($d === null || $k === null) {
                $err[] = "Baris {$n}: nominal harus berupa angka.";
            } elseif (($d > 0) === ($k > 0)) {
                $err[] = "Baris {$n}: isi salah satu, debit atau kredit.";
            }
            $baris[] = ['no_akun' => $no, 'debit' => (int) $d, 'kredit' => (int) $k];
        }

        if (count($baris) < 2) {
            $err[] = 'Jurnal membutuhkan minimal dua baris: satu akun di debit dan satu di kredit.';
        }
        $totalD = array_sum(array_column($baris, 'debit'));
        $totalK = array_sum(array_column($baris, 'kredit'));
        if ($totalD !== $totalK) {
            $err[] = 'Total debit (' . rupiah($totalD) . ') belum sama dengan total kredit (' . rupiah($totalK) . ').';
        }

        $model = $this->model();
        if (! $err) {
            $kembar = $model->where($this->cfg['tgl'], $tanggal)->where($this->cfg['ket'], $ket);
            if ($idLama) {
                $kembar->whereNotIn($this->cfg['id'], $idLama);
            }
            if ($kembar->countAllResults() > 0) {
                $err[] = 'Sudah ada jurnal lain dengan tanggal dan keterangan yang sama. Bedakan keterangannya agar tidak tergabung.';
            }
        }

        if ($err) {
            return redirect()->to($kembali)->with('errors', $err)->withInput();
        }

        $db = db_connect();
        $db->transStart();
        if ($idLama) {
            $model->whereIn($this->cfg['id'], $idLama)->delete();
        }
        $dasar = $this->kodeBaru($tanggal);
        $kini  = date('Y-m-d H:i:s');
        foreach ($baris as $i => $b) {
            $model->insert([
                $this->cfg['id']  => $dasar . '-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                $this->cfg['tgl'] => $tanggal,
                $this->cfg['ket'] => $ket,
                'no_akun'         => $b['no_akun'],
                'debit'           => $b['debit'],
                'kredit'          => $b['kredit'],
                'nip'             => (string) session('user_id'),
                'created_at'      => $kini,
                'updated_at'      => $kini,
            ]);
        }
        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->to($kembali)->with('errors', 'Jurnal gagal disimpan. Coba lagi.')->withInput();
        }

        return redirect()->to($kembali)->with('success', ($idLama ? 'Jurnal diperbarui: ' : 'Jurnal tersimpan: ') . $ket);
    }

    public function hapus()
    {
        $ids = array_filter((array) $this->request->getPost('ids'), 'is_string');
        $kembali = $this->cfg['url'] . '?' . http_build_query(['start' => $this->request->getPost('start'), 'end' => $this->request->getPost('end')]);
        if ($ids) {
            $this->model()->whereIn($this->cfg['id'], $ids)->delete();
        }

        return redirect()->to($kembali)->with('success', 'Jurnal dihapus.');
    }

    abstract protected function model();

    /** "1.500.000" atau "1500000" menjadi 1500000; teks lain menjadi null. */
    private function angka($nilai): ?int
    {
        $s = preg_replace('/[\s.]|Rp/i', '', (string) $nilai);
        if ($s === '') {
            return 0;
        }
        $s = str_replace(',', '.', $s);

        return is_numeric($s) && $s >= 0 ? (int) round((float) $s) : null;
    }

    /** Nomor bukti berurutan per tanggal, mis. JU261031-004. */
    private function kodeBaru(string $tanggal): string
    {
        $awal = $this->cfg['prefix'] . date('ymd', strtotime($tanggal)) . '-';
        $baris = $this->model()->select($this->cfg['id'] . ' AS id')->like($this->cfg['id'], $awal, 'after')
            ->orderBy($this->cfg['id'], 'DESC')->first();
        $urut = $baris ? (int) substr($baris['id'], strlen($awal), 3) + 1 : 1;

        return $awal . str_pad((string) $urut, 3, '0', STR_PAD_LEFT);
    }
}

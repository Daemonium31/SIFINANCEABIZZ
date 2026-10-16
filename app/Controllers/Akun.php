<?php

namespace App\Controllers;

use App\Libraries\SiklusAkuntansi;
use App\Models\AkunModel;

class Akun extends BaseController
{
    public const KELOMPOK = [
        'aset_lancar'   => 'Aset lancar',
        'aset_tetap'    => 'Aset tetap',
        'utang_pendek'  => 'Utang jangka pendek',
        'utang_panjang' => 'Utang jangka panjang',
        'modal'         => 'Modal',
        'prive'         => 'Prive',
        'ikhtisar'      => 'Ikhtisar laba rugi',
        'pendapatan'    => 'Pendapatan',
        'beban'         => 'Beban',
    ];

    public function index()
    {
        $cari  = trim((string) $this->request->getGet('cari'));
        $model = new AkunModel();
        if ($cari !== '') {
            $model->groupStart()->like('no_akun', $cari)->orLike('nama_akun', $cari)->orLike('keterangan', $cari)->groupEnd();
        }
        $akuns = $model->orderBy('no_akun')->findAll();

        // Saldo sampai hari ini, termasuk penyesuaian
        $siklus = new SiklusAkuntansi('1900-01-01', date('Y-m-d'));
        $saldo  = $siklus->saldoDisesuaikan();
        $pakai  = $this->jumlahPemakaian();

        $grup = [];
        foreach ($akuns as $a) {
            $k = SiklusAkuntansi::klasifikasi($a['no_akun'], $a['nama_akun'], (string) $a['keterangan']);
            $grup[$k['kelompok']][] = $a + [
                'kontra' => $k['kontra'],
                'saldo'  => $saldo[$a['no_akun']] ?? 0,
                'pakai'  => $pakai[$a['no_akun']] ?? 0,
            ];
        }

        return view('akun', [
            'nav'      => 'akun',
            'judul'    => 'Daftar Akun',
            'grup'     => $grup,
            'kelompok' => self::KELOMPOK,
            'cari'     => $cari,
            'jumlah'   => count($akuns),
        ]);
    }

    public function simpan()
    {
        $lama = (string) $this->request->getPost('no_lama');
        $no   = trim((string) $this->request->getPost('no_akun'));
        $nama = trim((string) $this->request->getPost('nama_akun'));
        $ket  = trim((string) $this->request->getPost('keterangan'));

        $aturan = [
            'no_akun'    => ['label' => 'Nomor akun', 'rules' => 'required|numeric|max_length[20]|is_unique[akun.no_akun' . ($lama !== '' ? ',no_akun,' . $lama : '') . ']'],
            'nama_akun'  => ['label' => 'Nama akun', 'rules' => 'required|max_length[100]|is_unique[akun.nama_akun' . ($lama !== '' ? ',no_akun,' . $lama : '') . ']'],
            'keterangan' => ['label' => 'Keterangan', 'rules' => 'permit_empty|max_length[255]'],
        ];
        $pesan = [
            'no_akun'   => ['is_unique' => 'Nomor akun {value} sudah dipakai akun lain.', 'numeric' => 'Nomor akun hanya berisi angka, mis. 111.'],
            'nama_akun' => ['is_unique' => 'Nama akun "{value}" sudah ada.'],
        ];
        if (! $this->validate($aturan, $pesan)) {
            return redirect()->to('/akun')->with('errors', $this->validator->getErrors())->withInput();
        }

        $model = new AkunModel();
        $kini  = date('Y-m-d H:i:s');
        $db    = db_connect();
        $db->transStart();
        if ($lama !== '') {
            $model->update($lama, ['no_akun' => $no, 'nama_akun' => $nama, 'keterangan' => $ket, 'updated_at' => $kini]);
            if ($lama !== $no) {
                // Nomor akun berubah: ikutkan semua baris jurnal yang memakainya
                $db->table('jurnal_umum')->where('no_akun', $lama)->update(['no_akun' => $no]);
                $db->table('jurnal_penyesuaian')->where('no_akun', $lama)->update(['no_akun' => $no]);
            }
        } else {
            $model->insert(['no_akun' => $no, 'nama_akun' => $nama, 'keterangan' => $ket, 'created_at' => $kini, 'updated_at' => $kini]);
        }
        $db->transComplete();

        return redirect()->to('/akun')->with('success', ($lama !== '' ? 'Akun diperbarui: ' : 'Akun ditambahkan: ') . $no . ' ' . $nama);
    }

    public function hapus()
    {
        $no    = (string) $this->request->getPost('no_akun');
        $pakai = $this->jumlahPemakaian()[$no] ?? 0;
        if ($pakai > 0) {
            return redirect()->to('/akun')->with('errors', "Akun {$no} tidak bisa dihapus karena sudah dipakai di {$pakai} baris jurnal. Ubah atau hapus jurnalnya lebih dulu.");
        }
        (new AkunModel())->delete($no);

        return redirect()->to('/akun')->with('success', "Akun {$no} dihapus.");
    }

    private function jumlahPemakaian(): array
    {
        $sql = 'SELECT no_akun, COUNT(*) AS n FROM (SELECT no_akun FROM jurnal_umum UNION ALL SELECT no_akun FROM jurnal_penyesuaian) AS semua GROUP BY no_akun';

        return array_column(db_connect()->query($sql)->getResultArray(), 'n', 'no_akun');
    }
}

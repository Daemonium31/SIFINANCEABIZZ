<?php

namespace App\Controllers;

use App\Libraries\Cadangan as Berkas;

/**
 * Cadangan & pemulihan data. Karena tiap komputer menyimpan bukunya sendiri,
 * berkas cadangan (.json) adalah cara memindahkan atau mengamankan data.
 *
 * - Cadangan otomatis: satu berkas di folder data aplikasi, ditimpa setiap
 *   kali data berubah (lihat App\Filters\CadanganOtomatis).
 * - Cadangan manual: berkas terpisah bertanggal yang diunduh pengguna.
 */
class Cadangan extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $jumlah = [];
        foreach (array_keys(Berkas::TABEL) as $t) {
            $jumlah[$t] = $db->table($t)->countAllResults();
        }

        return view('cadangan', [
            'nav'      => 'cadangan',
            'judul'    => 'Cadangan Data',
            'jumlah'   => $jumlah,
            'otomatis' => Berkas::infoOtomatis(),
            'lokasi'   => Berkas::berkasOtomatis(),
        ]);
    }

    /** Cadangan manual: berkas baru bertanggal, terpisah dari cadangan otomatis. */
    public function unduh()
    {
        return $this->response
            ->setContentType('application/json')
            ->setHeader('Content-Disposition', 'attachment; filename="' . \App\Libraries\Identitas::slug() . '_Cadangan-Manual_' . date('Y-m-d_His') . '.json"')
            ->setBody(Berkas::json(Berkas::ekspor('manual', (string) session('user_name'))));
    }

    /** Salinan berkas cadangan otomatis terakhir. */
    public function unduhOtomatis()
    {
        $info = Berkas::infoOtomatis();
        if (! $info['ada']) {
            return redirect()->to('/cadangan')->with('errors', 'Belum ada cadangan otomatis.');
        }

        return $this->response
            ->setContentType('application/json')
            ->setHeader('Content-Disposition', 'attachment; filename="' . \App\Libraries\Identitas::slug() . '_Cadangan-Otomatis_' . date('Y-m-d_His', strtotime($info['dibuat'])) . '.json"')
            ->setBody((string) file_get_contents(Berkas::berkasOtomatis()));
    }

    /** Pulihkan dari berkas yang diunggah (manual maupun otomatis). */
    public function pulihkan()
    {
        $berkas = $this->request->getFile('berkas');
        if (! $berkas || ! $berkas->isValid()) {
            return redirect()->to('/cadangan')->with('errors', 'Pilih berkas cadangan (.json) lebih dulu.');
        }

        return $this->ganti(Berkas::baca((string) file_get_contents($berkas->getTempName())));
    }

    /** Pulihkan langsung dari cadangan otomatis yang tersimpan di komputer ini. */
    public function pulihkanOtomatis()
    {
        return $this->ganti(Berkas::baca((string) @file_get_contents(Berkas::berkasOtomatis())));
    }

    private function ganti(?array $isi)
    {
        if ($pesan = Berkas::periksa($isi)) {
            return redirect()->to('/cadangan')->with('errors', $pesan);
        }
        if (! Berkas::pulihkan($isi)) {
            return redirect()->to('/cadangan')->with('errors', 'Pemulihan gagal; data lama tetap dipakai.');
        }

        // Cadangan otomatis ikut mencerminkan data yang baru dipulihkan
        Berkas::simpanOtomatis((string) session('user_name'));
        session()->destroy();

        return redirect()->to('/login')->with('success', 'Data dipulihkan dari cadangan ' . tgl_indo(substr((string) ($isi['dibuat'] ?? date('c')), 0, 10)) . '. Silakan masuk kembali.');
    }
}

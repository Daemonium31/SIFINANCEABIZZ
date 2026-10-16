<?php

namespace App\Controllers;

use App\Libraries\Cadangan;
use App\Libraries\Identitas;

/** Identitas Perusahaan: nama dan logo yang tampil di aplikasi, laporan, dan PDF. */
class Pengaturan extends BaseController
{
    public function index()
    {
        return view('pengaturan', ['nav' => 'pengaturan', 'judul' => 'Identitas Perusahaan']);
    }

    public function simpan()
    {
        if (! $this->validate([
            'nama_perusahaan' => ['label' => 'Nama perusahaan', 'rules' => 'required|max_length[100]'],
        ])) {
            return redirect()->to('/pengaturan')->with('errors', $this->validator->getErrors())->withInput();
        }
        Identitas::simpanNama(trim((string) $this->request->getPost('nama_perusahaan')));

        $logo = $this->request->getFile('logo');
        if ($logo && $logo->getError() !== UPLOAD_ERR_NO_FILE) {
            if (! $logo->isValid() || $logo->getSize() > 2 * 1024 * 1024) {
                return redirect()->to('/pengaturan')->with('errors', 'Logo maksimal 2 MB dan harus berupa gambar PNG, JPG, atau WebP.');
            }
            if ($pesan = Identitas::simpanLogo($logo->getTempName())) {
                return redirect()->to('/pengaturan')->with('errors', $pesan);
            }
        } elseif ($this->request->getPost('hapus_logo')) {
            Identitas::hapusLogo();
        }

        Cadangan::simpanOtomatis((string) session('user_name'));

        return redirect()->to('/pengaturan')->with('success', 'Identitas perusahaan disimpan.');
    }

    /** Logo untuk browser; terbuka tanpa login karena tampil di halaman masuk. */
    public function logo()
    {
        $p = Identitas::berkasLogo();
        if (! $p) {
            return $this->response->setStatusCode(404);
        }

        return $this->response
            ->setContentType('image/png')
            ->setHeader('Cache-Control', 'public, max-age=31536000')
            ->setBody((string) file_get_contents($p));
    }
}

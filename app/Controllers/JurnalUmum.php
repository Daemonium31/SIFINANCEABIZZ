<?php

namespace App\Controllers;

use App\Models\JUModel;

class JurnalUmum extends JurnalBase
{
    protected array $cfg = [
        'tabel'    => 'jurnal_umum',
        'id'       => 'no_transaksi',
        'tgl'      => 'tgl_transaksi',
        'ket'      => 'keterangan_transaksi',
        'maks_ket' => 100,
        'prefix'   => 'JU',
        'url'      => '/jurnal_umum',
        'judul'    => 'Jurnal Umum',
        'nav'      => 'ju',
        'sumber'   => 'ju',
    ];

    protected function model()
    {
        return new JUModel();
    }
}

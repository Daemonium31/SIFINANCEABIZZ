<?php

namespace App\Controllers;

use App\Models\JPModel;

class JurnalPenyesuaian extends JurnalBase
{
    protected array $cfg = [
        'tabel'    => 'jurnal_penyesuaian',
        'id'       => 'id_penyesuaian',
        'tgl'      => 'tgl_penyesuaian',
        'ket'      => 'keterangan_penyesuaian',
        'maks_ket' => 255,
        'prefix'   => 'JP',
        'url'      => '/jurnal_penyesuaian',
        'judul'    => 'Jurnal Penyesuaian',
        'nav'      => 'jp',
        'sumber'   => 'jp',
    ];

    protected function model()
    {
        return new JPModel();
    }
}

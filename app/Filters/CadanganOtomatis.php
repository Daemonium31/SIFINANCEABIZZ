<?php

namespace App\Filters;

use App\Libraries\Cadangan;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Setelah setiap perubahan data (POST pada akun, jurnal, pengguna, profil),
 * berkas cadangan otomatis ditimpa dengan data terbaru. Kegagalan hanya
 * dicatat ke log dan tidak mengganggu pekerjaan pengguna.
 */
class CadanganOtomatis implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        if (strtoupper($request->getMethod()) !== 'POST' || ! session('logged_in')) {
            return;
        }

        try {
            if (! Cadangan::simpanOtomatis((string) session('user_name'))) {
                log_message('error', 'Cadangan otomatis gagal ditulis ke ' . Cadangan::berkasOtomatis());
            }
        } catch (\Throwable $e) {
            log_message('error', 'Cadangan otomatis gagal: ' . $e->getMessage());
        }
    }
}

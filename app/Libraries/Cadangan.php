<?php

namespace App\Libraries;

/**
 * Format berkas cadangan dan pemulihannya, dipakai oleh cadangan manual
 * (diunduh pengguna) maupun cadangan otomatis (satu berkas yang selalu ditimpa).
 * Keduanya memakai format yang sama sehingga sama-sama bisa dipulihkan.
 */
class Cadangan
{
    public const APLIKASI = 'sistem-informasi-keuangan';

    /** Penanda cadangan versi awal aplikasi, tetap diterima saat pemulihan. */
    public const APLIKASI_LAMA = ['cyber-evo-keuangan'];

    public const TABEL = [
        'akun'               => ['no_akun', 'nama_akun', 'keterangan', 'created_at', 'updated_at'],
        'jurnal_umum'        => ['no_transaksi', 'tgl_transaksi', 'keterangan_transaksi', 'no_akun', 'debit', 'kredit', 'nip', 'created_at', 'updated_at'],
        'jurnal_penyesuaian' => ['id_penyesuaian', 'tgl_penyesuaian', 'keterangan_penyesuaian', 'no_akun', 'debit', 'kredit', 'nip', 'created_at', 'updated_at'],
        'users'              => ['nip', 'username', 'email', 'password', 'jenis_user', 'created_at', 'updated_at'],
    ];

    /** Lokasi satu-satunya berkas cadangan otomatis. */
    public static function berkasOtomatis(): string
    {
        return WRITEPATH . 'cadangan' . DIRECTORY_SEPARATOR . 'cadangan-otomatis.json';
    }

    public static function ekspor(string $jenis, string $oleh): array
    {
        $db  = db_connect();
        $isi = ['aplikasi' => self::APLIKASI, 'versi' => 1, 'jenis' => $jenis, 'dibuat' => date('c'), 'oleh' => $oleh, 'tabel' => []];
        foreach (self::TABEL as $t => $kolom) {
            $isi['tabel'][$t] = $db->table($t)->select(implode(',', $kolom))->get()->getResultArray();
        }
        $isi['identitas'] = Identitas::ekspor();

        return $isi;
    }

    public static function json(array $isi): string
    {
        return json_encode($isi, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Menimpa berkas cadangan otomatis. Ditulis ke berkas sementara lalu
     * diganti namanya, sehingga berkas lama tetap utuh bila penulisan gagal.
     */
    public static function simpanOtomatis(string $oleh = ''): bool
    {
        $tujuan = self::berkasOtomatis();
        if (! is_dir(dirname($tujuan)) && ! mkdir(dirname($tujuan), 0775, true)) {
            return false;
        }
        $sementara = $tujuan . '.tmp';
        if (file_put_contents($sementara, self::json(self::ekspor('otomatis', $oleh)), LOCK_EX) === false) {
            return false;
        }
        if (is_file($tujuan)) {
            @unlink($tujuan);
        }

        return rename($sementara, $tujuan);
    }

    /** @return array{ada:bool, dibuat?:string, ukuran?:int, jumlah?:array} */
    public static function infoOtomatis(): array
    {
        $isi = self::baca((string) @file_get_contents(self::berkasOtomatis()));
        if (! is_array($isi) || self::periksa($isi) !== null) {
            return ['ada' => false];
        }

        return [
            'ada'    => true,
            'dibuat' => (string) $isi['dibuat'],
            'ukuran' => (int) filesize(self::berkasOtomatis()),
            'jumlah' => array_map('count', $isi['tabel']),
        ];
    }

    /** Membaca teks berkas cadangan (BOM UTF-8 diabaikan). */
    public static function baca(string $teks): ?array
    {
        $teks = preg_replace('/^\xEF\xBB\xBF/', '', $teks);
        $isi  = json_decode((string) $teks, true);

        return is_array($isi) ? $isi : null;
    }

    /** Pesan kesalahan bila isi bukan cadangan yang sah, atau null bila sah. */
    public static function periksa(?array $isi): ?string
    {
        $aplikasi = $isi['aplikasi'] ?? '';
        if (! is_array($isi) || ($aplikasi !== self::APLIKASI && ! in_array($aplikasi, self::APLIKASI_LAMA, true)) || ! is_array($isi['tabel'] ?? null)) {
            return 'Berkas ini bukan cadangan Sistem Informasi Keuangan.';
        }
        foreach (array_keys(self::TABEL) as $t) {
            if (isset($isi['tabel'][$t]) && ! is_array($isi['tabel'][$t])) {
                return "Bagian {$t} pada berkas cadangan rusak.";
            }
        }
        if (empty($isi['tabel']['users'])) {
            return 'Cadangan tidak berisi pengguna, pemulihan dibatalkan agar Anda tidak terkunci.';
        }

        return null;
    }

    /** Mengganti seluruh data dengan isi cadangan dalam satu transaksi. */
    public static function pulihkan(array $isi): bool
    {
        $db = db_connect();
        $db->transStart();
        foreach (self::TABEL as $t => $kolom) {
            $db->table($t)->emptyTable();
            foreach (array_chunk((array) ($isi['tabel'][$t] ?? []), 200) as $potong) {
                $baris = array_map(static fn ($r) => array_intersect_key((array) $r, array_flip($kolom)), $potong);
                if ($baris) {
                    $db->table($t)->insertBatch($baris);
                }
            }
        }
        $db->transComplete();

        if ($db->transStatus() && isset($isi['identitas'])) {
            Identitas::impor($isi['identitas']);
        }

        return $db->transStatus();
    }
}

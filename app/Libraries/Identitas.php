<?php

namespace App\Libraries;

/**
 * Identitas perusahaan pemakai aplikasi: nama dan logo. Diatur administrator
 * di halaman Identitas Perusahaan, dipakai di tampilan, laporan, dan PDF.
 *
 * Nama disimpan di tabel `pengaturan`; logo disimpan sebagai PNG di
 * writable/identitas/logo.png (folder data, ikut aplikasi desktop).
 */
class Identitas
{
    public const NAMA_BAWAAN = 'Nama Perusahaan';

    private static ?array $cache = null;

    public static function nama(): string
    {
        return self::muat()['nama'];
    }

    /** Path berkas logo, atau null bila belum diunggah. */
    public static function berkasLogo(): ?string
    {
        $p = WRITEPATH . 'identitas' . DIRECTORY_SEPARATOR . 'logo.png';

        return is_file($p) ? $p : null;
    }

    /** URL logo untuk browser (dengan penanda versi), atau null. */
    public static function urlLogo(): ?string
    {
        $p = self::berkasLogo();

        return $p ? '/identitas/logo?v=' . filemtime($p) : null;
    }

    /** Logo sebagai data URI untuk PDF (Dompdf tidak membaca URL aplikasi). */
    public static function dataLogo(): ?string
    {
        $p = self::berkasLogo();

        return $p ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($p)) : null;
    }

    /** Dua huruf awal nama, dipakai bila belum ada logo. */
    public static function inisial(): string
    {
        $kata = preg_split('/\s+/', trim(preg_replace('/^(PT|CV|UD|PD|Koperasi)\.?\s+/i', '', self::nama()))) ?: ['?'];
        $huruf = mb_substr($kata[0], 0, 1) . (isset($kata[1]) ? mb_substr($kata[1], 0, 1) : mb_substr($kata[0], 1, 1));

        return mb_strtoupper($huruf ?: '?');
    }

    /** Nama aman untuk nama berkas, mis. "PT Maju Jaya" → "PT-Maju-Jaya". */
    public static function slug(): string
    {
        $s = trim(preg_replace('/[^A-Za-z0-9]+/', '-', self::nama()), '-');

        return $s !== '' ? $s : 'Keuangan';
    }

    public static function simpanNama(string $nama): void
    {
        $db   = db_connect();
        $baris = ['kunci' => 'nama_perusahaan', 'nilai' => $nama, 'updated_at' => date('Y-m-d H:i:s')];
        if ($db->table('pengaturan')->where('kunci', 'nama_perusahaan')->countAllResults() > 0) {
            $db->table('pengaturan')->where('kunci', 'nama_perusahaan')->update($baris);
        } else {
            $db->table('pengaturan')->insert($baris);
        }
        self::$cache = null;
    }

    /**
     * Menyimpan logo dari berkas gambar (PNG/JPG/WebP). Gambar digambar ulang
     * sebagai PNG maksimal 512 px, sehingga kecil, aman, dan terbaca Dompdf.
     *
     * @return string|null pesan kesalahan, atau null bila berhasil
     */
    public static function simpanLogo(string $berkasSumber): ?string
    {
        $info = @getimagesize($berkasSumber);
        $buka = [IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_WEBP => 'imagecreatefromwebp'];
        if (! $info || ! isset($buka[$info[2]]) || ! function_exists($buka[$info[2]])) {
            return 'Logo harus berupa gambar PNG, JPG, atau WebP.';
        }
        $sumber = @$buka[$info[2]]($berkasSumber);
        if (! $sumber) {
            return 'Gambar logo tidak bisa dibaca.';
        }

        // Buang tepi polos (transparan atau putih) agar logo tampil sebesar mungkin
        imagesavealpha($sumber, true);
        $terpotong = @imagecropauto($sumber, IMG_CROP_SIDES);
        if ($terpotong !== false && imagesx($terpotong) > 8 && imagesy($terpotong) > 8) {
            $sumber = $terpotong;
        }

        [$w, $h] = [imagesx($sumber), imagesy($sumber)];
        $skala   = min(1, 512 / max($w, $h));
        $nw      = max(1, (int) round($w * $skala));
        $nh      = max(1, (int) round($h * $skala));
        $hasil   = imagecreatetruecolor($nw, $nh);
        imagesavealpha($hasil, true);
        imagealphablending($hasil, false);
        imagefill($hasil, 0, 0, imagecolorallocatealpha($hasil, 0, 0, 0, 127));
        imagecopyresampled($hasil, $sumber, 0, 0, 0, 0, $nw, $nh, $w, $h);

        $folder = WRITEPATH . 'identitas';
        if (! is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
        $ok = imagepng($hasil, $folder . DIRECTORY_SEPARATOR . 'logo.png', 9);

        return $ok ? null : 'Logo gagal disimpan.';
    }

    public static function hapusLogo(): void
    {
        if ($p = self::berkasLogo()) {
            @unlink($p);
        }
    }

    /** Untuk cadangan data: nama dan logo (base64). */
    public static function ekspor(): array
    {
        $p = self::berkasLogo();

        return ['nama' => self::nama(), 'logo_png_base64' => $p ? base64_encode((string) file_get_contents($p)) : null];
    }

    /** Dari cadangan data. Data yang tidak sah diabaikan. */
    public static function impor(?array $isi): void
    {
        if (! is_array($isi)) {
            return;
        }
        if (is_string($isi['nama'] ?? null) && trim($isi['nama']) !== '') {
            self::simpanNama(mb_substr(trim($isi['nama']), 0, 100));
        }
        self::hapusLogo();
        $png = is_string($isi['logo_png_base64'] ?? null) ? base64_decode($isi['logo_png_base64'], true) : false;
        if ($png) {
            $tmp = tempnam(sys_get_temp_dir(), 'logo');
            file_put_contents($tmp, $png);
            self::simpanLogo($tmp);
            @unlink($tmp);
        }
    }

    private static function muat(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $nama = null;
        try {
            $baris = db_connect()->table('pengaturan')->where('kunci', 'nama_perusahaan')->get()->getRowArray();
            $nama  = $baris['nilai'] ?? null;
        } catch (\Throwable $e) {
            // Tabel belum dimigrasi: pakai nama bawaan
        }

        return self::$cache = ['nama' => trim((string) $nama) !== '' ? trim((string) $nama) : self::NAMA_BAWAAN];
    }
}

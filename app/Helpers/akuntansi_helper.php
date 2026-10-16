<?php

/**
 * Format angka akuntansi Indonesia: 1.250.000, nilai negatif dalam kurung.
 * Nilai nol ditampilkan kosong bila $kosongJikaNol = true (kolom debit/kredit).
 */
function angka($nilai, bool $kosongJikaNol = false): string
{
    if ($kosongJikaNol && (float) $nilai == 0) {
        return '';
    }

    $teks = number_format(abs((float) $nilai), 0, ',', '.');

    return $nilai < 0 ? '(' . $teks . ')' : $teks;
}

function rupiah($nilai): string
{
    $teks = 'Rp ' . number_format(abs((float) $nilai), 0, ',', '.');

    return $nilai < 0 ? '(' . $teks . ')' : $teks;
}

/** Rupiah ringkas untuk kartu & grafik: Rp 1,25 M / Rp 350 jt / Rp 12 rb. */
function rupiah_singkat($nilai): string
{
    $abs = abs((float) $nilai);
    if ($abs >= 1e12) {
        $teks = number_format($abs / 1e12, 2, ',', '.') . ' T';
    } elseif ($abs >= 1e9) {
        $teks = number_format($abs / 1e9, 2, ',', '.') . ' M';
    } elseif ($abs >= 1e6) {
        $teks = number_format($abs / 1e6, 1, ',', '.') . ' jt';
    } elseif ($abs >= 1e3) {
        $teks = number_format($abs / 1e3, 0, ',', '.') . ' rb';
    } else {
        $teks = number_format($abs, 0, ',', '.');
    }

    return ($nilai < 0 ? '-' : '') . 'Rp ' . $teks;
}

function nama_bulan(int $bulan, bool $singkat = false): string
{
    $nama = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    return $singkat ? substr($nama[$bulan], 0, 3) : $nama[$bulan];
}

/** 2026-10-31 → 31 Oktober 2026 */
function tgl_indo(string $tanggal, bool $singkat = false): string
{
    $t = strtotime($tanggal);

    return date('j', $t) . ' ' . nama_bulan((int) date('n', $t), $singkat) . ' ' . date('Y', $t);
}

/** 2026-10 → Oktober 2026 */
function bulan_indo(string $bulan, bool $singkat = false): string
{
    $t = strtotime($bulan . '-01');

    return nama_bulan((int) date('n', $t), $singkat) . ' ' . date('Y', $t);
}

/** Label periode: "Oktober 2026" atau "1 Januari 2026 - 31 Maret 2026". */
function periode_indo(string $mulai, string $selesai): string
{
    if (date('Y-m', strtotime($mulai)) === date('Y-m', strtotime($selesai))
        && date('j', strtotime($mulai)) === '1'
        && date('t', strtotime($selesai)) === date('j', strtotime($selesai))) {
        return bulan_indo(date('Y-m', strtotime($mulai)));
    }

    return tgl_indo($mulai) . ' - ' . tgl_indo($selesai);
}

/** Persentase perubahan terhadap periode pembanding, null bila tidak dapat dihitung. */
function persen_perubahan($sekarang, $sebelum): ?float
{
    if ((float) $sebelum == 0) {
        return null;
    }

    return ($sekarang - $sebelum) / abs($sebelum) * 100;
}

/** Nama perusahaan dari Identitas Perusahaan (diatur administrator). */
function nama_perusahaan(): string
{
    return \App\Libraries\Identitas::nama();
}

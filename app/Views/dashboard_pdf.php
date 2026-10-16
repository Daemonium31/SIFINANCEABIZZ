<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Ringkasan Keuangan · <?= esc(nama_perusahaan()) ?></title>
<style>
    @font-face { font-family: 'Source Sans 3'; font-weight: normal; src: url('fonts/source-sans-3-latin-400-normal.ttf') format('truetype'); }
    @font-face { font-family: 'Source Sans 3'; font-weight: bold; src: url('fonts/source-sans-3-latin-700-normal.ttf') format('truetype'); }
    @font-face { font-family: 'DM Sans'; font-weight: bold; src: url('fonts/dm-sans-latin-700-normal.ttf') format('truetype'); }
    @page { margin: 34px 36px 46px; }
    body { font-family: 'Source Sans 3', 'DejaVu Sans', sans-serif; font-size: 9.6pt; color: #172a3b; }
    h1, h2, .fig b { font-family: 'DM Sans', 'DejaVu Sans', sans-serif; }
    .head { border-bottom: 1.4px solid #172a3b; padding-bottom: 10px; margin-bottom: 14px; }
    .head img { width: 38px; height: 38px; float: left; margin-right: 12px; }
    .head h1 { font-size: 17pt; margin: 0; }
    .head p { margin: 2px 0 0; color: #5a6a72; }
    table { width: 100%; border-collapse: collapse; }
    .figs td { width: 25%; padding: 10px 12px; border: 0.6px solid #d8d9d1; vertical-align: top; }
    .fig span { color: #5a6a72; font-size: 8.4pt; }
    .fig b { display: block; font-size: 13pt; margin-top: 3px; }
    .fig small { color: #5a6a72; font-size: 7.8pt; }
    h2 { font-size: 11pt; margin: 16px 0 6px; }
    .chart { width: 100%; border: 0.6px solid #e4e4dd; }
    .two td { width: 50%; vertical-align: top; }
    .two td:first-child { padding-right: 9px; }
    .two td:last-child { padding-left: 9px; }
    .rows td { padding: 3px 4px; border-bottom: 0.5px solid #e4e4dd; }
    .rows .num { text-align: right; white-space: nowrap; }
    .rows tr.total td { font-weight: bold; border-top: 0.8px solid #172a3b; border-bottom: 2.2px double #172a3b; }
    .note { color: #5a6a72; font-size: 8pt; margin-top: 12px; }
    .pb { page-break-before: always; }
</style>
</head>
<body>
<div class="head">
    <?php if ($logo = \App\Libraries\Identitas::dataLogo()): ?><img src="<?= $logo ?>" alt=""><?php endif ?>
    <h1>Ringkasan Keuangan <?= esc(nama_perusahaan()) ?></h1>
    <p>Periode <?= esc($periode) ?> · disiapkan oleh <?= esc($pembuat) ?></p>
</div>

<table class="figs">
    <tr>
        <td class="fig"><span>Pendapatan</span><b><?= rupiah($lr['pendapatan']['total']) ?></b><small><?= ($p = persen_perubahan($lr['pendapatan']['total'], $lrSebelum['pendapatan']['total'])) === null ? 'Belum ada pembanding' : ($p >= 0 ? 'Naik ' : 'Turun ') . number_format(abs($p), 1, ',', '.') . '% dari ' . esc($pembanding) ?></small></td>
        <td class="fig"><span>Beban</span><b><?= rupiah($lr['beban']['total']) ?></b><small><?= ($p = persen_perubahan($lr['beban']['total'], $lrSebelum['beban']['total'])) === null ? 'Belum ada pembanding' : ($p >= 0 ? 'Naik ' : 'Turun ') . number_format(abs($p), 1, ',', '.') . '% dari ' . esc($pembanding) ?></small></td>
        <td class="fig"><span><?= $lr['laba_bersih'] >= 0 ? 'Laba bersih' : 'Rugi bersih' ?></span><b><?= rupiah($lr['laba_bersih']) ?></b><small><?= $lr['margin'] !== null ? 'Margin ' . number_format($lr['margin'], 1, ',', '.') . '%' : '' ?></small></td>
        <td class="fig"><span>Saldo kas &amp; bank</span><b><?= rupiah($kas['saldo_akhir']) ?></b><small>Masuk <?= rupiah_singkat($kas['masuk']) ?> · keluar <?= rupiah_singkat($kas['keluar']) ?></small></td>
    </tr>
</table>

<table class="two">
    <tr>
        <td>
            <h2>Laba rugi</h2>
            <?php if (isset($gambar['labarugi'])): ?><img class="chart" src="<?= $gambar['labarugi'] ?>" alt=""><?php endif ?>
            <table class="rows">
                <tr><td>Pendapatan</td><td class="num"><?= rupiah($lr['pendapatan']['total']) ?></td></tr>
                <tr><td>Beban</td><td class="num"><?= rupiah(-$lr['beban']['total']) ?></td></tr>
                <tr class="total"><td><?= $lr['laba_bersih'] >= 0 ? 'Laba bersih' : 'Rugi bersih' ?></td><td class="num"><?= rupiah($lr['laba_bersih']) ?></td></tr>
            </table>
        </td>
        <td>
            <h2>Beban terbesar</h2>
            <table class="rows">
                <?php foreach ($komposisi as $k): ?>
                    <tr><td style="width:56%"><?= esc($k['nama_akun']) ?></td><td class="num"><?= rupiah($k['nilai']) ?></td><td class="num" style="color:#5a6a72"><?= number_format($k['nilai'] / max($lr['beban']['total'], 1) * 100, 1, ',', '.') ?>%</td></tr>
                <?php endforeach ?>
                <?php if (! $komposisi): ?><tr><td>Belum ada beban.</td></tr><?php endif ?>
            </table>
        </td>
    </tr>
</table>

<h2>Tren pendapatan &amp; beban, 6 bulan</h2>
<?php if (isset($gambar['tren'])): ?><img class="chart" src="<?= $gambar['tren'] ?>" alt=""><?php endif ?>

<div class="pb"></div>
<h2>Arus kas · <?= esc($periode) ?></h2>
<?php if (isset($gambar['kas_saldo'])): ?><img class="chart" src="<?= $gambar['kas_saldo'] ?>" alt=""><?php endif ?>
<?php if (isset($gambar['kas_mutasi'])): ?><img class="chart" src="<?= $gambar['kas_mutasi'] ?>" alt="" style="margin-top:6px"><?php endif ?>
<table class="rows" style="margin-top:6px">
    <tr><td>Saldo awal</td><td class="num"><?= rupiah($kas['saldo_awal']) ?></td><td>Kas masuk</td><td class="num"><?= rupiah($kas['masuk']) ?></td><td>Kas keluar</td><td class="num"><?= rupiah($kas['keluar']) ?></td><td><b>Saldo akhir</b></td><td class="num"><b><?= rupiah($kas['saldo_akhir']) ?></b></td></tr>
</table>

<h2>Posisi keuangan per <?= tgl_indo($selesai) ?></h2>
<?php if (isset($gambar['posisi'])): ?><img class="chart" src="<?= $gambar['posisi'] ?>" alt=""><?php endif ?>
<table class="rows" style="margin-top:6px">
    <tr><td>Total aktiva</td><td class="num"><?= rupiah($pk['total_aktiva']) ?></td><td>Total utang</td><td class="num"><?= rupiah($pk['total_utang']) ?></td><td>Ekuitas</td><td class="num"><?= rupiah($pk['modal']) ?></td></tr>
    <tr><td>Rasio lancar</td><td class="num"><?= $rasio['lancar'] !== null ? number_format($rasio['lancar'], 2, ',', '.') . '×' : '–' ?></td><td>Utang terhadap ekuitas</td><td class="num"><?= $rasio['der'] !== null ? number_format($rasio['der'], 2, ',', '.') . '×' : '–' ?></td><td>Margin laba</td><td class="num"><?= $rasio['margin'] !== null ? number_format($rasio['margin'], 1, ',', '.') . '%' : '–' ?></td></tr>
</table>

<p class="note">Angka sudah termasuk jurnal penyesuaian. Rincian lengkap tersedia di paket laporan siklus akuntansi.</p>
</body>
</html>

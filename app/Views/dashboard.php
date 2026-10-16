<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>
<?php
$pend   = $lr['pendapatan']['total'];
$beban  = $lr['beban']['total'];
$laba   = $lr['laba_bersih'];
$delta  = static function ($kini, $lalu, bool $naikBaik = true) use ($pembanding) {
    $p = persen_perubahan($kini, $lalu);
    if ($p === null) {
        return '<span class="figure-note">Belum ada data ' . esc($pembanding) . '</span>';
    }
    $baik  = $naikBaik ? $p >= 0 : $p <= 0;
    $tanda = $p >= 0 ? 'naik' : 'turun';

    return '<span class="delta ' . ($baik ? 'up' : 'down') . '">' . ucfirst($tanda) . ' ' . number_format(abs($p), 1, ',', '.') . '%</span> <span class="figure-note">dari ' . esc($pembanding) . '</span>';
};
$kasLabel = static function (string $l, string $satuan) {
    $t = strtotime($satuan === 'bulan' ? $l . '-01' : $l);

    return $satuan === 'bulan' ? nama_bulan((int) date('n', $t), true) . ' ' . date('Y', $t) : date('j', $t) . ' ' . nama_bulan((int) date('n', $t), true);
};
$grafik = [
    'warna' => [
        'pendapatan' => '#2f62c0', 'beban' => '#e8603f', 'laba' => '#2f9a6a',
        'aset_tetap' => '#c9961f', 'utang_panjang' => '#8a63c9',
    ],
    'labarugi' => ['pendapatan' => $pend, 'beban' => $beban, 'laba' => $laba],
    'kas' => [
        'label'  => array_map(static fn ($t) => $kasLabel($t['label'], $kas['satuan']), $kas['titik']),
        'masuk'  => array_column($kas['titik'], 'masuk'),
        'keluar' => array_map(static fn ($v) => -$v, array_column($kas['titik'], 'keluar')),
        'saldo'  => array_column($kas['titik'], 'saldo'),
    ],
    'tren' => [
        'label'      => array_map(static fn ($t) => bulan_indo($t['bulan'], true), $tren),
        'pendapatan' => array_column($tren, 'pendapatan'),
        'beban'      => array_column($tren, 'beban'),
        'laba'       => array_column($tren, 'laba'),
    ],
    'posisi' => [
        'aset_lancar'   => $pk['aset_lancar']['total'],
        'aset_tetap'    => $pk['aset_tetap']['total'],
        'utang_pendek'  => $pk['utang_pendek']['total'],
        'utang_panjang' => $pk['utang_panjang']['total'],
        'ekuitas'       => $pk['modal'],
    ],
];
$totalBeban = max($beban, 1);
$q = http_build_query($filter);
?>

<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p>Ringkasan keuangan <?= esc(nama_perusahaan()) ?> untuk <?= esc($periode) ?>. Semua angka sudah termasuk jurnal penyesuaian.</p>
    </div>
    <div class="page-actions">
        <button class="btn" type="button" data-unduh-pdf <?= $adaData ? '' : 'disabled' ?>><?= ikon('file-down', 16) ?>Unduh ringkasan PDF</button>
        <button class="btn btn-primary" type="button" data-presentasi <?= $adaData ? '' : 'disabled' ?>><?= ikon('presentation', 16) ?>Mode presentasi</button>
    </div>
</div>

<form class="panel toolbar" method="get" action="/" style="margin-bottom:18px">
    <label class="field"><span>Dari bulan</span><input class="input" type="month" name="start" value="<?= esc($filter['start']) ?>" required></label>
    <label class="field"><span>Sampai bulan</span><input class="input" type="month" name="end" value="<?= esc($filter['end']) ?>" required></label>
    <button class="btn btn-ink" type="submit">Tampilkan</button>
    <span class="spacer"></span>
    <a class="btn btn-quiet" href="/laporan?<?= $q ?>&jenis=laporan_keuangan"><?= ikon('files', 16) ?>Buka laporan keuangan</a>
</form>

<?php if (! $adaData): ?>
    <div class="panel empty">
        <h3>Belum ada transaksi pada <?= esc($periode) ?></h3>
        <p>Grafik muncul setelah jurnal umum dicatat. Mulai dari daftar akun, lalu catat transaksi pertama.</p>
        <?php if (session('user_role') !== '3'): ?>
            <div class="page-actions" style="justify-content:center">
                <a class="btn" href="/akun"><?= ikon('list-tree', 16) ?>Susun daftar akun</a>
                <a class="btn btn-primary" href="/jurnal_umum"><?= ikon('plus', 16) ?>Catat jurnal umum</a>
            </div>
        <?php endif ?>
    </div>
<?php else: ?>

<section class="figures" aria-label="Angka utama">
    <div class="figure">
        <div class="figure-label"><i style="background:#2f62c0"></i>Pendapatan</div>
        <div class="figure-value"><?= rupiah($pend) ?></div>
        <?= $delta($pend, $lrSebelum['pendapatan']['total']) ?>
    </div>
    <div class="figure">
        <div class="figure-label"><i style="background:#e8603f"></i>Beban</div>
        <div class="figure-value"><?= rupiah($beban) ?></div>
        <?= $delta($beban, $lrSebelum['beban']['total'], false) ?>
    </div>
    <div class="figure">
        <div class="figure-label"><i style="background:#2f9a6a"></i><?= $laba >= 0 ? 'Laba bersih' : 'Rugi bersih' ?></div>
        <div class="figure-value<?= $laba < 0 ? ' neg' : '' ?>"><?= rupiah($laba) ?></div>
        <span class="figure-note"><?= $lr['margin'] !== null ? 'Margin ' . number_format($lr['margin'], 1, ',', '.') . '% dari pendapatan' : 'Belum ada pendapatan' ?></span>
    </div>
    <div class="figure">
        <div class="figure-label">Saldo kas &amp; bank</div>
        <div class="figure-value<?= $kas['saldo_akhir'] < 0 ? ' neg' : '' ?>"><?= rupiah($kas['saldo_akhir']) ?></div>
        <?php $gerak = $kas['saldo_akhir'] - $kas['saldo_awal']; ?>
        <span class="delta <?= $gerak >= 0 ? 'up' : 'down' ?>"><?= $gerak >= 0 ? 'Bertambah' : 'Berkurang' ?> <?= rupiah_singkat(abs($gerak)) ?></span> <span class="figure-note">dari saldo awal <?= rupiah_singkat($kas['saldo_awal']) ?></span>
    </div>
</section>

<div class="widgets">
    <section class="panel w">
        <div class="panel-head">
            <div><h2>Laba rugi</h2><p><?= esc($periode) ?></p></div>
            <a class="btn btn-quiet btn-sm" href="/laporan?<?= $q ?>&jenis=laporan_keuangan">Detail<?= ikon('chevron-right', 14) ?></a>
        </div>
        <div class="panel-body">
            <div class="chart-box" style="height:170px"><canvas id="g-labarugi" role="img" aria-label="Pendapatan, beban dan laba bersih"></canvas></div>
            <div class="legend">
                <div class="legend-row"><i style="background:#2f62c0"></i><span>Pendapatan</span><b><?= rupiah($pend) ?></b><span class="pct">100%</span></div>
                <div class="legend-row"><i style="background:#e8603f"></i><span>Beban</span><b><?= rupiah($beban) ?></b><span class="pct"><?= $pend > 0 ? number_format($beban / $pend * 100, 1, ',', '.') . '%' : '–' ?></span></div>
                <div class="legend-row"><i style="background:#2f9a6a"></i><span><?= $laba >= 0 ? 'Laba bersih' : 'Rugi bersih' ?></span><b><?= rupiah($laba) ?></b><span class="pct"><?= $lr['margin'] !== null ? number_format($lr['margin'], 1, ',', '.') . '%' : '–' ?></span></div>
            </div>
        </div>
    </section>

    <section class="panel w w-8">
        <div class="panel-head">
            <div><h2>Arus kas</h2><p>Kas &amp; bank per <?= $kas['satuan'] ?>, <?= esc($periode) ?></p></div>
            <a class="btn btn-quiet btn-sm" href="/laporan?<?= $q ?>&jenis=buku_besar">Buku besar<?= ikon('chevron-right', 14) ?></a>
        </div>
        <div class="panel-body">
            <div class="kas-sum">
                <span>Saldo awal<b><?= rupiah($kas['saldo_awal']) ?></b></span>
                <span>Kas masuk<b><?= rupiah($kas['masuk']) ?></b></span>
                <span>Kas keluar<b><?= rupiah($kas['keluar']) ?></b></span>
                <span>Saldo akhir<b><?= rupiah($kas['saldo_akhir']) ?></b></span>
            </div>
            <div class="chart-box" style="height:150px"><canvas id="g-kas-saldo" role="img" aria-label="Saldo kas harian"></canvas></div>
            <div class="chart-box" style="height:130px; margin-top:6px"><canvas id="g-kas-mutasi" role="img" aria-label="Kas masuk dan keluar"></canvas></div>
        </div>
    </section>

    <section class="panel w w-8">
        <div class="panel-head">
            <div><h2>Tren pendapatan &amp; beban</h2><p>6 bulan sampai <?= bulan_indo($filter['end']) ?></p></div>
        </div>
        <div class="panel-body">
            <div class="chart-box tall"><canvas id="g-tren" role="img" aria-label="Tren pendapatan, beban dan laba enam bulan"></canvas></div>
            <details style="margin-top:10px">
                <summary class="btn btn-quiet btn-sm">Tampilkan tabel</summary>
                <table class="tbl" style="margin-top:8px">
                    <thead><tr><th>Bulan</th><th class="num">Pendapatan</th><th class="num">Beban</th><th class="num">Laba</th></tr></thead>
                    <tbody>
                    <?php foreach ($tren as $t): ?>
                        <tr><td><?= bulan_indo($t['bulan']) ?></td><td class="num"><?= rupiah($t['pendapatan']) ?></td><td class="num"><?= rupiah($t['beban']) ?></td><td class="num"><?= rupiah($t['laba']) ?></td></tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </details>
        </div>
    </section>

    <section class="panel w">
        <div class="panel-head">
            <div><h2>Beban perusahaan</h2><p>Komposisi <?= rupiah($beban) ?></p></div>
        </div>
        <div class="panel-body">
            <?php if (! $komposisi): ?>
                <div class="no-data">Belum ada beban pada periode ini.</div>
            <?php else: ?>
                <div class="bar-list">
                    <?php foreach ($komposisi as $k): ?>
                        <div class="bar-item">
                            <span><?= esc($k['nama_akun']) ?></span>
                            <b><?= rupiah($k['nilai']) ?></b>
                            <div class="bar-track"><div class="bar-fill" style="width:<?= max(1, round($k['nilai'] / $totalBeban * 100, 1)) ?>%; background:#e8603f"></div></div>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
    </section>

    <section class="panel w w-12">
        <div class="panel-head">
            <div><h2>Posisi keuangan</h2><p>Per <?= tgl_indo($selesai) ?></p></div>
            <span class="chip <?= $pk['seimbang'] ? 'chip-ok' : 'chip-bad' ?>"><?= ikon($pk['seimbang'] ? 'circle-check' : 'triangle-alert', 14) ?><?= $pk['seimbang'] ? 'Aktiva = Pasiva' : 'Tidak seimbang' ?></span>
        </div>
        <div class="panel-body">
            <div class="chart-box" style="height:150px"><canvas id="g-posisi" role="img" aria-label="Komposisi aktiva dan pasiva"></canvas></div>
            <div class="ratios">
                <div class="ratio"><b><?= $rasio['lancar'] !== null ? number_format($rasio['lancar'], 2, ',', '.') . '×' : '–' ?></b><span>Rasio lancar (aktiva lancar ÷ utang jangka pendek)</span></div>
                <div class="ratio"><b><?= $rasio['der'] !== null ? number_format($rasio['der'], 2, ',', '.') . '×' : '–' ?></b><span>Utang terhadap ekuitas</span></div>
                <div class="ratio"><b><?= $rasio['margin'] !== null ? number_format($rasio['margin'], 1, ',', '.') . '%' : '–' ?></b><span>Margin laba bersih</span></div>
            </div>
        </div>
    </section>
</div>

<form id="form-pdf" method="post" action="/dashboard/pdf" hidden>
    <input type="hidden" name="start" value="<?= esc($filter['start']) ?>">
    <input type="hidden" name="end" value="<?= esc($filter['end']) ?>">
    <?php foreach (['labarugi', 'tren', 'kas_saldo', 'kas_mutasi', 'posisi'] as $k): ?>
        <input type="hidden" name="grafik_<?= $k ?>">
    <?php endforeach ?>
</form>

<?= view('partials/presentasi', get_defined_vars()) ?>

<script type="application/json" id="data-grafik"><?= json_encode($grafik, JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<?php if ($adaData): ?>
    <script src="/vendor/chartjs/chart.umd.min.js"></script>
    <script src="/js/dashboard.js"></script>
<?php endif ?>
<?= $this->endSection() ?>

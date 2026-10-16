<?php
/** Mode presentasi: satu pesan per layar, dibuka dari dashboard. */
$persen = static fn ($v) => number_format($v, 1, ',', '.') . '%';
$akhir  = end($tren);
$lalu   = count($tren) > 1 ? $tren[count($tren) - 2] : null;
$ubahPendapatan = $lalu ? persen_perubahan($akhir['pendapatan'], $lalu['pendapatan']) : null;
$kasNaik = $kas['saldo_akhir'] - $kas['saldo_awal'];
$bebanUtama = $komposisi[0] ?? null;
$slides = 6;
?>
<div class="deck" id="deck" role="dialog" aria-modal="true" aria-label="Mode presentasi" hidden>
    <div class="deck-bar">
        <div class="deck-brand"><?= view('partials/merek') ?><?= esc(nama_perusahaan()) ?> · Ringkasan Keuangan <?= esc($periode) ?></div>
        <div style="display:flex; align-items:center; gap:14px">
            <span class="deck-count"><span data-deck-now>1</span> / <?= $slides ?></span>
            <button class="btn btn-sm" type="button" data-deck-close><?= ikon('x', 16) ?>Keluar (Esc)</button>
        </div>
    </div>
    <div class="deck-stage">
        <section class="slide slide-title is-current">
            <div style="margin-bottom:4vh"><?= view('partials/merek', ['ukuran' => 92]) ?></div>
            <h2>Ringkasan keuangan <?= esc($periode) ?></h2>
            <p class="lede">Disiapkan oleh <?= esc((string) session('user_name')) ?> · Tim Akuntansi <?= esc(nama_perusahaan()) ?></p>
            <div class="title-meta">
                <div><b><?= rupiah($lr['pendapatan']['total']) ?></b><span>Pendapatan</span></div>
                <div><b><?= rupiah($lr['laba_bersih']) ?></b><span><?= $lr['laba_bersih'] >= 0 ? 'Laba bersih' : 'Rugi bersih' ?></span></div>
                <div><b><?= rupiah($kas['saldo_akhir']) ?></b><span>Saldo kas &amp; bank</span></div>
            </div>
        </section>

        <section class="slide">
            <h2><?= $lr['laba_bersih'] >= 0 ? 'Laba bersih' : 'Rugi bersih' ?> <?= rupiah_singkat(abs($lr['laba_bersih'])) ?><?= $lr['margin'] !== null ? ', margin ' . $persen($lr['margin']) : '' ?></h2>
            <p class="lede">Pendapatan <?= rupiah($lr['pendapatan']['total']) ?> dikurangi beban <?= rupiah($lr['beban']['total']) ?>.</p>
            <div class="slide-chart"><canvas data-slide-chart="labarugi"></canvas></div>
        </section>

        <section class="slide">
            <h2><?php if ($ubahPendapatan === null): ?>Pendapatan dan beban 6 bulan terakhir<?php else: ?>Pendapatan <?= bulan_indo($akhir['bulan']) ?> <?= $ubahPendapatan >= 0 ? 'naik' : 'turun' ?> <?= $persen(abs($ubahPendapatan)) ?> dari bulan sebelumnya<?php endif ?></h2>
            <p class="lede">Batang menunjukkan pendapatan dan beban tiap bulan; garis hijau adalah laba bersih.</p>
            <div class="slide-chart"><canvas data-slide-chart="tren"></canvas></div>
        </section>

        <section class="slide">
            <h2><?php if ($kas['saldo_awal'] == 0): ?>Saldo kas akhir periode <?= rupiah_singkat($kas['saldo_akhir']) ?><?php else: ?>Saldo kas <?= $kasNaik >= 0 ? 'bertambah' : 'berkurang' ?> <?= rupiah_singkat(abs($kasNaik)) ?> menjadi <?= rupiah_singkat($kas['saldo_akhir']) ?><?php endif ?></h2>
            <p class="lede">Kas masuk <?= rupiah($kas['masuk']) ?>, kas keluar <?= rupiah($kas['keluar']) ?> selama <?= esc($periode) ?>.</p>
            <div class="slide-chart"><canvas data-slide-chart="kas"></canvas></div>
        </section>

        <section class="slide">
            <h2><?php if ($bebanUtama): ?><?= esc($bebanUtama['nama_akun']) ?> adalah beban terbesar, <?= $persen($bebanUtama['nilai'] / max($lr['beban']['total'], 1) * 100) ?> dari total beban<?php else: ?>Belum ada beban pada periode ini<?php endif ?></h2>
            <p class="lede">Total beban <?= rupiah($lr['beban']['total']) ?>, lima akun terbesar ditampilkan terpisah.</p>
            <div class="slide-chart"><canvas data-slide-chart="beban"></canvas></div>
        </section>

        <section class="slide">
            <h2>Total aktiva <?= rupiah_singkat($pk['total_aktiva']) ?>, didanai <?= $pk['total_pasiva'] > 0 ? $persen($pk['modal'] / $pk['total_pasiva'] * 100) : '–' ?> oleh ekuitas</h2>
            <p class="lede">Rasio lancar <?= $rasio['lancar'] !== null ? number_format($rasio['lancar'], 2, ',', '.') . '×' : '–' ?> · utang terhadap ekuitas <?= $rasio['der'] !== null ? number_format($rasio['der'], 2, ',', '.') . '×' : '–' ?> · per <?= tgl_indo($selesai) ?>.</p>
            <div class="slide-chart"><canvas data-slide-chart="posisi"></canvas></div>
        </section>
    </div>
    <div class="deck-nav">
        <button class="btn" type="button" data-deck-prev><?= ikon('chevron-left', 16) ?>Sebelumnya</button>
        <div class="deck-dots" role="tablist" aria-label="Pilih slide">
            <?php for ($i = 0; $i < $slides; $i++): ?>
                <button type="button" data-deck-go="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>" class="<?= $i === 0 ? 'is-current' : '' ?>"></button>
            <?php endfor ?>
        </div>
        <button class="btn btn-ink" type="button" data-deck-next>Berikutnya<?= ikon('chevron-right', 16) ?></button>
    </div>
</div>
<script type="application/json" id="data-beban"><?= json_encode(['label' => array_column($komposisi, 'nama_akun'), 'nilai' => array_column($komposisi, 'nilai')], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

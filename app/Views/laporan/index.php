<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>
<?php
$q = static function (array $ubah = []) use ($filter) {
    return http_build_query(array_merge(['start' => $filter['start'], 'end' => $filter['end'], 'jenis' => $filter['jenis']], $ubah));
};
$urutan = array_keys($daftar);
$posisi = array_search($filter['jenis'], $urutan, true);
?>
<div class="page-head">
    <div>
        <h1>Siklus Akuntansi</h1>
        <p>Sepuluh tahap laporan dari jurnal yang tercatat. Pilih tahap di kiri, atur periode, lalu unduh PDF.</p>
    </div>
    <div class="page-actions">
        <a class="btn" href="/laporan/pdf?<?= $q(['jenis' => 'semua']) ?>"><?= ikon('files', 16) ?>Unduh semua laporan (PDF)</a>
    </div>
</div>

<div class="reports">
    <aside class="panel cycle" aria-label="Tahap siklus akuntansi">
        <div class="cycle-title">Tahap siklus</div>
        <ol>
            <?php $n = 1; foreach ($daftar as $kunci => [$nama, $sub]): ?>
                <li>
                    <a href="/laporan?<?= $q(['jenis' => $kunci]) ?>" class="<?= $filter['jenis'] === $kunci ? 'is-active' : '' ?>"<?= $filter['jenis'] === $kunci ? ' aria-current="page"' : '' ?>>
                        <span class="step"><?= $n++ ?></span>
                        <span><?= $nama ?><span class="sub"><?= $sub ?></span></span>
                    </a>
                </li>
            <?php endforeach ?>
        </ol>
        <p class="cycle-note">Jurnal penutup dan jurnal balik dibentuk otomatis dari saldo periode, tidak perlu dicatat manual.</p>
    </aside>

    <div>
        <form class="panel report-tools" method="get" action="/laporan">
            <input type="hidden" name="jenis" value="<?= esc($filter['jenis']) ?>">
            <div class="toolbar">
                <label class="field"><span>Dari bulan</span><input class="input" type="month" name="start" value="<?= esc($filter['start']) ?>" required></label>
                <label class="field"><span>Sampai bulan</span><input class="input" type="month" name="end" value="<?= esc($filter['end']) ?>" required></label>
                <?php if ($filter['jenis'] === 'buku_besar'): ?>
                    <label class="field"><span>Akun</span>
                        <select class="select" name="akun">
                            <option value="">Semua akun</option>
                            <?php foreach ($akunList as $a): ?>
                                <option value="<?= esc($a['no_akun']) ?>" <?= $filter['akun'] === $a['no_akun'] ? 'selected' : '' ?>><?= esc($a['no_akun'] . ' · ' . $a['nama_akun']) ?></option>
                            <?php endforeach ?>
                        </select>
                    </label>
                    <label class="field"><span>Posting</span>
                        <select class="select" name="tahap">
                            <option value="ju" <?= $filter['tahap'] === 'ju' ? 'selected' : '' ?>>Jurnal umum saja</option>
                            <option value="jp" <?= $filter['tahap'] === 'jp' ? 'selected' : '' ?>>+ Jurnal penyesuaian</option>
                            <option value="tutup" <?= $filter['tahap'] === 'tutup' ? 'selected' : '' ?>>+ Jurnal penutup</option>
                        </select>
                    </label>
                <?php endif ?>
                <button class="btn btn-ink" type="submit">Tampilkan</button>
                <span class="spacer"></span>
                <button class="btn" type="button" onclick="window.print()"><?= ikon('printer', 16) ?>Cetak</button>
                <a class="btn btn-primary" href="/laporan/pdf?<?= $q(['akun' => $filter['akun'], 'tahap' => $filter['tahap']]) ?>"><?= ikon('file-down', 16) ?>Unduh PDF</a>
            </div>
        </form>

        <?php if (! $siklus->adaTransaksi()): ?>
            <div class="panel empty">
                <h3>Belum ada transaksi pada <?= periode_indo($siklus->mulai, $siklus->selesai) ?></h3>
                <p>Laporan dibentuk dari jurnal umum dan jurnal penyesuaian. Catat transaksi lebih dulu, atau pilih periode lain.</p>
                <?php if (session('user_role') !== '3'): ?>
                    <a class="btn btn-primary" href="/jurnal_umum"><?= ikon('plus', 16) ?>Catat jurnal umum</a>
                <?php endif ?>
            </div>
        <?php else: ?>
            <?php foreach ($lembar as $l): ?>
                <?= view('laporan/lembar', ['l' => $l]) ?>
            <?php endforeach ?>
        <?php endif ?>

        <nav class="page-actions no-print" style="justify-content:space-between; margin-top:18px" aria-label="Tahap berikutnya">
            <?php if ($posisi > 0): $k = $urutan[$posisi - 1]; ?>
                <a class="btn btn-quiet" href="/laporan?<?= $q(['jenis' => $k]) ?>"><?= ikon('chevron-left', 16) ?><?= $posisi ?>. <?= $daftar[$k][0] ?></a>
            <?php else: ?><span></span><?php endif ?>
            <?php if ($posisi < count($urutan) - 1): $k = $urutan[$posisi + 1]; ?>
                <a class="btn btn-quiet" href="/laporan?<?= $q(['jenis' => $k]) ?>"><?= $posisi + 2 ?>. <?= $daftar[$k][0] ?><?= ikon('chevron-right', 16) ?></a>
            <?php endif ?>
        </nav>
    </div>
</div>
<?= $this->endSection() ?>

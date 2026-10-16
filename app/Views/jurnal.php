<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>
<?php
$ju = $cfg['sumber'] === 'ju';
$entri = $jurnal['entri'];
?>
<div class="page-head">
    <div>
        <h1><?= esc($cfg['judul']) ?></h1>
        <p><?= $ju
            ? 'Catat setiap transaksi perusahaan. Satu jurnal boleh berisi beberapa akun, asalkan total debit sama dengan total kredit.'
            : 'Catat penyesuaian akhir periode: pemakaian perlengkapan, penyusutan, beban yang masih harus dibayar, pendapatan diterima di muka yang sudah menjadi hak.' ?></p>
    </div>
    <div class="page-actions">
        <a class="btn" href="/laporan?<?= http_build_query(['jenis' => $cfg['sumber'] === 'ju' ? 'jurnal_umum' : 'jurnal_penyesuaian', 'start' => $filter['start'], 'end' => $filter['end']]) ?>"><?= ikon('files', 16) ?>Lihat sebagai laporan</a>
        <button class="btn btn-primary" type="button" data-jurnal-baru <?= $akuns ? '' : 'disabled' ?>><?= ikon('plus', 16) ?>Catat jurnal</button>
    </div>
</div>

<form class="panel toolbar" method="get" action="<?= $cfg['url'] ?>" style="margin-bottom:18px">
    <label class="field"><span>Dari bulan</span><input class="input" type="month" name="start" value="<?= esc($filter['start']) ?>"></label>
    <label class="field"><span>Sampai bulan</span><input class="input" type="month" name="end" value="<?= esc($filter['end']) ?>"></label>
    <label class="field search"><span>Cari</span><?= ikon('search', 16) ?><input class="input" type="search" name="cari" value="<?= esc($filter['cari']) ?>" placeholder="Keterangan, nomor, atau akun"></label>
    <button class="btn btn-ink" type="submit">Tampilkan</button>
    <span class="spacer"></span>
    <?php if ($entri): ?>
        <span class="chip <?= $jurnal['seimbang'] ? 'chip-ok' : 'chip-bad' ?>"><?= ikon($jurnal['seimbang'] ? 'circle-check' : 'triangle-alert', 14) ?><?= count($entri) ?> jurnal · <?= $jurnal['seimbang'] ? 'seimbang' : 'tidak seimbang' ?></span>
    <?php endif ?>
</form>

<?php if (! $akuns): ?>
    <div class="panel empty">
        <h3>Daftar akun masih kosong</h3>
        <p>Jurnal memerlukan akun. Susun daftar akun perusahaan lebih dulu, misalnya 111 Kas, 311 Modal, 411 Pendapatan, 511 Beban Gaji.</p>
        <a class="btn btn-primary" href="/akun"><?= ikon('list-tree', 16) ?>Susun daftar akun</a>
    </div>
<?php elseif (! $entri): ?>
    <div class="panel empty">
        <h3><?= $filter['cari'] !== '' ? 'Tidak ada jurnal yang cocok' : 'Belum ada jurnal pada ' . esc($periode) ?></h3>
        <p><?= $filter['cari'] !== '' ? 'Coba kata kunci lain atau perluas periode.' : 'Klik Catat jurnal untuk mencatat transaksi pertama periode ini.' ?></p>
        <button class="btn btn-primary" type="button" data-jurnal-baru><?= ikon('plus', 16) ?>Catat jurnal</button>
    </div>
<?php else: ?>
    <div class="panel table-wrap">
        <table class="tbl">
            <thead>
            <tr>
                <th style="width:110px">Tanggal</th>
                <th style="width:72px">No.</th>
                <th>Keterangan &amp; akun</th>
                <th class="num">Debit</th>
                <th class="num">Kredit</th>
                <th class="actions"><span class="sr-only">Aksi</span></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($entri as $e): ?>
                <?php
                $muat = [
                    'ids' => array_column($e['baris'], 'id'), 'tanggal' => $e['tanggal'], 'keterangan' => $e['keterangan'],
                    'baris' => array_map(static fn ($b) => ['akun' => $b['no_akun'], 'debit' => $b['debit'], 'kredit' => $b['kredit']], $e['baris']),
                ];
                ?>
                <tr>
                    <td class="tnum"><?= date('d/m/Y', strtotime($e['tanggal'])) ?></td>
                    <td class="code"><?= esc($e['kode']) ?></td>
                    <td>
                        <strong style="color:var(--ink); font-weight:600"><?= esc($e['keterangan']) ?></strong>
                        <?php foreach ($e['baris'] as $b): ?>
                            <div class="muted" style="<?= $b['kredit'] > 0 ? 'padding-left:22px' : '' ?>"><?= esc($b['no_akun']) ?> · <?= esc($b['nama_akun']) ?></div>
                        <?php endforeach ?>
                    </td>
                    <td class="num"><div style="height:21px"></div><?php foreach ($e['baris'] as $b): ?><div><?= angka($b['debit'], true) ?: '&nbsp;' ?></div><?php endforeach ?></td>
                    <td class="num"><div style="height:21px"></div><?php foreach ($e['baris'] as $b): ?><div><?= angka($b['kredit'], true) ?: '&nbsp;' ?></div><?php endforeach ?></td>
                    <td class="actions">
                        <button class="btn btn-quiet btn-icon btn-sm" type="button" data-jurnal-ubah='<?= esc(json_encode($muat), 'attr') ?>' aria-label="Ubah <?= esc($e['kode'], 'attr') ?>" title="Ubah"><?= ikon('pencil', 16) ?></button>
                        <form method="post" action="<?= $cfg['url'] ?>/hapus" style="display:inline" data-confirm="Hapus jurnal <?= esc($e['kode'], 'attr') ?> (<?= esc($e['keterangan'], 'attr') ?>)? Semua barisnya ikut terhapus." data-confirm-label="Hapus jurnal">
                            <?php foreach ($e['baris'] as $b): ?><input type="hidden" name="ids[]" value="<?= esc($b['id'], 'attr') ?>"><?php endforeach ?>
                            <input type="hidden" name="start" value="<?= esc($filter['start']) ?>"><input type="hidden" name="end" value="<?= esc($filter['end']) ?>">
                            <button class="btn btn-quiet btn-icon btn-sm btn-danger" type="submit" aria-label="Hapus <?= esc($e['kode'], 'attr') ?>" title="Hapus"><?= ikon('trash-2', 16) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach ?>
            <tr>
                <td colspan="3" style="font-family:var(--ui); font-weight:700; color:var(--ink)">Jumlah</td>
                <td class="num" style="font-weight:700; color:var(--ink)"><?= angka($jurnal['total_debit']) ?></td>
                <td class="num" style="font-weight:700; color:var(--ink)"><?= angka($jurnal['total_kredit']) ?></td>
                <td></td>
            </tr>
            </tbody>
        </table>
    </div>
<?php endif ?>

<dialog class="dlg wide" id="dlg-jurnal" aria-labelledby="jurnal-judul">
    <form method="post" action="<?= $cfg['url'] ?>/simpan" id="form-jurnal" novalidate>
        <div class="dlg-head">
            <div>
                <h2 id="jurnal-judul" data-judul-baru="Catat <?= strtolower(esc($cfg['judul'])) ?>" data-judul-ubah="Ubah <?= strtolower(esc($cfg['judul'])) ?>">Catat <?= strtolower(esc($cfg['judul'])) ?></h2>
                <p>Akun di debit ditulis lebih dulu. Simpan aktif setelah debit dan kredit seimbang.</p>
            </div>
            <button class="btn btn-quiet btn-icon" type="button" data-close aria-label="Tutup"><?= ikon('x', 18) ?></button>
        </div>
        <div class="dlg-body">
            <input type="hidden" name="start" value="<?= esc($filter['start']) ?>">
            <input type="hidden" name="end" value="<?= esc($filter['end']) ?>">
            <div data-ids></div>
            <div style="display:grid; grid-template-columns: 180px 1fr; gap:14px">
                <label class="field"><span>Tanggal</span><input class="input" type="date" name="tanggal" required value="<?= esc(old('tanggal') ?? date('Y-m-d')) ?>"></label>
                <label class="field"><span>Keterangan</span><input class="input" type="text" name="keterangan" maxlength="<?= $cfg['maks_ket'] ?>" required placeholder="<?= $ju ? 'Mis. Penerimaan pembayaran langganan pelanggan' : 'Mis. Penyusutan peralatan jaringan bulan ini' ?>" value="<?= esc(old('keterangan') ?? '') ?>"></label>
            </div>
            <table class="lines">
                <thead><tr><th>Akun</th><th class="num">Debit (Rp)</th><th class="num">Kredit (Rp)</th><th></th></tr></thead>
                <tbody data-baris></tbody>
                <tfoot>
                <tr>
                    <td><button class="btn btn-sm" type="button" data-tambah-baris><?= ikon('plus', 14) ?>Tambah baris</button></td>
                    <td class="num tnum" data-total-debit>0</td>
                    <td class="num tnum" data-total-kredit>0</td>
                    <td></td>
                </tr>
                </tfoot>
            </table>
            <div class="balance-note">
                <span class="chip chip-info" data-status-seimbang>Isi minimal dua baris</span>
            </div>
        </div>
        <div class="dlg-foot">
            <button class="btn" type="button" data-close>Batal</button>
            <button class="btn btn-primary" type="submit" data-simpan disabled>Simpan jurnal</button>
        </div>
    </form>
</dialog>

<template id="tpl-baris">
    <tr>
        <td>
            <select class="select" name="akun[]" aria-label="Akun">
                <option value="">Pilih akun…</option>
                <?php foreach ($akuns as $a): ?>
                    <option value="<?= esc($a['no_akun'], 'attr') ?>"><?= esc($a['no_akun'] . ' · ' . $a['nama_akun']) ?></option>
                <?php endforeach ?>
            </select>
        </td>
        <td><input class="input num" name="debit[]" inputmode="numeric" autocomplete="off" aria-label="Debit"></td>
        <td><input class="input num" name="kredit[]" inputmode="numeric" autocomplete="off" aria-label="Kredit"></td>
        <td><button class="btn btn-quiet btn-icon btn-sm" type="button" data-hapus-baris aria-label="Hapus baris"><?= ikon('trash-2', 15) ?></button></td>
    </tr>
</template>
<?php if (session('errors') && old('keterangan') !== null): ?>
    <script type="application/json" id="jurnal-lama"><?= json_encode(['tanggal' => old('tanggal'), 'keterangan' => old('keterangan'), 'ids' => old('ids') ?? [], 'baris' => array_map(null, (array) old('akun'), (array) old('debit'), (array) old('kredit'))], JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="/js/jurnal.js"></script>
<?= $this->endSection() ?>

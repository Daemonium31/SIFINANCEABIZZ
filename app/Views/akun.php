<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>
<div class="page-head">
    <div>
        <h1>Daftar Akun</h1>
        <p>Nomor akun menentukan kelompoknya: 1 aset (11 lancar, 12 tetap), 2 utang (21 jangka pendek, 22 jangka panjang), 3 modal, 4 pendapatan, 5 beban. Akun bernama "Akumulasi…" dihitung sebagai pengurang aset.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" type="button" data-akun-baru><?= ikon('plus', 16) ?>Tambah akun</button>
    </div>
</div>

<form class="panel toolbar" method="get" action="/akun" style="margin-bottom:18px">
    <label class="field search"><span>Cari akun</span><?= ikon('search', 16) ?><input class="input" type="search" name="cari" value="<?= esc($cari) ?>" placeholder="Nomor, nama, atau keterangan"></label>
    <button class="btn btn-ink" type="submit">Cari</button>
    <?php if ($cari !== ''): ?><a class="btn btn-quiet" href="/akun">Tampilkan semua</a><?php endif ?>
    <span class="spacer"></span>
    <span class="chip chip-info"><?= $jumlah ?> akun</span>
</form>

<?php if (! $grup): ?>
    <div class="panel empty">
        <h3><?= $cari !== '' ? 'Tidak ada akun yang cocok' : 'Belum ada akun' ?></h3>
        <p><?= $cari !== '' ? 'Coba kata kunci lain.' : 'Mulai dengan akun yang paling sering dipakai: 111 Kas, 112 Piutang Usaha, 211 Utang Usaha, 311 Modal, 312 Prive, 313 Ikhtisar Laba Rugi, 411 Pendapatan, dan akun beban 5xx.' ?></p>
        <?php if ($cari === ''): ?><button class="btn btn-primary" type="button" data-akun-baru><?= ikon('plus', 16) ?>Tambah akun pertama</button><?php endif ?>
    </div>
<?php else: ?>
    <div class="panel table-wrap">
        <table class="tbl">
            <thead>
            <tr>
                <th style="width:110px">No. akun</th>
                <th>Nama akun</th>
                <th>Keterangan</th>
                <th class="num">Saldo hari ini</th>
                <th class="actions"><span class="sr-only">Aksi</span></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($kelompok as $kunci => $label): ?>
                <?php if (empty($grup[$kunci])) continue; ?>
                <tr><td colspan="5" style="background:var(--wash); font:700 13px var(--ui); color:var(--ink)"><?= $label ?></td></tr>
                <?php foreach ($grup[$kunci] as $a): ?>
                    <?php $s = $a['saldo']; ?>
                    <tr>
                        <td class="code"><?= esc($a['no_akun']) ?></td>
                        <td style="color:var(--ink)"><?= esc($a['nama_akun']) ?><?= $a['kontra'] ? ' <span class="chip chip-sand" style="margin-left:6px">pengurang aset</span>' : '' ?></td>
                        <td class="muted"><?= esc($a['keterangan']) ?></td>
                        <td class="num"><?= $s == 0 ? '<span class="muted">–</span>' : rupiah(abs($s)) . ' <span class="muted">' . ($s > 0 ? 'D' : 'K') . '</span>' ?></td>
                        <td class="actions">
                            <button class="btn btn-quiet btn-icon btn-sm" type="button" data-akun-ubah='<?= esc(json_encode(['no_akun' => $a['no_akun'], 'nama_akun' => $a['nama_akun'], 'keterangan' => $a['keterangan']]), 'attr') ?>' title="Ubah" aria-label="Ubah akun <?= esc($a['no_akun'], 'attr') ?>"><?= ikon('pencil', 16) ?></button>
                            <?php if ($a['pakai'] == 0): ?>
                                <form method="post" action="/akun/hapus" style="display:inline" data-confirm="Hapus akun <?= esc($a['no_akun'] . ' ' . $a['nama_akun'], 'attr') ?>?" data-confirm-label="Hapus akun">
                                    <input type="hidden" name="no_akun" value="<?= esc($a['no_akun'], 'attr') ?>">
                                    <button class="btn btn-quiet btn-icon btn-sm btn-danger" type="submit" title="Hapus" aria-label="Hapus akun <?= esc($a['no_akun'], 'attr') ?>"><?= ikon('trash-2', 16) ?></button>
                                </form>
                            <?php else: ?>
                                <span class="btn btn-quiet btn-icon btn-sm is-disabled" title="Dipakai di <?= $a['pakai'] ?> baris jurnal, tidak bisa dihapus" aria-hidden="true"><?= ikon('lock', 15) ?></span>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endforeach ?>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>

<dialog class="dlg" id="dlg-akun" aria-labelledby="akun-judul">
    <form method="post" action="/akun/simpan">
        <div class="dlg-head">
            <div><h2 id="akun-judul">Tambah akun</h2><p>Nomor akun menentukan posisinya di laporan keuangan.</p></div>
            <button class="btn btn-quiet btn-icon" type="button" data-close aria-label="Tutup"><?= ikon('x', 18) ?></button>
        </div>
        <div class="dlg-body cols">
            <input type="hidden" name="no_lama" value="<?= esc(old('no_lama') ?? '') ?>">
            <label class="field"><span>Nomor akun</span><input class="input tnum" name="no_akun" inputmode="numeric" required maxlength="20" placeholder="111" value="<?= esc(old('no_akun') ?? '') ?>"><small data-kelompok-hint>&nbsp;</small></label>
            <label class="field"><span>Nama akun</span><input class="input" name="nama_akun" required maxlength="100" placeholder="Kas" value="<?= esc(old('nama_akun') ?? '') ?>"></label>
            <label class="field full"><span>Keterangan <small>(opsional)</small></span><input class="input" name="keterangan" maxlength="255" placeholder="Mis. Kas kantor pusat Tangerang" value="<?= esc(old('keterangan') ?? '') ?>"></label>
        </div>
        <div class="dlg-foot">
            <button class="btn" type="button" data-close>Batal</button>
            <button class="btn btn-primary" type="submit">Simpan akun</button>
        </div>
    </form>
</dialog>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  var dlg = document.getElementById('dlg-akun');
  var f = dlg.querySelector('form');
  var hint = dlg.querySelector('[data-kelompok-hint]');
  function kelompok(no) {
    var d = (no || '').replace(/\D/g, '');
    if (!d) return ' ';
    var peta = { '1': d[1] === '2' ? 'Aset tetap' : 'Aset lancar', '2': d[1] >= '2' ? 'Utang jangka panjang' : 'Utang jangka pendek', '3': 'Modal / prive / ikhtisar', '4': 'Pendapatan', '7': 'Pendapatan lain' };
    return 'Masuk kelompok: ' + (peta[d[0]] || 'Beban');
  }
  f.no_akun.addEventListener('input', function () { hint.textContent = kelompok(f.no_akun.value); });
  function buka(a) {
    document.getElementById('akun-judul').textContent = a ? 'Ubah akun ' + a.no_akun : 'Tambah akun';
    f.no_lama.value = a ? a.no_akun : '';
    f.no_akun.value = a ? a.no_akun : '';
    f.nama_akun.value = a ? a.nama_akun : '';
    f.keterangan.value = a ? (a.keterangan || '') : '';
    hint.textContent = kelompok(f.no_akun.value);
    dlg.showModal();
    f.no_akun.focus();
  }
  document.querySelectorAll('[data-akun-baru]').forEach(function (b) { b.addEventListener('click', function () { buka(null); }); });
  document.querySelectorAll('[data-akun-ubah]').forEach(function (b) { b.addEventListener('click', function () { buka(JSON.parse(b.getAttribute('data-akun-ubah'))); }); });
  <?php if (session('errors') && old('nama_akun') !== null): ?>
  hint.textContent = kelompok(f.no_akun.value);
  document.getElementById('akun-judul').textContent = f.no_lama.value ? 'Ubah akun ' + f.no_lama.value : 'Tambah akun';
  dlg.showModal();
  <?php endif ?>
})();
</script>
<?= $this->endSection() ?>

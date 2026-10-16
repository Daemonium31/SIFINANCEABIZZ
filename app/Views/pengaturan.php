<?= $this->extend('layout/app') ?>

<?php use App\Libraries\Identitas; ?>

<?= $this->section('content') ?>
<div class="page-head">
    <div>
        <h1>Identitas Perusahaan</h1>
        <p>Nama dan logo ini tampil di menu, halaman masuk, kepala setiap laporan, berkas PDF, dan mode presentasi.</p>
    </div>
</div>

<div class="widgets">
    <section class="panel w w-6">
        <div class="panel-head"><div><h2>Ubah identitas</h2><p>Ikut tersimpan di cadangan data.</p></div></div>
        <form class="panel-body" method="post" action="/pengaturan/simpan" enctype="multipart/form-data" style="display:grid; gap:16px">
            <label class="field"><span>Nama perusahaan</span>
                <input class="input" name="nama_perusahaan" required maxlength="100" value="<?= esc(old('nama_perusahaan') ?? Identitas::nama()) ?>" placeholder="Mis. PT Maju Bersama">
                <small>Ditulis persis seperti ini di kepala laporan.</small>
            </label>
            <label class="field"><span>Logo <small>(opsional)</small></span>
                <input class="input" type="file" name="logo" accept="image/png,image/jpeg,image/webp">
                <small>PNG, JPG, atau WebP, maksimal 2 MB. Gambar persegi dengan latar transparan terlihat paling rapi. Tanpa logo, inisial nama yang ditampilkan.</small>
            </label>
            <?php if (Identitas::berkasLogo()): ?>
                <label style="display:flex; gap:8px; align-items:center"><input type="checkbox" name="hapus_logo" value="1"> Hapus logo dan pakai inisial</label>
            <?php endif ?>
            <div><button class="btn btn-primary" type="submit">Simpan identitas</button></div>
        </form>
    </section>

    <section class="panel w w-6">
        <div class="panel-head"><div><h2>Tampilan saat ini</h2><p>Menu samping dan kepala laporan.</p></div></div>
        <div class="panel-body" style="display:grid; gap:18px">
            <div class="brand" style="background:var(--nav); border-radius:var(--r); padding:16px 18px">
                <?= view('partials/merek') ?>
                <span><span class="brand-name"><?= esc(Identitas::nama()) ?></span><span class="brand-sub">Sistem Keuangan</span></span>
            </div>
            <div class="sheet" style="padding:22px">
                <div class="sheet-head" style="margin:0">
                    <div class="co"><?= esc(Identitas::nama()) ?></div>
                    <h2>Laporan Laba Rugi</h2>
                    <div class="per">Untuk periode yang berakhir <?= tgl_indo(date('Y-m-t')) ?></div>
                </div>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

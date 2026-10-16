<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>
<div class="page-head">
    <div>
        <h1>Cadangan Data</h1>
        <p>Data keuangan tersimpan di komputer ini saja. Cadangan otomatis selalu diperbarui setiap ada perubahan; cadangan manual membuat berkas baru yang bisa Anda simpan ke flashdisk atau folder lain.</p>
    </div>
</div>

<div class="widgets">
    <section class="panel w w-6">
        <div class="panel-head">
            <div><h2>Cadangan otomatis</h2><p>Satu berkas, ditimpa setiap kali akun, jurnal, atau pengguna berubah.</p></div>
            <span class="chip <?= $otomatis['ada'] ? 'chip-ok' : 'chip-info' ?>"><?= ikon($otomatis['ada'] ? 'circle-check' : 'info', 14) ?><?= $otomatis['ada'] ? 'Aktif' : 'Belum ada' ?></span>
        </div>
        <div class="panel-body" style="display:grid; gap:16px">
            <?php if ($otomatis['ada']): ?>
                <table class="tbl">
                    <tbody>
                    <tr><td>Terakhir diperbarui</td><td class="num"><?= tgl_indo(substr($otomatis['dibuat'], 0, 10)) ?>, <?= date('H:i', strtotime($otomatis['dibuat'])) ?></td></tr>
                    <tr><td>Isi</td><td class="num"><?= $otomatis['jumlah']['akun'] ?? 0 ?> akun · <?= ($otomatis['jumlah']['jurnal_umum'] ?? 0) + ($otomatis['jumlah']['jurnal_penyesuaian'] ?? 0) ?> baris jurnal · <?= $otomatis['jumlah']['users'] ?? 0 ?> pengguna</td></tr>
                    <tr><td>Ukuran</td><td class="num"><?= number_format($otomatis['ukuran'] / 1024, 1, ',', '.') ?> KB</td></tr>
                    </tbody>
                </table>
                <p class="figure-note" style="margin:0">Lokasi: <code style="word-break:break-all"><?= esc($lokasi) ?></code></p>
                <div class="page-actions">
                    <a class="btn" href="/cadangan/unduh-otomatis"><?= ikon('download', 16) ?>Unduh salinan</a>
                    <form method="post" action="/cadangan/pulihkan-otomatis" data-confirm="Data di komputer ini akan dikembalikan ke cadangan otomatis terakhir (<?= esc(tgl_indo(substr($otomatis['dibuat'], 0, 10)), 'attr') ?>, <?= date('H:i', strtotime($otomatis['dibuat'])) ?>). Lanjutkan?" data-confirm-label="Pulihkan">
                        <button class="btn btn-danger" type="submit"><?= ikon('upload', 16) ?>Pulihkan dari cadangan otomatis</button>
                    </form>
                </div>
            <?php else: ?>
                <p style="margin:0">Cadangan otomatis dibuat begitu ada perubahan pertama, misalnya saat akun atau jurnal disimpan.</p>
            <?php endif ?>
        </div>
    </section>

    <section class="panel w w-6">
        <div class="panel-head"><div><h2>Cadangan manual</h2><p>Membuat berkas baru bertanggal, terpisah dari cadangan otomatis.</p></div></div>
        <div class="panel-body" style="display:grid; gap:16px">
            <table class="tbl">
                <tbody>
                <tr><td>Akun</td><td class="num"><?= $jumlah['akun'] ?></td></tr>
                <tr><td>Baris jurnal umum</td><td class="num"><?= $jumlah['jurnal_umum'] ?></td></tr>
                <tr><td>Baris jurnal penyesuaian</td><td class="num"><?= $jumlah['jurnal_penyesuaian'] ?></td></tr>
                <tr><td>Pengguna</td><td class="num"><?= $jumlah['users'] ?></td></tr>
                </tbody>
            </table>
            <div><a class="btn btn-primary" href="/cadangan/unduh"><?= ikon('download', 16) ?>Buat cadangan manual</a></div>
        </div>
    </section>

    <section class="panel w w-12">
        <div class="panel-head"><div><h2>Pulihkan dari berkas</h2><p>Berkas cadangan manual maupun salinan cadangan otomatis bisa dipakai. Seluruh data di komputer ini akan diganti isi berkas.</p></div></div>
        <form class="panel-body" method="post" action="/cadangan/pulihkan" enctype="multipart/form-data" style="display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end" data-confirm="Seluruh akun, jurnal, dan pengguna di komputer ini akan diganti isi berkas cadangan. Lanjutkan?" data-confirm-label="Ganti data">
            <label class="field" style="flex:1; min-width:260px"><span>Berkas cadangan (.json)</span><input class="input" type="file" name="berkas" accept=".json,application/json" required></label>
            <button class="btn btn-danger" type="submit"><?= ikon('upload', 16) ?>Pulihkan data</button>
            <p class="figure-note" style="margin:0; width:100%">Setelah dipulihkan Anda akan diminta masuk lagi dengan pengguna dari berkas cadangan.</p>
        </form>
    </section>
</div>
<?= $this->endSection() ?>

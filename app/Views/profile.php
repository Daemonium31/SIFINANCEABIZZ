<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>
<div class="page-head">
    <div>
        <h1>Profil Saya</h1>
        <p>Nama Anda tercetak di halaman judul presentasi dan ringkasan PDF.</p>
    </div>
</div>

<div class="widgets">
    <section class="panel w w-6">
        <div class="panel-head"><div><h2>Data diri</h2><p><?= esc($peran[$profile['jenis_user']] ?? '') ?> · NIP <?= esc($profile['nip']) ?></p></div></div>
        <form class="panel-body" method="post" action="/profile/update" style="display:grid; gap:16px">
            <label class="field"><span>Nama</span><input class="input" name="nama" required maxlength="100" value="<?= esc($profile['username']) ?>"></label>
            <label class="field"><span>Email</span><input class="input" type="email" name="email" required value="<?= esc($profile['email']) ?>"></label>
            <div><button class="btn btn-primary" type="submit">Simpan profil</button></div>
        </form>
    </section>

    <section class="panel w w-6">
        <div class="panel-head"><div><h2>Ganti kata sandi</h2><p>Minimal 8 karakter.</p></div></div>
        <form class="panel-body" method="post" action="/profile/changePass" style="display:grid; gap:16px">
            <label class="field"><span>Kata sandi saat ini</span><input class="input" type="password" name="password_lama" required autocomplete="current-password"></label>
            <label class="field"><span>Kata sandi baru</span><input class="input" type="password" name="password_baru" required minlength="8" autocomplete="new-password"></label>
            <label class="field"><span>Ulangi kata sandi baru</span><input class="input" type="password" name="konfirmasi_password" required minlength="8" autocomplete="new-password"></label>
            <div><button class="btn btn-primary" type="submit">Ganti kata sandi</button></div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>

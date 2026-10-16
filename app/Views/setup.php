<?= $this->extend('layout/auth') ?>

<?= $this->section('content') ?>
<h2>Buat administrator</h2>
<p>Aplikasi ini baru dipasang di komputer ini. Buat akun administrator pertama; pengguna lain ditambahkan setelah masuk.</p>
<form method="post" action="/setup">
    <label class="field"><span>Nama</span><input class="input" name="nama" required maxlength="100" autofocus value="<?= esc(old('nama') ?? '') ?>"></label>
    <label class="field"><span>NIP</span><input class="input tnum" name="nip" required maxlength="20" value="<?= esc(old('nip') ?? '') ?>"></label>
    <label class="field"><span>Email</span><input class="input" type="email" name="email" required maxlength="100" autocomplete="username" value="<?= esc(old('email') ?? '') ?>"></label>
    <label class="field"><span>Kata sandi</span><input class="input" type="password" name="password" required minlength="8" autocomplete="new-password"><small>Minimal 8 karakter.</small></label>
    <label class="field"><span>Ulangi kata sandi</span><input class="input" type="password" name="konfirmasi" required minlength="8" autocomplete="new-password"></label>
    <button class="btn btn-primary" type="submit">Buat administrator</button>
</form>
<?= $this->endSection() ?>

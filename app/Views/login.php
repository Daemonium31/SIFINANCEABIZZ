<?= $this->extend('layout/auth') ?>

<?= $this->section('content') ?>
<h2>Masuk</h2>
<p>Gunakan email atau NIP yang didaftarkan administrator.</p>
<form method="post" action="/login">
    <label class="field"><span>Email atau NIP</span><input class="input" name="email" required autofocus autocomplete="username" value="<?= esc(old('email') ?? '') ?>"></label>
    <label class="field"><span>Kata sandi</span><input class="input" type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn btn-primary" type="submit">Masuk</button>
</form>
<?= $this->endSection() ?>

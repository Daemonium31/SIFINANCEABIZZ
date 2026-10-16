<?= $this->extend('layout/app') ?>

<?= $this->section('content') ?>
<div class="page-head">
    <div>
        <h1>Pengguna</h1>
        <p>Akuntan mencatat akun dan jurnal. Pimpinan hanya melihat dashboard dan laporan. Administrator juga mengelola pengguna dan cadangan data.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" type="button" data-user-baru><?= ikon('plus', 16) ?>Tambah pengguna</button>
    </div>
</div>

<div class="panel table-wrap">
    <table class="tbl">
        <thead><tr><th>Nama</th><th>NIP</th><th>Email</th><th>Peran</th><th class="actions"><span class="sr-only">Aksi</span></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td style="color:var(--ink); font-weight:600"><?= esc($u['username']) ?><?= $u['nip'] === session('user_id') ? ' <span class="chip chip-info" style="margin-left:6px">Anda</span>' : '' ?></td>
                <td class="code"><?= esc($u['nip']) ?></td>
                <td><?= esc($u['email']) ?></td>
                <td><?= $peran[$u['jenis_user']] ?? esc($u['jenis_user']) ?></td>
                <td class="actions">
                    <button class="btn btn-quiet btn-icon btn-sm" type="button" data-user-ubah='<?= esc(json_encode(['nip' => $u['nip'], 'nama' => $u['username'], 'email' => $u['email'], 'jenis_user' => $u['jenis_user']]), 'attr') ?>' title="Ubah" aria-label="Ubah <?= esc($u['username'], 'attr') ?>"><?= ikon('pencil', 16) ?></button>
                    <?php if ($u['nip'] !== session('user_id')): ?>
                        <form method="post" action="/user/hapus" style="display:inline" data-confirm="Hapus pengguna <?= esc($u['username'], 'attr') ?>? Pengguna ini tidak bisa masuk lagi." data-confirm-label="Hapus pengguna">
                            <input type="hidden" name="nip" value="<?= esc($u['nip'], 'attr') ?>">
                            <button class="btn btn-quiet btn-icon btn-sm btn-danger" type="submit" title="Hapus" aria-label="Hapus <?= esc($u['username'], 'attr') ?>"><?= ikon('trash-2', 16) ?></button>
                        </form>
                    <?php endif ?>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>

<dialog class="dlg" id="dlg-user" aria-labelledby="user-judul">
    <form method="post" action="/user/simpan" autocomplete="off">
        <div class="dlg-head">
            <div><h2 id="user-judul">Tambah pengguna</h2><p data-user-catatan>Kata sandi minimal 8 karakter.</p></div>
            <button class="btn btn-quiet btn-icon" type="button" data-close aria-label="Tutup"><?= ikon('x', 18) ?></button>
        </div>
        <div class="dlg-body cols">
            <input type="hidden" name="nip_lama" value="<?= esc(old('nip_lama') ?? '') ?>">
            <label class="field full"><span>Nama</span><input class="input" name="nama" required maxlength="100" value="<?= esc(old('nama') ?? '') ?>"></label>
            <label class="field"><span>NIP</span><input class="input tnum" name="nip" required maxlength="20" value="<?= esc(old('nip') ?? '') ?>"></label>
            <label class="field"><span>Peran</span>
                <select class="select" name="jenis_user">
                    <?php foreach ($peran as $k => $v): ?><option value="<?= $k ?>" <?= (old('jenis_user') ?? '2') == $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach ?>
                </select>
            </label>
            <label class="field full"><span>Email</span><input class="input" type="email" name="email" required maxlength="100" value="<?= esc(old('email') ?? '') ?>"></label>
            <label class="field full"><span>Kata sandi</span><input class="input" type="password" name="password" minlength="8" autocomplete="new-password"></label>
        </div>
        <div class="dlg-foot">
            <button class="btn" type="button" data-close>Batal</button>
            <button class="btn btn-primary" type="submit">Simpan pengguna</button>
        </div>
    </form>
</dialog>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function () {
  var dlg = document.getElementById('dlg-user');
  var f = dlg.querySelector('form');
  function buka(u) {
    document.getElementById('user-judul').textContent = u ? 'Ubah ' + u.nama : 'Tambah pengguna';
    dlg.querySelector('[data-user-catatan]').textContent = u ? 'Kosongkan kata sandi bila tidak ingin menggantinya.' : 'Kata sandi minimal 8 karakter.';
    f.nip_lama.value = u ? u.nip : '';
    f.nip.value = u ? u.nip : '';
    f.nama.value = u ? u.nama : '';
    f.email.value = u ? u.email : '';
    f.jenis_user.value = u ? u.jenis_user : '2';
    f.password.value = '';
    f.password.required = !u;
    dlg.showModal();
    f.nama.focus();
  }
  document.querySelector('[data-user-baru]').addEventListener('click', function () { buka(null); });
  document.querySelectorAll('[data-user-ubah]').forEach(function (b) { b.addEventListener('click', function () { buka(JSON.parse(b.getAttribute('data-user-ubah'))); }); });
  <?php if (session('errors') && old('nama') !== null): ?>dlg.showModal();<?php endif ?>
})();
</script>
<?= $this->endSection() ?>

<?php
$nav   = $nav ?? '';
$judul = $judul ?? 'Keuangan';
$peran = (string) session('user_role');
$namaPeran = ['1' => 'Administrator', '2' => 'Akuntan', '3' => 'Pimpinan'][$peran] ?? 'Pengguna';
$nama = (string) session('user_name');
$inisial = strtoupper(implode('', array_map(static fn ($k) => mb_substr($k, 0, 1), array_slice(preg_split('/\s+/', trim($nama)) ?: ['?'], 0, 2))));

$menu = [
    'Ringkasan' => [
        ['dashboard', '/', 'Dashboard', 'layout-dashboard', true],
    ],
    'Pencatatan' => [
        ['ju', '/jurnal_umum', 'Jurnal Umum', 'book-open', $peran !== '3'],
        ['jp', '/jurnal_penyesuaian', 'Jurnal Penyesuaian', 'notebook-pen', $peran !== '3'],
        ['akun', '/akun', 'Daftar Akun', 'list-tree', $peran !== '3'],
    ],
    'Laporan' => [
        ['laporan', '/laporan', 'Siklus Akuntansi', 'files', true],
    ],
    'Pengaturan' => [
        ['pengaturan', '/pengaturan', 'Identitas Perusahaan', 'settings', $peran === '1'],
        ['user', '/user', 'Pengguna', 'users', $peran === '1'],
        ['cadangan', '/cadangan', 'Cadangan Data', 'database-backup', $peran !== '3'],
        ['profil', '/profile', 'Profil Saya', 'user-round', true],
    ],
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($judul) ?> · <?= esc(nama_perusahaan()) ?> Keuangan</title>
    <link rel="icon" type="image/png" href="<?= esc(\App\Libraries\Identitas::urlLogo() ?? '/img/app-icon.png', 'attr') ?>">
    <link rel="stylesheet" href="/css/cyberevo.css">
    <?= $this->renderSection('head') ?>
</head>
<body>
<div class="shell" id="shell">
    <aside class="sidebar" aria-label="Navigasi utama">
        <a class="brand" href="/">
            <?= view('partials/merek') ?>
            <span>
                <span class="brand-name"><?= esc(nama_perusahaan()) ?></span>
                <span class="brand-sub">Sistem Keuangan</span>
            </span>
        </a>

        <nav>
            <?php foreach ($menu as $grup => $items): ?>
                <?php $items = array_filter($items, static fn ($i) => $i[4]); if (! $items) continue; ?>
                <div class="nav-group">
                    <div class="nav-caption"><?= $grup ?></div>
                    <?php foreach ($items as [$kunci, $url, $label, $ikon]): ?>
                        <a class="nav-item<?= $nav === $kunci ? ' is-active' : '' ?>" href="<?= $url ?>"<?= $nav === $kunci ? ' aria-current="page"' : '' ?>>
                            <?= ikon($ikon) ?><?= $label ?>
                        </a>
                    <?php endforeach ?>
                </div>
            <?php endforeach ?>
        </nav>

        <div class="sidebar-foot">
            <div class="user-card">
                <span class="avatar"><?= esc($inisial ?: '?') ?></span>
                <span>
                    <strong><?= esc($nama) ?></strong>
                    <small><?= $namaPeran ?></small>
                </span>
                <a href="/logout" title="Keluar" aria-label="Keluar"><?= ikon('log-out') ?></a>
            </div>
        </div>
    </aside>

    <div class="main-area">
        <header class="topbar">
            <div class="crumbs">
                <button class="menu-toggle" type="button" data-nav-toggle aria-label="Buka menu"><?= ikon('menu', 20) ?></button>
                <span><?= esc(nama_perusahaan()) ?></span><?= ikon('chevron-right', 14) ?><strong><?= esc($judul) ?></strong>
            </div>
            <div class="topbar-meta">
                <span class="local-chip" title="Data tersimpan di komputer ini dan tidak memerlukan internet"><span class="local-dot"></span>Data lokal · offline</span>
                <span class="tnum"><?= tgl_indo(date('Y-m-d')) ?></span>
            </div>
        </header>

        <main class="content">
            <?= view('partials/flash') ?>
            <?= $this->renderSection('content') ?>
        </main>

        <footer class="page-footer">
            <span>&copy; <?= date('Y') ?> <?= esc(nama_perusahaan()) ?>. Untuk penggunaan internal <?= esc(nama_perusahaan()) ?>.</span>
            <span>Sistem Informasi Keuangan · sumber terbuka (MIT)</span>
        </footer>
    </div>
</div>

<dialog class="dlg" id="dlg-konfirmasi" aria-labelledby="konfirmasi-judul">
    <form method="dialog">
        <div class="dlg-head">
            <div>
                <h2 id="konfirmasi-judul">Yakin?</h2>
                <p data-konfirmasi-pesan></p>
            </div>
        </div>
        <div class="dlg-foot">
            <button class="btn" value="batal">Batal</button>
            <button class="btn btn-primary" value="ya" data-konfirmasi-ya>Ya, lanjutkan</button>
        </div>
    </form>
</dialog>

<script src="/js/app.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>

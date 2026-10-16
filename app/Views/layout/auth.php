<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($judul ?? 'Masuk') ?> · <?= esc(nama_perusahaan()) ?> Keuangan</title>
    <link rel="icon" type="image/png" href="<?= esc(\App\Libraries\Identitas::urlLogo() ?? '/img/app-icon.png', 'attr') ?>">
    <link rel="stylesheet" href="/css/cyberevo.css">
</head>
<body>
<div class="auth">
    <aside class="auth-side">
        <div class="brand">
            <?= view('partials/merek') ?>
            <span><span class="brand-name"><?= esc(nama_perusahaan()) ?></span><span class="brand-sub">Sistem Keuangan</span></span>
        </div>
        <div>
            <h1>Dari jurnal sampai jurnal balik, dalam satu tempat.</h1>
            <p>Catat transaksi <?= esc(nama_perusahaan()) ?>, lalu sepuluh tahap laporan siklus akuntansi tersusun sendiri dan siap diunduh sebagai PDF.</p>
            <div class="auth-cycle" aria-label="Tahap siklus akuntansi">
                <span>Jurnal Umum</span><span>Buku Besar</span><span>Neraca Saldo</span><span>Jurnal Penyesuaian</span><span>Neraca Penyesuaian</span><span>Neraca Lajur</span><span>Laporan Keuangan</span><span>Jurnal Penutup</span><span>NS Setelah Penutupan</span><span>Jurnal Balik</span>
            </div>
        </div>
        <div class="auth-foot">Untuk penggunaan internal <?= esc(nama_perusahaan()) ?>. Data tersimpan di komputer ini dan tidak memerlukan internet.</div>
    </aside>
    <main class="auth-main">
        <div class="auth-card">
            <?= view('partials/flash') ?>
            <?= $this->renderSection('content') ?>
        </div>
    </main>
</div>
<script src="/js/app.js"></script>
</body>
</html>

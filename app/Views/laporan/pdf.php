<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title><?= esc($judul) ?> · <?= esc(nama_perusahaan()) ?></title>
<style>
    @font-face { font-family: 'Source Sans 3'; font-weight: normal; src: url('fonts/source-sans-3-latin-400-normal.ttf') format('truetype'); }
    @font-face { font-family: 'Source Sans 3'; font-weight: bold; src: url('fonts/source-sans-3-latin-700-normal.ttf') format('truetype'); }
    @font-face { font-family: 'DM Sans'; font-weight: bold; src: url('fonts/dm-sans-latin-700-normal.ttf') format('truetype'); }
    @page { margin: 34px 36px 46px; }
    body { font-family: 'Source Sans 3', 'DejaVu Sans', sans-serif; font-size: 9.4pt; color: #172a3b; }
    h1, h2, h3, .sheet-head .co, .acc th, .chip { font-family: 'DM Sans', 'DejaVu Sans', sans-serif; }
    .sheet { page-break-after: always; }
    .sheet:last-child { page-break-after: auto; }
    .sheet-head { text-align: center; margin-bottom: 14px; }
    .sheet-head .co { font-size: 9.5pt; font-weight: bold; color: #4a5d68; }
    .sheet-head h2 { font-size: 14pt; margin: 3px 0 2px; color: #172a3b; }
    .sheet-head .per { font-size: 9pt; color: #4a5d68; }
    .sheet-status { margin-top: 6px; }
    .chip { display: inline-block; padding: 2px 9px; border-radius: 9px; font-size: 7.5pt; font-weight: bold; }
    .chip-ok { background: #e5f0e6; color: #4b6d59; }
    .chip-bad { background: #fff0ec; color: #b23a24; }

    table { border-collapse: collapse; width: 100%; }
    .acc th { font-size: 7.8pt; font-weight: bold; text-align: left; padding: 5px 6px; border-top: 1.2px solid #172a3b; border-bottom: 0.8px solid #172a3b; }
    .acc td { padding: 3.5px 6px; border-bottom: 0.5px solid #e4e4dd; vertical-align: top; }
    .acc tr { page-break-inside: avoid; }
    .acc .num { text-align: right; white-space: nowrap; }
    .acc .c { text-align: center; }
    .acc .date { white-space: nowrap; }
    .acc .ref { color: #4a5d68; white-space: nowrap; }
    .acc .kr { padding-left: 26px; }
    .acc .desc td { color: #5a6a72; font-style: normal; font-size: 7.6pt; padding-top: 0; padding-bottom: 5px; border-bottom: 0.5px solid #d8d9d1; }
    .acc tr.calm td { border-bottom: 0; }
    .acc .code { display: block; font-size: 6.8pt; color: #7d8a8f; font-style: normal; }
    .acc tr.total td, .acc tr.grand td { font-weight: bold; border-top: 0.8px solid #172a3b; border-bottom: 2.4px double #172a3b; }
    .acc tr.subtotal td { font-weight: bold; border-top: 0.5px solid #b9bbb2; }
    .acc tr.sect td { font-weight: bold; padding-top: 9px; border-bottom: 0; }
    .acc .in1 { padding-left: 18px; }
    .acc .split { border-left: 0.5px solid #d8d9d1; }
    .acc .grp th { text-align: center; border-bottom: 0.5px solid #b9bbb2; }
    .acc .mark-jp td { background: #f7f0df; }
    .acc.wide { font-size: 7pt; }
    .acc.wide th, .acc.wide td { padding-left: 3px; padding-right: 3px; }
    .statement { width: 78%; margin: 0 auto; }
    .ledger { margin-bottom: 16px; page-break-inside: auto; }
    .reverse-why { margin-top: 14px; }
    .reverse-why h3 { font-size: 9.5pt; margin: 0 0 4px; }
    .sheet-note { margin-top: 10px; color: #5a6a72; font-size: 7.8pt; }
    .empty { text-align: center; color: #5a6a72; padding: 30px 0; }
    .cover { text-align: center; padding-top: 70px; page-break-after: always; }
    .cover img { width: 90px; }
    .cover h1 { font-size: 22pt; margin: 18px 0 6px; }
    .cover p { font-size: 11pt; color: #4a5d68; margin: 0; }
    .cover-list { width: 520px; margin: 34px auto 0; font-size: 10.5pt; }
    .cover-list td { padding: 5px 8px; text-align: left; border-bottom: 0.5px solid #e4e4dd; }
    .cover-list .n { color: #7d8a8f; width: 24px; }
</style>
</head>
<body>
<?php if (! empty($sampul)): ?>
    <div class="cover">
        <?php if ($logo = \App\Libraries\Identitas::dataLogo()): ?><img src="<?= $logo ?>" alt=""><?php endif ?>
        <h1><?= esc($judul) ?></h1>
        <p><?= esc(nama_perusahaan()) ?> · Periode <?= esc($periode) ?></p>
        <?php $nama = array_column(array_values($daftar), 0); ?>
        <table class="cover-list">
            <?php for ($i = 0; $i < 5; $i++): ?>
                <tr><td class="n"><?= $i + 1 ?>.</td><td><?= esc($nama[$i]) ?></td><td class="n"><?= $i + 6 ?>.</td><td><?= esc($nama[$i + 5]) ?></td></tr>
            <?php endfor ?>
        </table>
    </div>
<?php endif ?>
<?php foreach ($lembar as $l): ?>
    <?= view('laporan/lembar', ['l' => $l, 'pdf' => true]) ?>
<?php endforeach ?>
</body>
</html>

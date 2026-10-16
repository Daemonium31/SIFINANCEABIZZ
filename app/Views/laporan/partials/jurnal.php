<?php
/** @var array $data ringkasan jurnal: entri, total_debit, total_kredit */
$entri = $data['entri'];
?>
<?php if (! $entri): ?>
    <div class="empty">
        <p><?= esc($opsi['kosong'] ?? 'Tidak ada jurnal pada periode ini.') ?></p>
        <?php if (! empty($opsi['tambah']) && empty($pdf) && session('user_role') !== '3'): ?>
            <a class="btn btn-primary" href="<?= $opsi['tambah'] ?>"><?= ikon('plus', 16) ?>Catat jurnal</a>
        <?php endif ?>
    </div>
<?php else: ?>
    <table class="acc">
        <thead>
        <tr>
            <th style="width:13%">Tanggal</th>
            <th>Keterangan</th>
            <th class="c" style="width:9%">Ref</th>
            <th class="num" style="width:17%">Debit (Rp)</th>
            <th class="num" style="width:17%">Kredit (Rp)</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($entri as $e): ?>
            <?php foreach ($e['baris'] as $i => $b): ?>
                <tr class="<?= $i === 0 ? 'entry-first' : '' ?> calm">
                    <td class="date"><?php if ($i === 0): ?><?= date('d/m/Y', strtotime($e['tanggal'])) ?><span class="code"><?= esc($e['kode']) ?></span><?php endif ?></td>
                    <td class="<?= $b['kredit'] > 0 && $b['debit'] == 0 ? 'kr' : '' ?>"><?= esc($b['nama_akun']) ?></td>
                    <td class="ref c"><?= esc($b['no_akun'] ?: '-') ?></td>
                    <td class="num"><?= angka($b['debit'], true) ?></td>
                    <td class="num"><?= angka($b['kredit'], true) ?></td>
                </tr>
            <?php endforeach ?>
            <tr class="desc">
                <td></td>
                <td colspan="4">(<?= esc($e['keterangan']) ?>)</td>
            </tr>
        <?php endforeach ?>
        <tr class="total">
            <td colspan="3">Jumlah</td>
            <td class="num"><?= angka($data['total_debit']) ?></td>
            <td class="num"><?= angka($data['total_kredit']) ?></td>
        </tr>
        </tbody>
    </table>
<?php endif ?>

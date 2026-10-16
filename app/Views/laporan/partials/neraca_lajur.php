<?php
/** @var array $data baris, total, laba, total_akhir */
$kol = ['ns', 'jp', 'nsd', 'lr', 'nrc'];
$t   = $data['total'];
$a   = $data['total_akhir'];
?>
<?php if (! $data['baris']): ?>
    <div class="empty"><p>Belum ada saldo akun pada periode ini.</p></div>
<?php else: ?>
    <div class="table-wrap">
        <table class="acc wide">
            <thead>
            <tr class="grp">
                <th rowspan="2" style="text-align:left; width:4%">No.</th>
                <th rowspan="2" style="text-align:left; width:17%">Nama Akun</th>
                <th colspan="2" class="c">Neraca Saldo</th>
                <th colspan="2" class="c">Penyesuaian</th>
                <th colspan="2" class="c">NS Disesuaikan</th>
                <th colspan="2" class="c">Laba Rugi</th>
                <th colspan="2" class="c">Neraca</th>
            </tr>
            <tr>
                <?php foreach ($kol as $k): ?>
                    <th class="num split">Debit</th>
                    <th class="num">Kredit</th>
                <?php endforeach ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($data['baris'] as $b): ?>
                <tr>
                    <td class="ref"><?= esc($b['no_akun'] ?: '-') ?></td>
                    <td><?= esc($b['nama_akun']) ?></td>
                    <?php foreach ($kol as $k): ?>
                        <td class="num split"><?= angka($b[$k . '_d'], true) ?></td>
                        <td class="num"><?= angka($b[$k . '_k'], true) ?></td>
                    <?php endforeach ?>
                </tr>
            <?php endforeach ?>
            <tr class="subtotal">
                <td colspan="2">Jumlah</td>
                <?php foreach ($kol as $k): ?>
                    <td class="num split"><?= angka($t[$k . '_d']) ?></td>
                    <td class="num"><?= angka($t[$k . '_k']) ?></td>
                <?php endforeach ?>
            </tr>
            <tr>
                <td colspan="2"><strong><?= $data['laba'] >= 0 ? 'Laba bersih' : 'Rugi bersih' ?></strong></td>
                <td class="split" colspan="2"></td>
                <td class="split" colspan="2"></td>
                <td class="split" colspan="2"></td>
                <td class="num split"><?= $data['laba'] >= 0 ? angka($data['laba']) : '' ?></td>
                <td class="num"><?= $data['laba'] < 0 ? angka(-$data['laba']) : '' ?></td>
                <td class="num split"><?= $data['laba'] < 0 ? angka(-$data['laba']) : '' ?></td>
                <td class="num"><?= $data['laba'] >= 0 ? angka($data['laba']) : '' ?></td>
            </tr>
            <tr class="total">
                <td colspan="2">Total</td>
                <?php foreach ($kol as $k): ?>
                    <td class="num split"><?= angka($a[$k . '_d']) ?></td>
                    <td class="num"><?= angka($a[$k . '_k']) ?></td>
                <?php endforeach ?>
            </tr>
            </tbody>
        </table>
    </div>
<?php endif ?>

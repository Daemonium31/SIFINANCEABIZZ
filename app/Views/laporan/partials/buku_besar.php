<?php /** @var array $data daftar akun buku besar */ ?>
<?php if (! $data): ?>
    <div class="empty"><p>Belum ada akun yang bermutasi pada periode ini.</p></div>
<?php endif ?>
<?php foreach ($data as $akun): ?>
    <div class="ledger">
        <table class="acc" style="margin-bottom:4px">
            <tr class="calm">
                <td style="padding-left:0"><strong>Nama Perkiraan: <?= esc($akun['nama_akun']) ?></strong></td>
                <td class="num" style="padding-right:0"><strong>Nomor Perkiraan: <?= esc($akun['no_akun'] ?: '-') ?></strong></td>
            </tr>
        </table>
        <table class="acc">
            <thead>
            <tr class="grp">
                <th rowspan="2" style="width:11%; text-align:left">Tanggal</th>
                <th rowspan="2" style="text-align:left">Keterangan</th>
                <th rowspan="2" class="c" style="width:8%">Ref</th>
                <th rowspan="2" class="num" style="width:13%">Debit</th>
                <th rowspan="2" class="num" style="width:13%">Kredit</th>
                <th colspan="2" class="c">Saldo</th>
            </tr>
            <tr>
                <th class="num split" style="width:13%">Debit</th>
                <th class="num" style="width:13%">Kredit</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($akun['saldo_awal'] != 0 || ! $akun['baris']): ?>
                <tr>
                    <td class="date"></td>
                    <td>Saldo awal</td>
                    <td class="ref c"></td>
                    <td class="num"></td>
                    <td class="num"></td>
                    <td class="num split"><?= angka(max($akun['saldo_awal'], 0), true) ?></td>
                    <td class="num"><?= angka(max(-$akun['saldo_awal'], 0), true) ?></td>
                </tr>
            <?php endif ?>
            <?php foreach ($akun['baris'] as $b): ?>
                <tr class="<?= strpos($b['ref'], 'JP-') === 0 ? 'mark-jp' : '' ?>">
                    <td class="date"><?= date('d/m/Y', strtotime($b['tanggal'])) ?></td>
                    <td><?= esc($b['keterangan']) ?></td>
                    <td class="ref c"><?= esc($b['ref']) ?></td>
                    <td class="num"><?= angka($b['debit'], true) ?></td>
                    <td class="num"><?= angka($b['kredit'], true) ?></td>
                    <td class="num split"><?= angka(max($b['saldo'], 0), true) ?: ($b['saldo'] == 0 ? '0' : '') ?></td>
                    <td class="num"><?= angka(max(-$b['saldo'], 0), true) ?></td>
                </tr>
            <?php endforeach ?>
            <tr class="total">
                <td colspan="3">Jumlah mutasi · saldo akhir</td>
                <td class="num"><?= angka($akun['total']['debit']) ?></td>
                <td class="num"><?= angka($akun['total']['kredit']) ?></td>
                <td class="num split"><?= angka(max($akun['saldo_akhir'], 0), true) ?></td>
                <td class="num"><?= angka(max(-$akun['saldo_akhir'], 0), true) ?></td>
            </tr>
            </tbody>
        </table>
    </div>
<?php endforeach ?>

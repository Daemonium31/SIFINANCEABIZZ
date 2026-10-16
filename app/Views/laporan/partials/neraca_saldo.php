<?php /** @var array $data baris, total */ ?>
<?php if (! $data['baris']): ?>
    <div class="empty"><p>Belum ada saldo akun pada periode ini.</p></div>
<?php else: ?>
    <table class="acc" style="max-width:820px; margin:0 auto">
        <thead>
        <tr>
            <th style="width:14%">No. Akun</th>
            <th>Nama Akun</th>
            <th class="num" style="width:22%">Debit (Rp)</th>
            <th class="num" style="width:22%">Kredit (Rp)</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data['baris'] as $b): ?>
            <tr>
                <td class="ref"><?= esc($b['no_akun'] ?: '-') ?></td>
                <td><?= esc($b['nama_akun']) ?></td>
                <td class="num"><?= angka($b['debit'], true) ?></td>
                <td class="num"><?= angka($b['kredit'], true) ?></td>
            </tr>
        <?php endforeach ?>
        <tr class="total">
            <td colspan="2">Jumlah</td>
            <td class="num"><?= angka($data['total']['debit']) ?></td>
            <td class="num"><?= angka($data['total']['kredit']) ?></td>
        </tr>
        </tbody>
    </table>
<?php endif ?>

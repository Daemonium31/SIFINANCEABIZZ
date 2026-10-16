<?php /** @var array $data entri, tidak_dibalik, tanggal */ ?>
<?= view('laporan/partials/jurnal', ['data' => $data, 'opsi' => ['kosong' => 'Tidak ada jurnal penyesuaian yang perlu dibalik untuk periode ini.'], 'pdf' => $pdf ?? false]) ?>

<?php if ($data['entri']): ?>
    <div class="reverse-why">
        <h3>Alasan dibalik</h3>
        <table class="acc">
            <tbody>
            <?php foreach ($data['entri'] as $e): ?>
                <tr><td class="ref" style="width:12%"><?= esc($e['kode']) ?> dari <?= esc($e['asal']) ?></td><td><?= esc($e['alasan']) ?></td></tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>

<?php if ($data['tidak_dibalik']): ?>
    <div class="reverse-why">
        <h3>Jurnal penyesuaian yang tidak dibalik</h3>
        <table class="acc">
            <tbody>
            <?php foreach ($data['tidak_dibalik'] as $t): ?>
                <tr><td class="ref" style="width:12%"><?= esc($t['kode']) ?></td><td><?= esc($t['keterangan']) ?><span class="code"><?= esc($t['alasan']) ?></span></td></tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>

<p class="sheet-note">Jurnal balik dibuat pada tanggal pertama periode berikutnya (<?= tgl_indo($data['tanggal']) ?>) untuk penyesuaian yang memunculkan akun utang atau aset baru, seperti beban yang masih harus dibayar.</p>

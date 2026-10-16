<?php /** @var array $l satu lembar laporan; $pdf bool */ ?>
<section class="sheet<?= ! empty($l['opsi']['lanskap']) ? ' sheet-wide' : '' ?>">
    <header class="sheet-head">
        <div class="co"><?= esc(nama_perusahaan()) ?></div>
        <h2><?= esc($l['judul']) ?></h2>
        <div class="per"><?= esc($l['periode']) ?></div>
        <?php if ($l['status'] !== null): ?>
            <div class="sheet-status">
                <?php if ($l['status']['seimbang']): ?>
                    <span class="chip chip-ok"><?= empty($pdf) ? ikon('circle-check', 14) : '' ?>Seimbang · <?= esc($l['status']['label'] ?? 'Debit = Kredit') ?></span>
                <?php else: ?>
                    <span class="chip chip-bad"><?= empty($pdf) ? ikon('triangle-alert', 14) : '' ?>Tidak seimbang · selisih <?= rupiah(abs($l['status']['selisih'])) ?></span>
                <?php endif ?>
            </div>
        <?php endif ?>
    </header>
    <?= view('laporan/partials/' . $l['partial'], ['data' => $l['data'], 'opsi' => $l['opsi'], 'pdf' => $pdf ?? false]) ?>
</section>

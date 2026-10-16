<?php $errors = session('errors'); $success = session('success'); ?>
<?php if ($success): ?>
    <div class="flash flash-ok" role="status">
        <?= ikon('circle-check', 20) ?>
        <div><strong><?= esc($success) ?></strong></div>
        <button class="btn btn-quiet btn-icon btn-sm" type="button" data-dismiss aria-label="Tutup"><?= ikon('x', 16) ?></button>
    </div>
<?php endif ?>
<?php if ($errors): ?>
    <div class="flash flash-bad" role="alert">
        <?= ikon('triangle-alert', 20) ?>
        <div>
            <?php if (is_array($errors)): ?>
                <strong>Data belum tersimpan. Periksa isian berikut:</strong>
                <ul><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
            <?php else: ?>
                <strong><?= esc($errors) ?></strong>
            <?php endif ?>
        </div>
        <button class="btn btn-quiet btn-icon btn-sm" type="button" data-dismiss aria-label="Tutup"><?= ikon('x', 16) ?></button>
    </div>
<?php endif ?>

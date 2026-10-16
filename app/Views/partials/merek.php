<?php
/** Tanda merek: logo perusahaan bila ada, atau inisial nama. $ukuran opsional (px). */
use App\Libraries\Identitas;

$logo = Identitas::urlLogo();
$gaya = isset($ukuran) ? ' style="width:' . (int) $ukuran . 'px;height:' . (int) $ukuran . 'px;font-size:' . round($ukuran * 0.38) . 'px"' : '';
?>
<?php if ($logo): ?>
    <span class="brand-mark has-logo"<?= $gaya ?>><img src="<?= esc($logo, 'attr') ?>" alt=""></span>
<?php else: ?>
    <span class="brand-mark"<?= $gaya ?> aria-hidden="true"><?= esc(Identitas::inisial()) ?></span>
<?php endif ?>

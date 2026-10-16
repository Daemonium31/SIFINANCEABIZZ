<?php
/** @var array $data aset_lancar, aset_tetap, utang_pendek, utang_panjang, modal, total_aktiva, total_pasiva */
$daftar = static function (array $items, string $kosong) {
    if (! $items) {
        return '<tr class="calm"><td class="in1" colspan="2" style="color:#7d8a8f">' . $kosong . '</td></tr>';
    }
    $html = '';
    foreach ($items as $i) {
        $html .= '<tr class="calm"><td class="in1">' . esc($i['nama_akun']) . '</td><td class="num">' . rupiah($i['nilai']) . '</td></tr>';
    }

    return $html;
};
?>
<table class="twin-t" style="width:100%; border-collapse:collapse">
    <tr>
        <td style="width:50%; vertical-align:top; padding:0 18px 0 0">
            <table class="acc">
                <colgroup><col><col style="width:40%"></colgroup>
                <thead><tr><th colspan="2">Aktiva</th></tr></thead>
                <tbody>
                <tr class="sect"><td colspan="2">Aktiva lancar</td></tr>
                <?= $daftar($data['aset_lancar']['items'], 'Tidak ada') ?>
                <tr class="subtotal"><td>Total aktiva lancar</td><td class="num"><?= rupiah($data['aset_lancar']['total']) ?></td></tr>
                <tr class="sect"><td colspan="2">Aktiva tetap</td></tr>
                <?= $daftar($data['aset_tetap']['items'], 'Tidak ada') ?>
                <tr class="subtotal"><td>Total aktiva tetap</td><td class="num"><?= rupiah($data['aset_tetap']['total']) ?></td></tr>
                </tbody>
            </table>
        </td>
        <td style="width:50%; vertical-align:top; padding:0 0 0 18px">
            <table class="acc">
                <colgroup><col><col style="width:40%"></colgroup>
                <thead><tr><th colspan="2">Pasiva</th></tr></thead>
                <tbody>
                <tr class="sect"><td colspan="2">Utang jangka pendek</td></tr>
                <?= $daftar($data['utang_pendek']['items'], 'Tidak ada') ?>
                <tr class="subtotal"><td>Total utang jangka pendek</td><td class="num"><?= rupiah($data['utang_pendek']['total']) ?></td></tr>
                <tr class="sect"><td colspan="2">Utang jangka panjang</td></tr>
                <?= $daftar($data['utang_panjang']['items'], 'Tidak ada') ?>
                <tr class="subtotal"><td>Total utang jangka panjang</td><td class="num"><?= rupiah($data['utang_panjang']['total']) ?></td></tr>
                <tr class="subtotal"><td>Total utang</td><td class="num"><?= rupiah($data['total_utang']) ?></td></tr>
                <tr class="sect"><td colspan="2">Ekuitas</td></tr>
                <tr class="calm"><td class="in1"><?= esc($data['nama_modal']) ?> akhir</td><td class="num"><?= rupiah($data['modal']) ?></td></tr>
                </tbody>
            </table>
        </td>
    </tr>
    <tr>
        <td style="vertical-align:bottom; padding:0 18px 0 0">
            <table class="acc"><colgroup><col><col style="width:40%"></colgroup><tr class="grand"><td>Total aktiva</td><td class="num"><?= rupiah($data['total_aktiva']) ?></td></tr></table>
        </td>
        <td style="vertical-align:bottom; padding:0 0 0 18px">
            <table class="acc"><colgroup><col><col style="width:40%"></colgroup><tr class="grand"><td>Total pasiva</td><td class="num"><?= rupiah($data['total_pasiva']) ?></td></tr></table>
        </td>
    </tr>
</table>

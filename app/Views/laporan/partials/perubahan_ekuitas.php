<?php /** @var array $data modal_awal, laba_bersih, prive, perubahan, modal_akhir */ ?>
<div class="statement">
    <table class="acc">
        <colgroup><col><col style="width:24%"><col style="width:24%"></colgroup>
        <tbody>
        <?php if ($data['saldo_awal_modal'] != 0 && $data['setoran_periode'] != 0): ?>
            <tr class="calm"><td>Saldo modal awal periode</td><td class="num"><?= rupiah($data['saldo_awal_modal']) ?></td><td></td></tr>
            <tr class="calm"><td>Setoran modal periode ini</td><td class="num"><?= rupiah($data['setoran_periode']) ?></td><td></td></tr>
        <?php endif ?>
        <tr class="subtotal">
            <td><?= esc($data['nama_modal']) ?> awal</td>
            <td></td>
            <td class="num"><?= rupiah($data['modal_awal']) ?></td>
        </tr>
        <tr class="sect"><td colspan="3">Kenaikan / penurunan modal</td></tr>
        <tr class="calm">
            <td class="in1"><?= $data['laba_bersih'] >= 0 ? 'Laba bersih' : 'Rugi bersih' ?></td>
            <td class="num"><?= rupiah($data['laba_bersih']) ?></td>
            <td></td>
        </tr>
        <tr class="calm">
            <td class="in1">Prive</td>
            <td class="num"><?= rupiah(-$data['prive']) ?></td>
            <td></td>
        </tr>
        <tr class="subtotal">
            <td><?= $data['perubahan'] >= 0 ? 'Kenaikan modal' : 'Penurunan modal' ?></td>
            <td></td>
            <td class="num"><?= rupiah($data['perubahan']) ?></td>
        </tr>
        <tr class="grand">
            <td><?= esc($data['nama_modal']) ?> akhir</td>
            <td></td>
            <td class="num"><?= rupiah($data['modal_akhir']) ?></td>
        </tr>
        </tbody>
    </table>
</div>

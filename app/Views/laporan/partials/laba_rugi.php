<?php /** @var array $data pendapatan, beban, laba_bersih */ ?>
<div class="statement">
    <table class="acc">
        <colgroup><col><col style="width:24%"><col style="width:24%"></colgroup>
        <tbody>
        <tr class="sect"><td colspan="3">Pendapatan</td></tr>
        <?php foreach ($data['pendapatan']['items'] as $i): ?>
            <tr class="calm">
                <td class="in1"><?= esc($i['nama_akun']) ?></td>
                <td class="num"><?= rupiah($i['nilai']) ?></td>
                <td></td>
            </tr>
        <?php endforeach ?>
        <?php if (! $data['pendapatan']['items']): ?>
            <tr class="calm"><td class="in1" colspan="3" style="color:#7d8a8f">Tidak ada pendapatan pada periode ini.</td></tr>
        <?php endif ?>
        <tr class="subtotal">
            <td>Total pendapatan</td>
            <td></td>
            <td class="num"><?= rupiah($data['pendapatan']['total']) ?></td>
        </tr>

        <tr class="sect"><td colspan="3">Beban</td></tr>
        <?php foreach ($data['beban']['items'] as $i): ?>
            <tr class="calm">
                <td class="in1"><?= esc($i['nama_akun']) ?></td>
                <td class="num"><?= rupiah($i['nilai']) ?></td>
                <td></td>
            </tr>
        <?php endforeach ?>
        <?php if (! $data['beban']['items']): ?>
            <tr class="calm"><td class="in1" colspan="3" style="color:#7d8a8f">Tidak ada beban pada periode ini.</td></tr>
        <?php endif ?>
        <tr class="subtotal">
            <td>Total beban</td>
            <td></td>
            <td class="num"><?= rupiah(-$data['beban']['total']) ?></td>
        </tr>

        <tr class="grand">
            <td><?= $data['laba_bersih'] >= 0 ? 'Laba bersih' : 'Rugi bersih' ?></td>
            <td></td>
            <td class="num"><?= rupiah($data['laba_bersih']) ?></td>
        </tr>
        </tbody>
    </table>
</div>

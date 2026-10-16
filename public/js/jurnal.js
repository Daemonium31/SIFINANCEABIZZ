// Formulir jurnal berbaris banyak: total debit/kredit langsung dihitung.
(function () {
  'use strict';

  var dlg = document.getElementById('dlg-jurnal');
  if (!dlg) return;
  var form = document.getElementById('form-jurnal');
  var tbody = form.querySelector('[data-baris]');
  var tpl = document.getElementById('tpl-baris');
  var status = form.querySelector('[data-status-seimbang]');
  var simpan = form.querySelector('[data-simpan]');
  var judul = document.getElementById('jurnal-judul');
  var nf = new Intl.NumberFormat('id-ID');

  function nilai(input) {
    var v = (input.value || '').replace(/[^\d]/g, '');
    return v ? parseInt(v, 10) : 0;
  }

  function format(input) {
    var v = nilai(input);
    input.value = v ? nf.format(v) : '';
  }

  function tambahBaris(data) {
    var row = tpl.content.firstElementChild.cloneNode(true);
    if (data) {
      row.querySelector('select').value = data.akun || '';
      if (+data.debit) row.querySelector('[name="debit[]"]').value = nf.format(+data.debit);
      if (+data.kredit) row.querySelector('[name="kredit[]"]').value = nf.format(+data.kredit);
    }
    tbody.appendChild(row);
    return row;
  }

  function hitung() {
    var d = 0, k = 0, terisi = 0, salah = false;
    tbody.querySelectorAll('tr').forEach(function (tr) {
      var akun = tr.querySelector('select').value;
      var vd = nilai(tr.querySelector('[name="debit[]"]'));
      var vk = nilai(tr.querySelector('[name="kredit[]"]'));
      if (!akun && !vd && !vk) return;
      terisi++;
      if (!akun || (vd > 0) === (vk > 0)) salah = true;
      d += vd; k += vk;
    });
    form.querySelector('[data-total-debit]').textContent = nf.format(d);
    form.querySelector('[data-total-kredit]').textContent = nf.format(k);

    var ok = false;
    if (terisi < 2) { status.className = 'chip chip-info'; status.textContent = 'Isi minimal dua baris'; }
    else if (salah) { status.className = 'chip chip-sand'; status.textContent = 'Setiap baris perlu akun dan satu nominal (debit atau kredit)'; }
    else if (d !== k) { status.className = 'chip chip-bad'; status.textContent = 'Selisih Rp ' + nf.format(Math.abs(d - k)) + (d > k ? ' di debit' : ' di kredit'); }
    else { status.className = 'chip chip-ok'; status.textContent = 'Seimbang · Rp ' + nf.format(d); ok = true; }
    simpan.disabled = !ok;
  }

  function isi(data) {
    tbody.innerHTML = '';
    form.querySelector('[data-ids]').innerHTML = '';
    var ubah = data && data.ids && data.ids.length;
    judul.textContent = ubah ? judul.dataset.judulUbah : judul.dataset.judulBaru;
    if (data) {
      if (data.tanggal) form.tanggal.value = data.tanggal;
      form.keterangan.value = data.keterangan || '';
      (data.ids || []).forEach(function (id) {
        var h = document.createElement('input');
        h.type = 'hidden'; h.name = 'ids[]'; h.value = id;
        form.querySelector('[data-ids]').appendChild(h);
      });
      (data.baris || []).forEach(function (b) { tambahBaris(Array.isArray(b) ? { akun: b[0], debit: String(b[1] || '').replace(/[^\d]/g, ''), kredit: String(b[2] || '').replace(/[^\d]/g, '') } : b); });
    } else {
      form.keterangan.value = '';
    }
    while (tbody.children.length < 2) tambahBaris();
    hitung();
    dlg.showModal();
    (data ? tbody.querySelector('select') : form.keterangan).focus();
  }

  document.querySelectorAll('[data-jurnal-baru]').forEach(function (b) { b.addEventListener('click', function () { isi(null); }); });
  document.querySelectorAll('[data-jurnal-ubah]').forEach(function (b) {
    b.addEventListener('click', function () { isi(JSON.parse(b.getAttribute('data-jurnal-ubah'))); });
  });

  form.querySelector('[data-tambah-baris]').addEventListener('click', function () {
    tambahBaris().querySelector('select').focus();
    hitung();
  });

  tbody.addEventListener('click', function (e) {
    var hapus = e.target.closest('[data-hapus-baris]');
    if (!hapus) return;
    hapus.closest('tr').remove();
    if (tbody.children.length < 2) tambahBaris();
    hitung();
  });

  tbody.addEventListener('input', function (e) {
    if (e.target.matches('.num')) {
      format(e.target);
      // Mengisi debit mengosongkan kredit pada baris yang sama, dan sebaliknya
      var tr = e.target.closest('tr');
      var lawan = tr.querySelector(e.target.name === 'debit[]' ? '[name="kredit[]"]' : '[name="debit[]"]');
      if (nilai(e.target) > 0) lawan.value = '';
    }
    hitung();
  });
  tbody.addEventListener('change', hitung);

  // Kembalikan isian bila server menolak
  var lama = document.getElementById('jurnal-lama');
  if (lama) isi(JSON.parse(lama.textContent));
})();

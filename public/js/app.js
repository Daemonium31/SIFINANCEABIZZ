// Perilaku umum aplikasi: menu, dialog, konfirmasi hapus, notifikasi.
(function () {
  'use strict';

  document.addEventListener('click', function (e) {
    var toggle = e.target.closest('[data-nav-toggle]');
    if (toggle) {
      document.getElementById('shell').classList.toggle('nav-open');
      return;
    }

    var opener = e.target.closest('[data-open]');
    if (opener) {
      var dlg = document.querySelector(opener.getAttribute('data-open'));
      if (dlg && typeof dlg.showModal === 'function') {
        e.preventDefault();
        dlg.showModal();
        var first = dlg.querySelector('[autofocus], input:not([type=hidden]):not([readonly]), select, textarea');
        if (first) first.focus();
      }
      return;
    }

    var closer = e.target.closest('[data-close]');
    if (closer) {
      var d = closer.closest('dialog');
      if (d) d.close();
      return;
    }

    var dismiss = e.target.closest('[data-dismiss]');
    if (dismiss) {
      dismiss.parentElement.remove();
    }
  });

  // Klik di luar isi dialog menutup dialog
  document.querySelectorAll('dialog.dlg').forEach(function (dlg) {
    dlg.addEventListener('click', function (e) {
      if (e.target === dlg) dlg.close();
    });
  });

  // Formulir dengan data-confirm meminta konfirmasi lebih dulu
  var konfirmasi = document.getElementById('dlg-konfirmasi');
  var tertunda = null;
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.hasAttribute('data-confirm') || form.dataset.terkonfirmasi === '1' || !konfirmasi) return;
    e.preventDefault();
    tertunda = form;
    konfirmasi.querySelector('[data-konfirmasi-pesan]').textContent = form.getAttribute('data-confirm');
    var tombol = konfirmasi.querySelector('[data-konfirmasi-ya]');
    tombol.textContent = form.getAttribute('data-confirm-label') || 'Ya, lanjutkan';
    konfirmasi.showModal();
  });
  if (konfirmasi) {
    konfirmasi.addEventListener('close', function () {
      if (konfirmasi.returnValue === 'ya' && tertunda) {
        tertunda.dataset.terkonfirmasi = '1';
        if (typeof tertunda.requestSubmit === 'function') tertunda.requestSubmit(); else tertunda.submit();
      }
      tertunda = null;
    });
  }

  // Notifikasi berhasil hilang sendiri
  var ok = document.querySelector('.flash-ok');
  if (ok) setTimeout(function () { ok.style.transition = 'opacity .3s'; ok.style.opacity = '0'; setTimeout(function () { ok.remove(); }, 320); }, 4500);
})();

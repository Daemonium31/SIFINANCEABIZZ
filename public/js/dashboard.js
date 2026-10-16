// Grafik dashboard (Chart.js 4, lokal) + mode presentasi + ringkasan PDF.
(function () {
  'use strict';

  var data = JSON.parse(document.getElementById('data-grafik').textContent);
  var beban = JSON.parse(document.getElementById('data-beban').textContent);
  var W = data.warna;
  var INK = '#172a3b', INK3 = '#5a6a72', GRID = '#ecebe4', SURFACE = '#fffefa';

  var nf = new Intl.NumberFormat('id-ID');
  function rupiah(v) { return (v < 0 ? '-' : '') + 'Rp ' + nf.format(Math.abs(Math.round(v))); }
  function singkat(v) {
    var a = Math.abs(v), s;
    if (a >= 1e9) s = (a / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' M';
    else if (a >= 1e6) s = (a / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt';
    else if (a >= 1e3) s = (a / 1e3).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + ' rb';
    else s = nf.format(a);
    return (v < 0 ? '-' : '') + 'Rp ' + s;
  }

  Chart.defaults.font.family = "'Source Sans 3', 'Segoe UI', sans-serif";
  Chart.defaults.font.size = 13;
  Chart.defaults.color = INK3;
  Chart.defaults.devicePixelRatio = Math.max(2, window.devicePixelRatio || 1);
  Chart.defaults.animation.duration = 450;
  Chart.defaults.plugins.legend.labels.usePointStyle = true;
  Chart.defaults.plugins.legend.labels.pointStyle = 'rectRounded';
  Chart.defaults.plugins.legend.labels.boxWidth = 10;
  Chart.defaults.plugins.legend.labels.color = INK;
  Chart.defaults.plugins.tooltip.backgroundColor = INK;
  Chart.defaults.plugins.tooltip.titleFont = { family: "'DM Sans', sans-serif", weight: '700', size: 13 };
  Chart.defaults.plugins.tooltip.bodyFont = { size: 13 };
  Chart.defaults.plugins.tooltip.padding = 10;
  Chart.defaults.plugins.tooltip.cornerRadius = 6;
  Chart.defaults.plugins.tooltip.boxPadding = 4;
  Chart.defaults.plugins.tooltip.usePointStyle = true;
  Chart.defaults.plugins.tooltip.callbacks.label = function (c) {
    var v = c.parsed.y !== undefined && c.chart.options.indexAxis !== 'y' ? c.parsed.y : c.parsed.x;
    return ' ' + (c.dataset.label || c.label) + ': ' + rupiah(v);
  };

  // Latar kanvas ikut warna kertas agar PNG untuk PDF tidak transparan
  var latar = {
    id: 'latar',
    beforeDraw: function (chart) {
      var ctx = chart.ctx; ctx.save(); ctx.globalCompositeOperation = 'destination-over';
      ctx.fillStyle = SURFACE; ctx.fillRect(0, 0, chart.width, chart.height); ctx.restore();
    }
  };

  // Label nilai di ujung batang horizontal (label langsung, bukan di setiap titik)
  var labelUjung = {
    id: 'labelUjung',
    afterDatasetsDraw: function (chart, args, opt) {
      if (!opt || !opt.aktif) return;
      var ctx = chart.ctx;
      chart.data.datasets.forEach(function (ds, i) {
        chart.getDatasetMeta(i).data.forEach(function (bar, j) {
          var v = ds.data[j];
          ctx.save();
          ctx.font = '700 ' + (opt.ukuran || 13) + "px 'DM Sans', sans-serif";
          ctx.fillStyle = INK;
          ctx.textBaseline = 'middle';
          var x = v >= 0 ? bar.x + 8 : bar.x - 8;
          ctx.textAlign = v >= 0 ? 'left' : 'right';
          ctx.fillText(opt.format ? opt.format(v) : rupiah(v), x, bar.y);
          ctx.restore();
        });
      });
    }
  };
  Chart.register(latar, labelUjung);

  function sumbu(extra) {
    return Object.assign({
      grid: { color: GRID, drawTicks: false },
      border: { display: false },
      ticks: { padding: 8, callback: function (v) { return singkat(v); } }
    }, extra || {});
  }
  function sumbuKategori(extra) {
    return Object.assign({ grid: { display: false }, border: { color: '#d8d9d1' }, ticks: { padding: 6 } }, extra || {});
  }

  // ---------------------------------------------------------------- konfigurasi
  function cfgLabaRugi(besar) {
    var lr = data.labarugi;
    return {
      type: 'bar',
      data: {
        labels: ['Pendapatan', 'Beban', lr.laba >= 0 ? 'Laba bersih' : 'Rugi bersih'],
        datasets: [{ data: [lr.pendapatan, lr.beban, lr.laba], backgroundColor: [W.pendapatan, W.beban, W.laba], borderRadius: 4, borderSkipped: 'start', barPercentage: 0.62, categoryPercentage: 0.9 }]
      },
      options: {
        indexAxis: 'y', maintainAspectRatio: false,
        layout: { padding: { right: besar ? 190 : 0 } },
        plugins: { legend: { display: false }, labelUjung: { aktif: !!besar, ukuran: besar ? 22 : 13 } },
        scales: { x: sumbu({ beginAtZero: true, ticks: { display: !besar, padding: 6, maxTicksLimit: 4, callback: function (v) { return singkat(v); } }, grid: { color: besar ? 'transparent' : GRID, drawTicks: false } }), y: sumbuKategori({ ticks: { color: INK, font: { size: besar ? 20 : 13, weight: '600' } } }) }
      }
    };
  }

  function cfgTren(besar) {
    var t = data.tren;
    return {
      type: 'bar',
      data: {
        labels: t.label,
        datasets: [
          { type: 'line', label: 'Laba bersih', data: t.laba, borderColor: W.laba, backgroundColor: W.laba, borderWidth: 2, pointRadius: 4, pointHoverRadius: 6, pointBorderColor: SURFACE, pointBorderWidth: 2, cubicInterpolationMode: 'monotone', order: 0 },
          { label: 'Pendapatan', data: t.pendapatan, backgroundColor: W.pendapatan, borderRadius: 4, borderSkipped: 'start', maxBarThickness: besar ? 54 : 30, order: 1 },
          { label: 'Beban', data: t.beban, backgroundColor: W.beban, borderRadius: 4, borderSkipped: 'start', maxBarThickness: besar ? 54 : 30, order: 1 }
        ]
      },
      options: {
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        datasets: { bar: { categoryPercentage: 0.72, barPercentage: 0.86 } },
        plugins: { legend: { position: 'top', align: 'end', labels: { font: { size: besar ? 17 : 13 } } } },
        scales: { y: sumbu({ ticks: { padding: 8, font: { size: besar ? 15 : 12 }, callback: function (v) { return singkat(v); } } }), x: sumbuKategori({ ticks: { font: { size: besar ? 17 : 13 } } }) }
      }
    };
  }

  function cfgKasSaldo(besar) {
    return {
      type: 'line',
      data: {
        labels: data.kas.label,
        datasets: [{ label: 'Saldo kas', data: data.kas.saldo, borderColor: W.pendapatan, backgroundColor: 'rgba(47, 98, 192, .08)', fill: true, borderWidth: 2, pointRadius: 0, pointHoverRadius: 5, cubicInterpolationMode: 'monotone' }]
      },
      options: {
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: !!besar, position: 'top', align: 'end', labels: { font: { size: 17 } } } },
        scales: { y: sumbu({ ticks: { padding: 8, maxTicksLimit: 4, font: { size: besar ? 15 : 12 }, callback: function (v) { return singkat(v); } } }), x: sumbuKategori({ ticks: { maxRotation: 0, autoSkipPadding: 14, font: { size: besar ? 15 : 12 }, display: !!besar } }) }
      }
    };
  }

  function cfgKasMutasi() {
    return {
      type: 'bar',
      data: {
        labels: data.kas.label,
        datasets: [
          { label: 'Kas masuk', data: data.kas.masuk, backgroundColor: W.laba, borderRadius: 3, borderSkipped: 'start', maxBarThickness: 14 },
          { label: 'Kas keluar', data: data.kas.keluar, backgroundColor: W.beban, borderRadius: 3, borderSkipped: 'start', maxBarThickness: 14 }
        ]
      },
      options: {
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: { position: 'top', align: 'end' },
          tooltip: { callbacks: { label: function (c) { return ' ' + c.dataset.label + ': ' + rupiah(Math.abs(c.parsed.y)); } } }
        },
        scales: {
          x: sumbuKategori({ stacked: true, ticks: { maxRotation: 0, autoSkipPadding: 14, font: { size: 12 } } }),
          y: sumbu({ stacked: true, ticks: { padding: 8, maxTicksLimit: 3, font: { size: 12 }, callback: function (v) { return singkat(Math.abs(v)); } } })
        }
      }
    };
  }

  function cfgPosisi(besar) {
    var p = data.posisi;
    var seg = function (label, nilai, warna) {
      return { label: label, data: nilai, backgroundColor: warna, borderColor: SURFACE, borderWidth: { right: 2 }, borderSkipped: false, barPercentage: 0.7, categoryPercentage: 0.9 };
    };
    return {
      type: 'bar',
      data: {
        labels: ['Aktiva', 'Pasiva'],
        datasets: [
          seg('Aktiva lancar', [p.aset_lancar, 0], W.pendapatan),
          seg('Aktiva tetap', [p.aset_tetap, 0], W.aset_tetap),
          seg('Utang jangka pendek', [0, p.utang_pendek], W.beban),
          seg('Utang jangka panjang', [0, p.utang_panjang], W.utang_panjang),
          seg('Ekuitas', [0, p.ekuitas], W.laba)
        ]
      },
      options: {
        indexAxis: 'y', maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', align: 'start', labels: { font: { size: besar ? 17 : 13 }, filter: function (item, d) { return d.datasets[item.datasetIndex].data.some(function (v) { return v !== 0; }); } } },
          tooltip: { filter: function (c) { return c.parsed.x !== 0; } }
        },
        scales: {
          x: sumbu({ stacked: true, beginAtZero: true, ticks: { padding: 6, font: { size: besar ? 15 : 12 }, callback: function (v) { return singkat(v); } } }),
          y: sumbuKategori({ stacked: true, ticks: { color: INK, font: { size: besar ? 19 : 14, weight: '600' } } })
        }
      }
    };
  }

  function cfgBeban() {
    return {
      type: 'bar',
      data: { labels: beban.label, datasets: [{ label: 'Beban', data: beban.nilai, backgroundColor: W.beban, borderRadius: 4, borderSkipped: 'start', barPercentage: 0.7 }] },
      options: {
        indexAxis: 'y', maintainAspectRatio: false,
        layout: { padding: { right: 200 } },
        plugins: { legend: { display: false }, labelUjung: { aktif: true, ukuran: 20 } },
        scales: { x: { display: false, beginAtZero: true }, y: sumbuKategori({ ticks: { color: INK, font: { size: 18, weight: '600' } } }) }
      }
    };
  }

  // ---------------------------------------------------------------- dashboard
  var grafik = {
    labarugi: new Chart(document.getElementById('g-labarugi'), cfgLabaRugi(false)),
    kas_saldo: new Chart(document.getElementById('g-kas-saldo'), cfgKasSaldo(false)),
    kas_mutasi: new Chart(document.getElementById('g-kas-mutasi'), cfgKasMutasi()),
    tren: new Chart(document.getElementById('g-tren'), cfgTren(false)),
    posisi: new Chart(document.getElementById('g-posisi'), cfgPosisi(false))
  };

  // ---------------------------------------------------------------- ringkasan PDF
  var tombolPdf = document.querySelector('[data-unduh-pdf]');
  tombolPdf.addEventListener('click', function () {
    var form = document.getElementById('form-pdf');
    Object.keys(grafik).forEach(function (k) {
      form.querySelector('[name="grafik_' + k + '"]').value = grafik[k].toBase64Image('image/png', 1);
    });
    form.submit();
  });

  // ---------------------------------------------------------------- presentasi
  var deck = document.getElementById('deck');
  var slides = Array.prototype.slice.call(deck.querySelectorAll('.slide'));
  var dots = Array.prototype.slice.call(deck.querySelectorAll('[data-deck-go]'));
  var sekarang = 0;
  var dibuat = {};
  var pabrik = { labarugi: cfgLabaRugi, tren: cfgTren, kas: cfgKasSaldo, beban: cfgBeban, posisi: cfgPosisi };

  function tampil(i) {
    sekarang = Math.max(0, Math.min(slides.length - 1, i));
    slides.forEach(function (s, n) { s.classList.toggle('is-current', n === sekarang); });
    dots.forEach(function (d, n) { d.classList.toggle('is-current', n === sekarang); });
    deck.querySelector('[data-deck-now]').textContent = sekarang + 1;
    var kanvas = slides[sekarang].querySelector('[data-slide-chart]');
    if (kanvas && !dibuat[kanvas.dataset.slideChart]) {
      dibuat[kanvas.dataset.slideChart] = new Chart(kanvas, pabrik[kanvas.dataset.slideChart](true));
    }
  }

  function buka() {
    deck.hidden = false;
    deck.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    if (deck.requestFullscreen) deck.requestFullscreen().catch(function () {});
    tampil(0);
  }

  function tutup() {
    deck.classList.remove('is-open');
    deck.hidden = true;
    document.body.style.overflow = '';
    if (document.fullscreenElement && document.exitFullscreen) document.exitFullscreen().catch(function () {});
  }

  document.querySelector('[data-presentasi]').addEventListener('click', buka);
  deck.querySelector('[data-deck-close]').addEventListener('click', tutup);
  deck.querySelector('[data-deck-next]').addEventListener('click', function () { tampil(sekarang + 1); });
  deck.querySelector('[data-deck-prev]').addEventListener('click', function () { tampil(sekarang - 1); });
  dots.forEach(function (d) { d.addEventListener('click', function () { tampil(+d.dataset.deckGo); }); });
  document.addEventListener('fullscreenchange', function () {
    if (!document.fullscreenElement && deck.classList.contains('is-open')) tutup();
  });
  document.addEventListener('keydown', function (e) {
    if (!deck.classList.contains('is-open')) return;
    if (e.key === 'ArrowRight' || e.key === 'PageDown' || e.key === ' ') { e.preventDefault(); tampil(sekarang + 1); }
    else if (e.key === 'ArrowLeft' || e.key === 'PageUp') { e.preventDefault(); tampil(sekarang - 1); }
    else if (e.key === 'Home') tampil(0);
    else if (e.key === 'End') tampil(slides.length - 1);
    else if (e.key === 'Escape') tutup();
  });
})();

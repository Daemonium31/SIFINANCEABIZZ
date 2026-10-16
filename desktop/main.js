// Sistem Informasi Keuangan: membungkus aplikasi CodeIgniter dengan PHP bawaan agar
// berjalan sebagai aplikasi desktop tanpa internet. Data (SQLite), sesi, cache
// dan log disimpan di folder data pengguna, bukan di folder instalasi.
'use strict';

const { app, BrowserWindow, dialog, shell, Menu } = require('electron');
const { spawn, spawnSync } = require('child_process');
const fs = require('fs');
const http = require('http');
const net = require('net');
const path = require('path');

const NAMA = 'Sistem Informasi Keuangan';
let jendela = null;
let server = null;

if (!app.requestSingleInstanceLock()) {
  app.quit();
}

function lokasi() {
  const sumber = app.isPackaged ? process.resourcesPath : path.join(__dirname, 'build');
  const data = app.getPath('userData');
  return {
    app: path.join(sumber, 'app'),
    php: path.join(sumber, 'php', 'php.exe'),
    ini: path.join(sumber, 'php', 'php.ini'),
    data,
    writable: path.join(data, 'writable'),
    db: path.join(data, 'data', 'keuangan.db'),
  };
}

function siapkanFolder(p) {
  for (const sub of ['cache', 'logs', 'session', 'uploads', 'fonts', 'debugbar']) {
    fs.mkdirSync(path.join(p.writable, sub), { recursive: true });
  }
  fs.mkdirSync(path.dirname(p.db), { recursive: true });
}

function tulisEnv(p, port) {
  const isi = [
    'CI_ENVIRONMENT = production',
    `app.baseURL = 'http://127.0.0.1:${port}/'`,
    'database.default.DBDriver = SQLite3',
    `database.default.database = '${p.db}'`,
    "database.default.hostname = ''",
    "database.default.username = ''",
    "database.default.password = ''",
    'database.default.foreignKeys = true',
    '',
  ].join('\n');
  fs.writeFileSync(path.join(p.data, '.env'), isi, 'utf8');
}

function portKosong() {
  return new Promise((resolve, reject) => {
    const s = net.createServer();
    s.unref();
    s.on('error', reject);
    s.listen(0, '127.0.0.1', () => {
      const { port } = s.address();
      s.close(() => resolve(port));
    });
  });
}

function envPhp(p) {
  return Object.assign({}, process.env, { CE_WRITABLE: p.writable, CE_ENV_DIR: p.data, PHPRC: path.dirname(p.ini) });
}

function migrasi(p) {
  const hasil = spawnSync(p.php, ['-c', p.ini, 'spark', 'migrate', '--all', '--no-header'], {
    cwd: p.app, env: envPhp(p), windowsHide: true, encoding: 'utf8',
  });
  fs.writeFileSync(path.join(p.writable, 'logs', 'migrasi.log'), (hasil.stdout || '') + (hasil.stderr || ''), 'utf8');
  if (hasil.status !== 0) {
    throw new Error('Basis data gagal disiapkan.\n\n' + (hasil.stderr || hasil.stdout || '').slice(-1200));
  }
}

function jalankanServer(p, port) {
  const log = fs.openSync(path.join(p.writable, 'logs', 'server.log'), 'a');
  server = spawn(p.php, [
    '-c', p.ini,
    '-S', `127.0.0.1:${port}`,
    '-t', path.join(p.app, 'public'),
    path.join(p.app, 'vendor', 'codeigniter4', 'framework', 'system', 'rewrite.php'),
  ], { cwd: p.app, env: envPhp(p), windowsHide: true, stdio: ['ignore', log, log] });
  server.on('exit', (kode) => {
    server = null;
    if (jendela && !app.isQuitting) {
      dialog.showErrorBox(NAMA, `Mesin aplikasi berhenti (kode ${kode}). Tutup lalu buka kembali aplikasi.`);
    }
  });
}

function tungguSiap(port, batasMs = 20000) {
  const mulai = Date.now();
  return new Promise((resolve, reject) => {
    (function coba() {
      const req = http.get({ host: '127.0.0.1', port, path: '/login', timeout: 1500 }, (res) => {
        res.resume();
        resolve();
      });
      req.on('error', () => {
        if (Date.now() - mulai > batasMs) reject(new Error('Mesin aplikasi tidak merespons.'));
        else setTimeout(coba, 250);
      });
      req.on('timeout', () => req.destroy());
    })();
  });
}

function buatJendela() {
  jendela = new BrowserWindow({
    width: 1440,
    height: 900,
    minWidth: 1080,
    minHeight: 680,
    title: NAMA,
    backgroundColor: '#f7f6f1',
    icon: path.join(__dirname, 'build', 'icon.png'),
    autoHideMenuBar: true,
    show: false,
    webPreferences: { contextIsolation: true, nodeIntegration: false, sandbox: true, spellcheck: false },
  });
  jendela.once('ready-to-show', () => { jendela.maximize(); jendela.show(); });
  jendela.loadFile(path.join(__dirname, 'memuat.html'));

  // Hanya alamat lokal aplikasi yang boleh dibuka di jendela ini
  const lokal = (url) => /^http:\/\/127\.0\.0\.1:\d+\//.test(url) || url.startsWith('file:');
  jendela.webContents.on('will-navigate', (e, url) => {
    if (!lokal(url)) { e.preventDefault(); shell.openExternal(url); }
  });
  jendela.webContents.setWindowOpenHandler(({ url }) => {
    if (!lokal(url)) shell.openExternal(url);
    return { action: 'deny' };
  });

  // Unduhan PDF/cadangan: tanyakan lokasi simpan, default ke folder Dokumen
  jendela.webContents.session.on('will-download', (e, item) => {
    item.setSaveDialogOptions({ defaultPath: path.join(app.getPath('documents'), item.getFilename()) });
  });

  jendela.on('closed', () => { jendela = null; });
}

async function mulai() {
  Menu.setApplicationMenu(null);
  buatJendela();
  const p = lokasi();
  try {
    if (!fs.existsSync(p.php)) throw new Error('PHP bawaan tidak ditemukan di ' + p.php);
    siapkanFolder(p);
    const port = await portKosong();
    tulisEnv(p, port);
    migrasi(p);
    jalankanServer(p, port);
    await tungguSiap(port);
    if (jendela) jendela.loadURL(`http://127.0.0.1:${port}/`);
  } catch (err) {
    dialog.showErrorBox(NAMA, err.message + '\n\nLog: ' + path.join(p.writable, 'logs'));
    app.quit();
  }
}

app.on('second-instance', () => {
  if (jendela) { if (jendela.isMinimized()) jendela.restore(); jendela.focus(); }
});
app.on('before-quit', () => { app.isQuitting = true; if (server) server.kill(); });
app.on('window-all-closed', () => app.quit());
app.whenReady().then(mulai);

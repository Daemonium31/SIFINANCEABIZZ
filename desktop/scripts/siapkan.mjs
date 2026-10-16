// Menyiapkan isi aplikasi desktop di desktop/build:
//   build/app  salinan aplikasi CodeIgniter + vendor tanpa paket pengembangan
//   build/php  runtime PHP minimal (ekstensi yang dibutuhkan saja) + php.ini
//   build/icon.png
// PHP sumber diambil dari PHP_DIR, atau PHP Laragon bila tidak diisi.
import { cpSync, existsSync, mkdirSync, readdirSync, rmSync, writeFileSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const desktop = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const proyek = path.resolve(desktop, '..');
const build = path.join(desktop, 'build');
const phpSumber = process.env.PHP_DIR || 'C:\\laragon\\bin\\php\\php-8.3.33-Win32-vs16-x64';

const EKSTENSI = ['intl', 'mbstring', 'sqlite3', 'gd', 'fileinfo', 'opcache'];

function jalankan(cmd, args, opsi = {}) {
  const r = spawnSync(cmd, args, { stdio: 'inherit', shell: true, ...opsi });
  if (r.status !== 0) throw new Error(`Gagal: ${cmd} ${args.join(' ')}`);
}

if (!existsSync(path.join(phpSumber, 'php.exe'))) {
  throw new Error(`php.exe tidak ditemukan di ${phpSumber}. Isi PHP_DIR dengan folder PHP 8.2+ (Windows x64, thread safe).`);
}

// ------------------------------------------------------------------ aplikasi
const tujuanApp = path.join(build, 'app');
rmSync(tujuanApp, { recursive: true, force: true });
mkdirSync(tujuanApp, { recursive: true });

const lewati = new Set([
  path.join(proyek, 'app', 'Database', 'Seeds'),
]);
for (const item of ['app', 'public', 'spark', 'composer.json', 'composer.lock']) {
  cpSync(path.join(proyek, item), path.join(tujuanApp, item), {
    recursive: true,
    filter: (src) => !lewati.has(src) && !src.endsWith('.htaccess.bak'),
  });
}
for (const sub of ['cache', 'logs', 'session', 'uploads', 'debugbar']) {
  mkdirSync(path.join(tujuanApp, 'writable', sub), { recursive: true });
}

console.log('» composer install --no-dev');
const envComposer = { ...process.env, PATH: `${phpSumber};${process.env.PATH}`, COMPOSER_NO_INTERACTION: '1' };
jalankan('composer', ['install', '--no-dev', '--optimize-autoloader', '--no-progress', '--prefer-dist'], { cwd: tujuanApp, env: envComposer });
rmSync(path.join(tujuanApp, 'composer.json'), { force: true });
rmSync(path.join(tujuanApp, 'composer.lock'), { force: true });

// ------------------------------------------------------------------ PHP
const tujuanPhp = path.join(build, 'php');
rmSync(tujuanPhp, { recursive: true, force: true });
mkdirSync(path.join(tujuanPhp, 'ext'), { recursive: true });

// Hanya berkas yang dibutuhkan php.exe dan ekstensi di atas (dicek dengan deplister.exe)
const dibutuhkan = /^(php\.exe|php8ts\.dll|icu(dt|in|io|uc)\d+\.dll|libsqlite3\.dll)$/i;
for (const f of readdirSync(phpSumber)) {
  if (dibutuhkan.test(f)) cpSync(path.join(phpSumber, f), path.join(tujuanPhp, f));
}
// Runtime Visual C++ disertakan agar jalan di komputer tanpa VC++ Redistributable
const sistem = path.join(process.env.SystemRoot || 'C:\Windows', 'System32');
for (const f of ['vcruntime140.dll', 'vcruntime140_1.dll', 'msvcp140.dll']) {
  if (existsSync(path.join(sistem, f))) cpSync(path.join(sistem, f), path.join(tujuanPhp, f));
}
for (const ext of EKSTENSI) {
  cpSync(path.join(phpSumber, 'ext', `php_${ext}.dll`), path.join(tujuanPhp, 'ext', `php_${ext}.dll`));
}
for (const lisensi of ['license.txt', 'LICENSE', 'README.md']) {
  if (existsSync(path.join(phpSumber, lisensi))) cpSync(path.join(phpSumber, lisensi), path.join(tujuanPhp, lisensi));
}

writeFileSync(path.join(tujuanPhp, 'php.ini'), [
  '; PHP untuk Sistem Informasi Keuangan (dibuat oleh scripts/siapkan.mjs)',
  'extension_dir = "ext"',
  ...EKSTENSI.filter((e) => e !== 'opcache').map((e) => `extension = ${e}`),
  'zend_extension = opcache',
  'opcache.enable = 1',
  'opcache.enable_cli = 1',
  'opcache.validate_timestamps = 0',
  'memory_limit = 512M',
  'max_execution_time = 120',
  'post_max_size = 64M',
  'upload_max_filesize = 32M',
  'date.timezone = Asia/Jakarta',
  'display_errors = Off',
  'log_errors = On',
  'expose_php = Off',
  'variables_order = "EGPCS"',
  'default_charset = "UTF-8"',
  '',
].join('\r\n'));

// ------------------------------------------------------------------ ikon
// Ikon aplikasi bawaan (netral). Ganti public/img/app-icon.png (PNG 512 px) untuk ikon sendiri.
cpSync(path.join(proyek, 'public', 'img', 'app-icon.png'), path.join(build, 'icon.png'));

console.log('Selesai: desktop/build siap dikemas.');

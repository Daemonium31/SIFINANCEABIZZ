# Sistem Informasi Keuangan

Aplikasi akuntansi sumber terbuka untuk usaha kecil dan menengah di Indonesia. Anda cukup mencatat **jurnal umum** dan **jurnal penyesuaian**; sepuluh tahap **siklus akuntansi** tersusun otomatis, bisa dilihat di layar, dan diunduh sebagai **PDF**. Tersedia juga **dashboard grafik** dengan **mode presentasi** untuk melaporkan ke pimpinan.

Bisa dipakai sebagai **aplikasi web** (Laragon/XAMPP/server) atau dikemas menjadi **aplikasi desktop Windows yang berjalan tanpa internet**. Nama dan logo perusahaan diatur sendiri dari dalam aplikasi, jadi siapa pun bisa memakainya untuk usahanya.

> Format laporan mengikuti bentuk yang diajarkan di buku akuntansi Indonesia (jurnal dengan kolom Ref, buku besar bersaldo berjalan, neraca lajur 10 kolom, laporan keuangan bentuk skontro). Rujukan tampilan dashboard: Accurate Online.

---

## Daftar isi

1. [Fitur](#fitur)
2. [Persyaratan](#persyaratan)
3. [Instalasi (aplikasi web)](#instalasi-aplikasi-web)
4. [Langkah pertama setelah instalasi](#langkah-pertama-setelah-instalasi)
5. [Mengganti nama dan logo perusahaan](#mengganti-nama-dan-logo-perusahaan)
6. [Cara memakai sehari-hari](#cara-memakai-sehari-hari)
7. [Cadangan data](#cadangan-data)
8. [Aplikasi desktop offline (Windows)](#aplikasi-desktop-offline-windows)
9. [Keamanan sebelum dipakai sungguhan](#keamanan-sebelum-dipakai-sungguhan)
10. [Struktur proyek & dokumentasi](#struktur-proyek--dokumentasi)
11. [Berkontribusi](#berkontribusi)
12. [Lisensi & kredit](#lisensi--kredit)

---

## Fitur

**Sepuluh laporan siklus akuntansi**, semuanya dengan pemeriksaan keseimbangan (*Seimbang / Tidak seimbang · selisih Rp …*) dan tombol **Unduh PDF**:

| # | Laporan | Sumber |
|---|---|---|
| 1 | Jurnal Umum | dicatat pengguna |
| 2 | Buku Besar (filter per akun; posting JU / +penyesuaian / +penutup) | otomatis |
| 3 | Neraca Saldo | otomatis |
| 4 | Jurnal Penyesuaian | dicatat pengguna |
| 5 | Neraca Saldo Setelah Penyesuaian | otomatis |
| 6 | Neraca Lajur (10 kolom) | otomatis |
| 7 | Laporan Keuangan dalam Rupiah: Laba Rugi, Perubahan Ekuitas, Posisi Keuangan | otomatis |
| 8 | Jurnal Penutup | **dibentuk otomatis** |
| 9 | Neraca Saldo Setelah Penutupan | otomatis |
| 10 | Jurnal Balik (dengan alasan tiap keputusan) | **dibentuk otomatis** |

Lainnya:

- **Paket PDF lengkap**: sepuluh laporan dalam satu berkas bersampul.
- **Dashboard**: pendapatan, beban, laba, saldo kas (dibanding periode sebelumnya), laba rugi, arus kas harian, tren 6 bulan, beban terbesar, posisi keuangan dan rasio.
- **Mode presentasi**: enam slide layar penuh dengan kalimat ringkasan otomatis, plus **ringkasan PDF** dua halaman.
- **Jurnal berbaris banyak**: satu jurnal boleh berisi banyak akun; tombol simpan baru aktif bila debit = kredit.
- **Saldo awal berjalan**: laba periode lalu otomatis masuk ke modal pada periode berikutnya.
- **Tiga peran**: Administrator, Akuntan, Pimpinan (hanya melihat).
- **Cadangan otomatis** (satu berkas yang selalu diperbarui) dan **cadangan manual** (berkas terpisah bertanggal); keduanya bisa dipulihkan.
- **Identitas perusahaan**: nama dan logo diatur administrator, tampil di semua halaman, laporan, dan PDF.
- **Berjalan offline**: semua huruf, ikon, dan pustaka grafik disertakan; tidak ada CDN.
- Mendukung **MySQL/MariaDB** dan **SQLite**.

## Persyaratan

| Kebutuhan | Versi |
|---|---|
| PHP | 8.2 atau lebih baru, dengan ekstensi `intl`, `mbstring`, `gd`, `fileinfo`, serta `mysqli` (MySQL) **atau** `sqlite3` (SQLite) |
| Composer | 2.x |
| Basis data | MySQL 5.7+/MariaDB 10.3+ **atau** SQLite 3 (tanpa server) |
| Untuk aplikasi desktop | Windows 10/11 x64, Node.js 18+ (hanya saat membangun) |

Cara termudah di Windows: [Laragon](https://laragon.org/) atau XAMPP, yang sudah berisi PHP, MySQL, dan Composer.

## Instalasi (aplikasi web)

```bash
# 1. Ambil kode
git clone https://github.com/Daemonium31/SIFINANCEABIZZ.git
cd SIFINANCEABIZZ

# 2. Pasang pustaka PHP
composer install

# 3. Buat berkas konfigurasi
copy env .env        # Windows
# cp env .env        # Linux / macOS
```

**4. Atur `.env`.** Buka `.env`, hapus tanda `#` pada baris yang dipakai, lalu isi:

Pilihan A, MySQL/MariaDB (buat dulu basis data kosong, mis. `keuangan`, lewat phpMyAdmin):

```ini
CI_ENVIRONMENT = production
app.baseURL = 'http://localhost:8080/'
database.default.hostname = localhost
database.default.database = keuangan
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
```

Pilihan B, SQLite (tanpa server basis data; cocok untuk satu komputer). Tulis path lengkap memakai garis miring terbalik di Windows:

```ini
CI_ENVIRONMENT = production
app.baseURL = 'http://localhost:8080/'
database.default.DBDriver = SQLite3
database.default.database = 'C:\data\keuangan\keuangan.db'
database.default.hostname = ''
database.default.username = ''
database.default.password = ''
```

> Gunakan `CI_ENVIRONMENT = development` hanya saat mengembangkan kode: pesan galat lengkap dan toolbar debug akan tampil.

```bash
# 5. Buat tabel
php spark migrate --all

# 6. Jalankan
php spark serve
```

Buka **http://localhost:8080**. Di server sungguhan, arahkan *document root* web server ke folder `public/` (bukan ke root proyek) dan pastikan folder `writable/` bisa ditulisi.

## Langkah pertama setelah instalasi

1. **Buat administrator.** Saat basis data masih kosong, aplikasi langsung membuka layar *Buat administrator*. Isi nama, NIP, email, dan kata sandi (minimal 8 karakter).
2. **Atur identitas perusahaan**: menu **Pengaturan → Identitas Perusahaan** (lihat bagian berikut).
3. **Tambah pengguna**: menu **Pengguna**. Pilih peran *Akuntan* untuk yang mencatat, *Pimpinan* untuk yang hanya membaca laporan.
4. **Susun daftar akun**: menu **Daftar Akun**. Nomor akun menentukan letaknya di laporan:

| Awalan nomor | Kelompok | Contoh |
|---|---|---|
| 11 | Aset lancar | 111 Kas, 112 Piutang Usaha, 113 Perlengkapan |
| 12 | Aset tetap | 121 Peralatan, 122 Akumulasi Penyusutan Peralatan |
| 21 | Utang jangka pendek | 211 Utang Usaha, 212 Utang Gaji |
| 22 | Utang jangka panjang | 221 Utang Bank |
| 3 | Modal / prive / ikhtisar | 311 Modal, 312 Prive, 313 Ikhtisar Laba Rugi |
| 4 | Pendapatan | 411 Pendapatan Jasa |
| 5 | Beban | 511 Beban Gaji, 512 Beban Listrik |

Akun bernama "Akumulasi …" otomatis dihitung sebagai pengurang aset. Akun yang namanya mengandung "Prive" dan "Ikhtisar" dipakai oleh jurnal penutup.

5. **Catat saldo awal** sebagai jurnal umum pertama (mis. Kas dan Peralatan di debit, Modal di kredit).

### Data contoh (opsional, hanya untuk mencoba)

`php spark db:seed AdminSeeder` membuat tiga pengguna contoh (`admin@admin.com`, `accounting@accounting.com`, `ceo@ceo.com`) dengan kata sandi bersama **`ayohitung123`**. Kata sandi ini tercantum di kode sumber publik: **jangan pakai seeder ini untuk data sungguhan**, atau segera ganti semua kata sandinya di **Profil Saya**.

## Mengganti nama dan logo perusahaan

Masuk sebagai **Administrator** → **Pengaturan → Identitas Perusahaan**.

- **Nama perusahaan**: ditulis persis seperti diketik di kepala setiap laporan (*"PT Maju Bersama — Neraca Saldo per 31 Oktober 2026"*), menu samping, halaman masuk, catatan kaki, nama berkas PDF dan cadangan, serta slide presentasi.
- **Logo** (opsional): PNG, JPG, atau WebP, maks. 2 MB. Tepi kosong dipotong otomatis dan gambar diperkecil hingga 512 px. Logo persegi berlatar transparan terlihat paling rapi. Tanpa logo, dua huruf awal nama dipakai sebagai tanda.
- Nama dan logo **ikut tersimpan di berkas cadangan**, jadi ikut pindah bila data dipulihkan di komputer lain.

Hanya tampilan identitas yang diubah dari menu; warna dan huruf aplikasi diatur di `public/css/cyberevo.css` (lihat token warna di bagian atas berkas). Ikon aplikasi desktop: ganti `public/img/app-icon.png` (PNG 512×512).

## Cara memakai sehari-hari

**Setiap transaksi** → **Jurnal Umum → Catat jurnal**. Setiap transaksi selalu menyentuh minimal dua akun (*double entry*): yang bertambah/berkurang di sisi **Debit** dan sumbernya di sisi **Kredit**, dengan total yang sama.

| Contoh | Debit | Kredit |
|---|---|---|
| Pelanggan membayar jasa Rp 5.000.000 | Kas 5.000.000 | Pendapatan Jasa 5.000.000 |
| Membayar listrik Rp 2.000.000 | Beban Listrik 2.000.000 | Kas 2.000.000 |
| Membeli peralatan 60 jt, tunai 40 jt, sisanya utang | Peralatan 60.000.000 | Kas 40.000.000 · Utang Usaha 20.000.000 |

**Akhir bulan:**

1. **Jurnal Penyesuaian**: perlengkapan terpakai, penyusutan, gaji yang belum dibayar, sewa dibayar di muka yang sudah terpakai, pendapatan diterima di muka yang sudah menjadi hak.
2. **Laporan → Siklus Akuntansi**: pilih bulan, periksa tiap tahap bertanda *Seimbang*.
3. Jurnal penutup, neraca saldo setelah penutupan, dan jurnal balik **tidak perlu dicatat**; aplikasi menyusunnya.
4. **Unduh semua laporan (PDF)** untuk arsip; **Dashboard → Mode presentasi** atau **Unduh ringkasan PDF** untuk rapat.
5. **Cadangan Data → Buat cadangan manual**, simpan di flashdisk.

## Cadangan data

| | Cadangan otomatis | Cadangan manual |
|---|---|---|
| Kapan | Setiap kali akun, jurnal, pengguna, profil, atau identitas berubah | Saat tombol **Buat cadangan manual** diklik |
| Berkas | **Satu** berkas yang selalu ditimpa: `writable/cadangan/cadangan-otomatis.json` | Berkas **baru** bertanggal: `<Nama-Perusahaan>_Cadangan-Manual_<tanggal>.json` |

Keduanya berformat JSON yang sama, berisi akun, jurnal, pengguna (hanya *hash* kata sandi), nama dan logo perusahaan, dan bisa dipulihkan dari menu **Cadangan Data**. Pemulihan mengganti **seluruh** data dalam satu transaksi; bila gagal, data lama tetap utuh. Berkas cadangan bersifat rahasia: simpan dengan aman.

Administrator dan Akuntan dapat membuat dan memulihkan cadangan.

## Aplikasi desktop offline (Windows)

Folder `desktop/` membungkus aplikasi dengan Electron dan PHP bawaan. Hasilnya penginstal Windows yang berjalan tanpa internet dan tanpa Laragon. Data (SQLite), sesi, log, dan cadangan disimpan di `%APPDATA%\Sistem Informasi Keuangan\`. Instalasi baru dimulai kosong dan meminta pembuatan administrator.

```bash
cd desktop
npm install
npm run siapkan   # menyalin aplikasi + vendor tanpa paket dev + PHP minimal ke desktop/build
npm run dist      # membuat penginstal NSIS dan versi portable di desktop/dist
```

- `npm run siapkan` mengambil PHP dari Laragon secara bawaan; atur variabel lingkungan `PHP_DIR` ke folder PHP 8.2+ Windows x64 *thread safe* bila letaknya lain.
- Bila `electron-builder` gagal dengan *"Cannot create symbolic link"*, aktifkan **Developer Mode** Windows atau jalankan terminal sebagai Administrator, lalu ulangi. Tambahkan `CSC_IDENTITY_AUTO_DISCOVERY=false` bila tidak punya sertifikat penandatanganan kode.
- Penginstal tidak ditandatangani, sehingga Windows SmartScreen akan memperingatkan saat pertama dijalankan.
- Nama produk, ikon, dan `appId` ada di `desktop/package.json`.

## Keamanan sebelum dipakai sungguhan

- [ ] `CI_ENVIRONMENT = production` di `.env`.
- [ ] Jangan memakai `AdminSeeder` untuk data sungguhan, atau ganti semua kata sandinya.
- [ ] Web server mengarah ke `public/`, bukan root proyek; `.env` dan `writable/` tidak bisa diakses dari browser.
- [ ] Bila dipasang di jaringan/internet: pakai HTTPS dan aktifkan filter `csrf` di `app/Config/Filters.php` (saat ini nonaktif, mengikuti proyek asal). Aplikasi desktop hanya mendengarkan `127.0.0.1`.
- [ ] Jadwalkan cadangan manual rutin ke media lain.
- [ ] Jangan pernah meng-*commit* `.env`, folder `writable/cadangan/`, atau berkas cadangan ke repositori.

## Struktur proyek & dokumentasi

| Path | Isi |
|---|---|
| `app/Libraries/SiklusAkuntansi.php` | Mesin akuntansi: semua laporan dihitung dari jurnal, tidak ada angka turunan yang disimpan |
| `app/Libraries/Identitas.php` | Nama & logo perusahaan |
| `app/Libraries/Cadangan.php`, `app/Filters/CadanganOtomatis.php` | Format, pemulihan, dan pemicu cadangan |
| `app/Libraries/PdfLaporan.php` | Pembuatan PDF (Dompdf) |
| `app/Controllers/` | Halaman: dashboard, laporan, jurnal, akun, pengguna, cadangan, pengaturan |
| `app/Views/laporan/partials/` | Satu partial per laporan, dipakai di layar **dan** PDF |
| `public/css/cyberevo.css` | Seluruh tampilan (token warna, komponen) |
| `desktop/` | Pembungkus aplikasi desktop |
| `app/Database/Migrations/` | Struktur tabel: `akun`, `jurnal_umum`, `jurnal_penyesuaian`, `users`, `pengaturan` |

## Berkontribusi

Laporan masalah dan *pull request* dipersilakan. Aturan yang menjaga pembukuan tetap benar:

1. Laporan selalu dihitung dari jurnal oleh `SiklusAkuntansi`; jangan menyimpan angka turunan.
2. Satu partial untuk layar dan PDF; gunakan markup tabel (Dompdf tidak mendukung flex/grid).
3. SQL harus jalan di MySQL **dan** SQLite (hindari `DATE_FORMAT`, `IF()`).
4. Tidak boleh ada URL eksternal di halaman (aplikasi harus jalan offline).
5. Setelah mengubah mesin akuntansi, uji dengan contoh soal buku teks (mis. neraca lajur yang totalnya sudah diketahui) dan pastikan kesepuluh laporan tetap *Seimbang*.

## Lisensi & kredit

Dirilis dengan **lisensi MIT**. Lihat [`LICENSE`](LICENSE). Anda bebas memakai, mengubah, dan mendistribusikan ulang, termasuk untuk keperluan komersial, selama pemberitahuan hak cipta dan lisensi tetap disertakan. Perangkat lunak disediakan **apa adanya, tanpa jaminan**; periksa kembali hasil pembukuan Anda dengan akuntan.

- Proyek asal: [Qorthony/Sistem-Informasi-Keuangan](https://github.com/Qorthony/Sistem-Informasi-Keuangan) oleh Ahmad Qorthony dan kontributor (2020), juga berlisensi MIT.
- Kerangka: CodeIgniter 4 (MIT); lisensinya ikut terpasang di `vendor/codeigniter4/framework/LICENSE` setelah `composer install`.
- Komponen lain (Dompdf LGPL, Chart.js MIT, ikon Lucide ISC, huruf DM Sans & Source Sans 3 OFL, serta komponen paket desktop): lihat [`THIRD-PARTY-NOTICES.md`](THIRD-PARTY-NOTICES.md).
- Nama dan logo perusahaan yang diisi pemakai adalah milik pemakai masing-masing dan tidak termasuk lisensi ini.

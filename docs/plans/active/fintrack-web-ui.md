---
type: Project Plan
title: FinTrack responsive web application
description: Rencana membangun antarmuka web mobile-first dengan Laravel, Inertia, dan Vue di repo backend.
tags: [fintrack, web, ui, laravel, inertia, vue]
status: active
---

# Rancangan Web Responsif FinTrack

## Ringkasan

Membangun aplikasi web FinTrack yang nyaman dipakai dari desktop maupun browser iPhone, termasuk Safari. Web ditempatkan di aplikasi Laravel backend yang sama dan menggunakan Inertia.js dengan Vue 3. Backend Laravel dan database tetap menjadi sumber data yang sama untuk web dan aplikasi Flutter.

Dokumen ini mencatat rancangan dan kemajuan implementasinya. Repo Flutter `fintrack_app` tidak termasuk lingkup pengerjaan.

## Kemajuan implementasi

| Tahap | Status | Hasil saat ini |
| --- | --- | --- |
| 1. Audit dan fondasi | Selesai | Inertia/Vue/TypeScript terpasang; login, daftar akun, logout, dashboard awal, dan layout responsif tersedia. |
| 2. Alur keuangan inti | Selesai (completed) | Dashboard data nyata, CRUD dompet/transaksi, filter dan paginasi, validasi kepemilikan, serta perubahan saldo atomik pada web. |
| 3. Anggaran dan analitik | Selesai (completed) | CRUD anggaran bulanan, progres di dashboard, analitik bulan/rentang tanggal, grafik tren/distribusi dengan alternatif tabel. |
| 4. Laporan dan profil | Selesai (completed) | Laporan PDF/Excel rentang tanggal dengan pratinjau dan isolasi akun; profil, kata sandi, avatar, dan logout web. |
| 5. Pemolesan dan rilis | Dalam pengerjaan | Optimasi grafik, aksesibilitas, smoke Chromium, dan panduan Arenhost selesai; uji Safari iPhone 14 serta deploy production menunggu akses/hasil pengguna. |

Tahap 1 menambahkan route web berbasis sesi di `routes/web.php`, controller di `app/Http/Controllers/Web/`, middleware Inertia di `app/Http/Middleware/`, halaman Vue di `resources/js/Pages/`, serta tes di `tests/Feature/Web/`. Tahap 2 sudah menghubungkan dashboard ke data keuangan dan menyediakan pengelolaan dompet serta transaksi.

### Hasil tahap 2

- Dashboard membaca total saldo dompet aktif, pemasukan dan pengeluaran bulan berjalan, serta lima transaksi terbaru milik user. Pengeluaran mencakup biaya admin; transfer dipisahkan dari pemasukan/pengeluaran.
- Halaman dompet mendukung tambah, ubah nama/saldo, dan hapus. Dompet yang dipakai sebagai asal atau tujuan transaksi tidak dapat dihapus agar riwayat tetap utuh.
- Halaman transaksi mendukung pencarian judul/catatan, filter jenis/bulan/dompet/kategori, paginasi 15 baris, tambah, ubah, dan hapus, termasuk transfer antar dompet.
- Form memvalidasi kategori menurut jenis, kepemilikan dompet, dompet tujuan yang berbeda, jumlah/biaya admin, dan tanggal. Seluruh query web dibatasi ke akun yang login.
- `WebTransactionService` membalikkan efek lama dan menerapkan efek baru dalam satu transaksi database dengan penguncian dompet. Operasi memakai aritmetika desimal dua digit melalui ekstensi PHP BCMath.
- API Flutter tetap memakai route dan respons yang sama. Penyimpanan transfer API kini mencatat `to_wallet_id`, menolak dompet milik akun lain, dan mencegah penghapusan dompet tujuan yang masih dipakai transaksi.
- Verifikasi tahap 2: `composer test` (33 tes), pemeriksaan TypeScript, build Vite, dan Pint untuk file PHP web/baru. Uji manual Safari iPhone tetap bagian tahap 5.

### Hasil tahap 3

- Halaman anggaran menampilkan batas, realisasi, sisa, persentase, dan status per kategori pada bulan pilihan. Anggaran yang belum diatur khusus pada bulan itu mengikuti catatan kategori terbaru sebagaimana API dan diberi label nilai bawaan.
- Pengguna dapat menyimpan batas kategori untuk bulan tertentu, mengubah batas yang sudah tersimpan, dan menghapusnya. Validasi server membatasi kategori pengeluaran, periode, jumlah, dan kepemilikan anggaran. Mengubah batas tidak mengganti nama kategori global atau memindahkan transaksi.
- Realisasi pengeluaran mencakup biaya admin. Anggaran investasi mengikuti penghitungan transfer yang sudah digunakan API. Dashboard menampilkan tiga anggaran dengan pemakaian tertinggi dan tren enam bulan.
- Halaman analitik mendukung bulan pilihan atau rentang tanggal paling banyak 12 bulan kalender. Ringkasan pemasukan, pengeluaran, arus kas bersih, tren bulanan, dan distribusi kategori berasal dari transaksi milik akun aktif. Rentang tanggal diterapkan tepat pada tanggal awal/akhir yang dipilih.
- Grafik memakai `apexcharts` dan `vue3-apexcharts/core` dengan modul bar, donut, dan legend yang diperlukan. Angka tetap tersedia dalam tabel; grafik disembunyikan dari pembaca layar untuk menghindari duplikasi informasi.
- API analitik mempertahankan bentuk responsnya dan kini menghitung pemasukan dari `amount` saja, selaras dengan dampak saldo serta web. Transfer tetap terpisah dari pemasukan/pengeluaran.
- Verifikasi tahap 3: `composer test` (41 tes, 443 assertion), pemeriksaan TypeScript, build Vite, dan Pint untuk file PHP web/baru lulus. Build memperingatkan ukuran bundle grafik dan Node lokal 22.0.0; optimasi dan uji Safari iPhone termasuk tahap 5.

### Hasil tahap 4

- Halaman Laporan menerima rentang tanggal tepat dengan batas maksimal 12 bulan kalender. Pratinjau menampilkan pemasukan, pengeluaran termasuk biaya admin, arus kas bersih, jumlah transaksi, dan lima transaksi terbaru. Transfer terlihat dalam rincian tetapi tidak masuk arus kas.
- Pengguna dapat mengunduh PDF atau Excel dari periode yang sedang ditampilkan, dengan indikator proses dan pesan kegagalan. Ekspor memakai paket DomPDF dan Laravel Excel yang sudah terpasang, serta nama file berdasarkan rentang tanggal.
- Query pratinjau dan file ekspor dibatasi pada transaksi akun yang login melalui sesi web. Header unduhan mencegah penyimpanan cache; sel Excel yang berasal dari teks pengguna disimpan sebagai teks untuk menghindari rumus aktif. Endpoint ekspor API bulanan dan format respons Flutter tetap terpisah.
- Halaman Profil mendukung ubah nama/email, ganti kata sandi dengan pemeriksaan kata sandi saat ini, unggah atau hapus avatar JPG/PNG maksimal 2 MB, dan logout. Avatar disajikan lewat route milik akun yang login sehingga tidak memerlukan symlink `public/storage` untuk tampilan web.
- Navigasi desktop membuka Laporan dan Profil. Navigasi bawah ponsel membuka Profil; tautan dari Profil menuju Laporan dan Analitik.
- Verifikasi tahap 4: `composer test` (47 tes, 542 assertion), pemeriksaan TypeScript, build Vite, dan Pint untuk file PHP tahap 4 lulus. Build masih memperingatkan Node lokal 22.0.0 dan ukuran bundle grafik; uji Safari iPhone dan optimasi termasuk tahap 5.

### Hasil tahap 5 (implementasi repo)

- Grafik tren dan distribusi diganti dengan komponen CSS/SVG Vue. Dependensi ApexCharts dihapus; chunk grafik hasil build turun dari sekitar 656,65 KB menjadi 2,77 KB, dan peringatan ukuran chunk grafik hilang. Tabel angka tetap tersedia sebagai alternatif aksesibel.
- Layout web memperoleh skip link, fokus keyboard yang lebih jelas, dukungan preferensi gerakan berkurang, perbaikan header pada layar kecil, serta banner saat koneksi gagal pada navigasi Inertia. Error form ditandai agar terbaca oleh teknologi bantu.
- `DatabaseSeeder` pada lingkungan production hanya mengisi kategori, sehingga akun dan data contoh tidak dibuat saat seeding production. Tes regresi untuk perilaku ini ditambahkan.
- Smoke test Chromium lulus untuk 10 halaman pada lebar 320, 390, dan 1280 px (30 kombinasi), termasuk pemeriksaan overflow, navigasi, pendaftaran, dompet, form transaksi, dan pesan kegagalan jaringan. Hasil dan langkah pengulangan ada di [panduan verifikasi browser](../../guides/browser-validation.md).
- `composer test` lulus (48 tes, 545 assertion); pemeriksaan TypeScript, build Vite, dan Pint untuk perubahan PHP tahap 5 juga lulus. Node lokal 22.0.0 masih memunculkan peringatan versi saat build; lingkungan rilis perlu memakai Node 20.19+ atau 22.12+.
- [Panduan deploy Arenhost shared hosting](../../guides/deploy-arenhost.md) sudah disiapkan. Uji Safari pada iPhone 14 dan verifikasi deploy di domain production belum dijalankan; keduanya tetap menjadi syarat sebelum tahap 5 ditandai completed.

## Kondisi saat ini

- Backend menggunakan Laravel 12, PHP 8.2+, Eloquent, Vite, dan Tailwind CSS 4.
- `routes/web.php` menyediakan login, pendaftaran, logout, dashboard, dompet, transaksi, anggaran, analitik, laporan, dan profil yang memerlukan sesi web.
- API keuangan tersedia di `routes/api.php`; endpoint API yang terlindungi menggunakan Sanctum bearer token untuk aplikasi Flutter.
- Model utama mencakup user, kategori, transaksi, dompet, anggaran, laporan, notifikasi, dan riwayat chatbot.
- Inertia, Vue 3, TypeScript, dan Lucide sudah dipasang. Grafik memakai CSS/SVG ringan dengan alternatif tabel; komponen UI memakai Tailwind.

## Tujuan

- Pengguna dapat mengakses dan mengelola keuangan dari browser desktop atau iPhone.
- Pengalaman penggunaan tetap nyaman pada layar kecil, keyboard virtual, dan Safari iOS.
- Web menggunakan akun, aturan bisnis, dan data yang sama dengan aplikasi Flutter.
- Web dan API dapat dirilis dari satu aplikasi Laravel dan satu domain.
- API yang ada tetap kompatibel dengan Flutter.

## Di luar cakupan tahap pertama

- Mengubah atau membangun ulang aplikasi Flutter.
- Mengganti database, model transaksi, atau format respons API yang sudah digunakan Flutter.
- Membuat aplikasi frontend terpisah atau server Node.js untuk produksi.
- PWA/offline mode, push notification web, dan instalasi lewat App Store.
- Menambahkan dependency untuk fitur yang belum dibutuhkan pada alur utama web.

## Pengguna dan alur utama

1. Pengguna membuka domain FinTrack dan masuk dengan email serta kata sandi.
2. Dashboard menampilkan ringkasan dompet, pemasukan, pengeluaran, anggaran, dan transaksi terbaru.
3. Pengguna menambah atau mengubah transaksi dan melihat dampaknya pada dompet serta anggaran.
4. Pengguna menelusuri analitik berdasarkan bulan atau rentang tanggal.
5. Pengguna mengunduh laporan PDF atau Excel dan dapat mengelola profil akunnya.
6. Pengguna keluar dengan mengakhiri sesi web.

## Peta halaman

| Halaman | Isi dan aksi |
| --- | --- |
| Masuk | Form email/kata sandi, tampilkan error validasi, status loading, dan tautan bantuan akun. |
| Daftar akun | Form pembuatan akun dengan validasi server dan sesi web setelah berhasil. |
| Beranda | Saldo semua dompet, pemasukan/pengeluaran bulan ini, grafik ringkas, progres anggaran, transaksi terbaru, dan tombol tambah transaksi. |
| Transaksi | Daftar transaksi dengan pencarian, filter bulan/jenis/kategori/dompet, urutan tanggal, tambah, edit, dan hapus. |
| Form transaksi | Jenis transaksi, jumlah, tanggal, kategori, dompet, catatan, serta dompet tujuan untuk transfer bila didukung model. |
| Dompet | Daftar saldo dan detail dompet; tambah, ubah, atau hapus sesuai batasan backend. |
| Anggaran | Anggaran per kategori dan periode, jumlah terpakai, sisa, progres, serta status mendekati/melewati batas. |
| Analitik | Tren pemasukan dan pengeluaran, distribusi kategori, ringkasan periode, dan pemilih rentang tanggal. |
| Laporan | Pilih rentang tanggal dan ekspor PDF/Excel dengan indikator proses unduhan dan error. |
| Profil | Informasi akun, ubah detail profil/avatar jika didukung, dan keluar. |

Chatbot dan scan struk/voice tidak masuk MVP antarmuka web; evaluasi sebagai tahap lanjutan setelah transaksi inti stabil.

## Navigasi dan tata letak

- **Desktop:** sidebar kiri untuk Beranda, Transaksi, Dompet, Anggaran, Analitik, Laporan, dan Profil; header memuat judul halaman, periode aktif, dan menu akun.
- **Ponsel:** header ringkas dan navigasi bawah untuk Beranda, Transaksi, Dompet, Anggaran, dan Profil. Analitik serta Laporan dapat dicapai dari Beranda atau menu lainnya.
- Form transaksi menggunakan halaman atau sheet penuh di ponsel, tidak mengandalkan modal kecil.
- Daftar transaksi berubah dari tabel desktop menjadi kartu/baris ringkas pada layar kecil.
- Filter yang banyak dikelompokkan dalam panel yang mudah dibuka di ponsel.
- Sediakan state loading, kosong, error, sukses, konfirmasi hapus, dan feedback setelah penyimpanan.

## Arah visual dan aksesibilitas

- Antarmuka bersih dengan latar netral, tipografi mudah dibaca, ruang antar elemen yang cukup, dan nominal utama yang menonjol.
- Warna hijau dan merah membantu membedakan pemasukan/pengeluaran, tetapi label dan ikon juga menyampaikan makna.
- Prioritaskan kontras, fokus keyboard yang terlihat, label input yang jelas, serta elemen interaktif yang dapat digunakan dengan VoiceOver.
- Target sentuh ponsel sekitar 44 × 44 px; hindari aksi penting yang hanya muncul saat hover.
- Gunakan format Rupiah dan tanggal Indonesia; konsistenkan zona waktu dan aturan tanggal backend.
- Hindari overflow horizontal pada lebar 320 px ke atas dan pastikan konten tetap terbaca saat zoom.

## Arsitektur aplikasi

### Web

- Route halaman didefinisikan di `routes/web.php` dan dilindungi middleware sesi Laravel.
- Controller web mengembalikan halaman Inertia dan props yang hanya berisi data yang dibutuhkan halaman.
- Vue menangani komponen dan interaksi antarmuka; navigasi halaman dilakukan melalui Inertia.
- Untuk web, controller dapat membaca model/service Laravel secara langsung. Tidak perlu memanggil endpoint Sanctum API milik Flutter melalui HTTP dari browser.
- Pindahkan aturan bisnis bersama ke service/action hanya jika diperlukan oleh beberapa entry point; hindari menyalin logika hitung saldo dan transfer.
- Semua query dan mutasi data harus dibatasi ke user yang sedang login. Gunakan policy/otorisasi dan validasi server.
- Gunakan session cookie, CSRF protection, dan mekanisme login/logout Laravel untuk web. Autentikasi web berbasis sesi tetap terpisah dari bearer token API Flutter.

### API dan Flutter

- Route, autentikasi bearer token, dan bentuk respons API yang sudah digunakan Flutter dipertahankan.
- Perubahan skema atau aturan transaksi harus diuji terhadap web dan API agar tidak mengubah perilaku yang sudah ada tanpa sengaja.
- Sebelum implementasi, petakan controller/model yang ada untuk memastikan aturan saldo, transfer, biaya admin, soft delete, dan anggaran dipakai konsisten.

## Teknologi dan library yang direncanakan

| Kebutuhan | Pilihan | Penggunaan |
| --- | --- | --- |
| Backend dan routing | Laravel 12 | Route web, middleware sesi, validasi, query model, unduhan laporan. |
| Bridge server/client | Inertia.js Laravel adapter + Vue 3 adapter | Mengirim props Laravel ke halaman Vue tanpa membuat SPA/server terpisah. |
| Frontend | Vue 3 + TypeScript | Halaman, form, state komponen, dan interaksi responsif. |
| Build aset | Vite yang sudah ada | Mengompilasi aset web ke direktori build Laravel. |
| Styling | Tailwind CSS 4 yang sudah terpasang | Layout responsif dan token tampilan dasar. |
| Komponen UI | shadcn-vue | Komponen terpilih seperti dialog, dropdown, tabs, dan form primitives; kode komponen disimpan di repo agar dapat disesuaikan. |
| Ikon | `@lucide/vue` | Ikon navigasi dan aksi yang konsisten; paket `lucide-vue-next` sudah deprecated. |
| Grafik | CSS/SVG Vue tanpa dependency tambahan | Grafik tren dan distribusi ringan pada dashboard/analitik, dengan alternatif tabel. |
| Form | Inertia `useForm` + validasi Laravel | Mengirim form, menampilkan error server, dan menjaga aturan validasi di backend. |
| Format angka/tanggal | `Intl.NumberFormat` + `Intl.DateTimeFormat` | Format `id-ID` tanpa library format tambahan. |
| Notifikasi UI | Komponen toast yang kompatibel dengan Vue, dipilih saat implementasi | Feedback sukses/gagal; hindari notifikasi yang hanya tampil sebentar untuk error form penting. |

Versi package harus diperiksa kecocokannya dengan Laravel 12, Vite 7, Tailwind 4, dan Vue saat implementasi. Vite 7 membutuhkan Node.js 20.19+ atau 22.12+ untuk lingkungan build resmi. Tambahkan hanya package yang benar-benar dipakai.

## Struktur file target

Struktur ini perkiraan awal dan boleh disesuaikan dengan starter kit resmi Laravel/Inertia yang dipilih saat implementasi:

```text
finTrack-be/
├── app/Http/Controllers/Web/
│   ├── Auth/
│   ├── DashboardController.php
│   ├── TransactionController.php
│   ├── WalletController.php
│   ├── BudgetController.php
│   ├── AnalyticsController.php
│   ├── ReportController.php
│   └── ProfileController.php
├── resources/
│   ├── js/
│   │   ├── app.ts
│   │   ├── Components/
│   │   ├── Layouts/
│   │   ├── Pages/
│   │   │   ├── Auth/
│   │   │   ├── Dashboard.vue
│   │   │   ├── Transactions/
│   │   │   ├── Wallets/
│   │   │   ├── Budgets/
│   │   │   ├── Analytics.vue
│   │   │   ├── Reports.vue
│   │   │   └── Profile.vue
│   │   └── types/
│   └── css/app.css
├── routes/web.php
└── tests/Feature/Web/
```

Komponen yang direncanakan meliputi `AppLayout`, sidebar, navigasi bawah, header halaman, kartu ringkasan, filter transaksi, form transaksi, badge tipe transaksi, progress anggaran, state kosong/loading/error, dan konfirmasi aksi berisiko.

## Keamanan dan konsistensi data

- Setiap operasi baca, ubah, hapus, dan ekspor memastikan resource memang milik user aktif.
- Validasi jumlah, tanggal, kategori, dompet, dan periode dilakukan di server.
- Mutasi transaksi yang mengubah beberapa saldo harus mengikuti transaksi database dan aturan domain yang sudah ada.
- Jangan mengirim data sensitif yang tidak diperlukan sebagai Inertia props.
- Terapkan CSRF, rate limiting pada autentikasi, dan cookie aman melalui konfigurasi HTTPS produksi.
- Unduhan laporan hanya boleh mengakses data milik user aktif.

## Tahap pengerjaan dan hasil

1. **Audit dan fondasi — selesai:** cek kontrak model/controller; pasang adaptor Inertia secara manual, Vue, dan konfigurasi Vite; siapkan layout responsif serta autentikasi sesi.
2. **Alur keuangan inti — selesai (completed):** dashboard, dompet, daftar transaksi, form create/edit/delete, validasi, dan otorisasi per user.
3. **Perencanaan dan analitik — selesai:** halaman anggaran, ringkasan grafik, filter periode bulanan/rentang tanggal, dan visualisasi yang punya alternatif tabel/teks.
4. **Laporan dan profil — selesai (completed):** unduhan PDF/Excel yang terotorisasi, pembaruan profil/avatar, kata sandi, dan logout.
5. **Pemolesan dan rilis — dalam pengerjaan:** aksesibilitas, state UI, optimasi bundle grafik, build produksi, dan smoke test desktop selesai; pengujian Safari iPhone serta deploy production masih menunggu pelaksanaan.

### Hasil yang akan dibuat

- Web Laravel/Inertia/Vue yang hidup pada origin/domain backend Laravel yang sama.
- Halaman masuk, pendaftaran akun, dashboard, transaksi, dompet, anggaran, analitik, laporan, dan profil.
- Layout desktop sidebar dan navigasi ponsel dengan pengalaman yang dapat digunakan dari Safari iPhone.
- Komponen UI bersama untuk navigasi, kartu ringkasan, transaksi, form, feedback, dan state kosong/error/loading.
- Route/controller web dengan autentikasi sesi, validasi server, dan pembatasan data per pengguna.
- Build aset frontend melalui Vite ke `public/build`; tidak ada server frontend terpisah untuk produksi.
- Tes feature untuk autentikasi dan kepemilikan data, tes CRUD/domain terkait, serta hasil build frontend.

## Deploy

- Web dan API berjalan dari satu aplikasi Laravel dan domain; aplikasi Flutter tetap memakai endpoint API.
- Build aset dibuat saat proses CI/deploy menggunakan `npm ci` dan `npm run build`, lalu file `public/build` dipublikasikan bersama aplikasi Laravel.
- Server membutuhkan PHP dan ekstensi Laravel yang sesuai, database terkonfigurasi, `APP_KEY`, HTTPS, serta konfigurasi mail/storage bila alur tersebut digunakan.
- Migrasi dijalankan melalui prosedur deploy yang aman. Queue worker hanya diperlukan untuk pekerjaan yang memang dikirim ke queue.
- Konfigurasi secret disediakan melalui environment server, tidak disimpan di repo.

## Verifikasi dan kriteria selesai

- Tes feature membuktikan user dapat mengakses datanya sendiri dan tidak bisa membaca/mengubah data user lain.
- Alur login/logout, CRUD transaksi, dompet, anggaran, filter, analitik, dan unduhan laporan bekerja sesuai fitur yang dirilis.
- Aturan transaksi mempertahankan saldo dan keterkaitan data sesuai backend saat ini.
- API yang digunakan Flutter tetap lulus tes dan kontrak responsnya tidak berubah tanpa rencana migrasi.
- `composer test` lulus dan `npm run build` menghasilkan aset produksi.
- Halaman diuji minimal di desktop Chromium dan Safari iPhone; tidak ada overflow horizontal, aksi penting dapat disentuh/diakses, dan form nyaman saat keyboard virtual terbuka.
- Error validasi, loading, data kosong, dan kegagalan jaringan memiliki tampilan yang jelas.
- Semua route web terproteksi dengan benar dan laporan tidak membocorkan data lintas akun.

## Keputusan yang masih perlu dikonfirmasi saat implementasi

- Pendaftaran akun web sudah dibuka pada tahap 1 mengikuti kemampuan API yang sudah ada; kebijakan pembukaan akun dapat ditinjau kembali sebelum rilis publik.
- Apakah fitur chatbot dan scan struk akan masuk tahap web setelah MVP.
- Alur reset kata sandi melalui email belum menjadi bagian dari tahap 4 dan memerlukan keputusan tentang layanan mail saat akan dirilis.
- Target hosting adalah Arenhost shared hosting dengan domain yang sudah tersedia. Detail domain, akses panel/SSH, document root, dan hasil verifikasi production akan diisi saat pengguna siap melakukan rilis; dukungan mail/storage perlu diperiksa sesuai fitur yang dipakai.

## Referensi

- [Laravel starter kits](https://laravel.com/starter-kits)
- [Laravel frontend dan Inertia](https://laravel.com/framework/docs/frontend)
- [shadcn-vue](https://www.shadcn-vue.com/)
- [Tailwind CSS dengan Laravel dan Vite](https://tailwindcss.com/docs/installation/framework-guides/laravel/vite)
- [Panduan deploy Arenhost](../../guides/deploy-arenhost.md)
- [Verifikasi browser](../../guides/browser-validation.md)

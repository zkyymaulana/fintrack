---
type: Deployment Guide
title: Deploy FinTrack di Arenhost shared hosting
description: Langkah rilis satu aplikasi Laravel untuk web Inertia dan API Flutter di satu domain.
tags: [deploy, arenhost, laravel, inertia, shared-hosting]
status: prepared
---

# Deploy FinTrack di Arenhost shared hosting

Panduan ini disiapkan untuk paket shared hosting Arenhost dengan domain yang sudah ada. Nama domain, akses Terminal/SSH, versi PHP, dan kemampuan mengubah document root perlu diisi sesuai panel milik akun sebelum rilis. Arenhost menyebut dukungan Laravel, SSL, dan akses SSH/Terminal pada paket tertentu, tetapi fitur yang tersedia bergantung pada paket akun. [Informasi Arenhost](https://arenhost.id/business-hosting)

## 1. Periksa lingkungan

- Pilih PHP 8.2 atau lebih baru. Pastikan ekstensi yang dibutuhkan Laravel dan paket aplikasi tersedia, terutama BCMath, cURL, DOM/XML, Fileinfo, GD, Mbstring, OpenSSL, PDO MySQL, dan Zip. Jalankan `composer check-platform-reqs --no-dev` setelah instalasi.
- Siapkan database MySQL/MariaDB beserta user khusus aplikasi. Catat host, port, nama database, dan user pada pengelola rahasia hosting; jangan masukkan kata sandi ke Git.
- Arahkan document root domain/subdomain ke folder `finTrack-be/public`. Simpan kode Laravel, `.env`, `vendor`, dan `storage` di luar document root. Jika panel tidak mengizinkan, minta dukungan Arenhost mengaturnya atau gunakan domain/subdomain yang mendukung document root khusus. [Panduan Laravel tentang document root](https://laravel.com/framework/docs/12.x/deployment#server-configuration)
- Aktifkan sertifikat HTTPS sebelum menguji login dan cookie sesi. Pastikan `storage` dan `bootstrap/cache` dapat ditulis oleh proses PHP. [Persyaratan Laravel](https://laravel.com/framework/docs/12.x/deployment#server-requirements)

## 2. Siapkan artefak aplikasi

Rilis folder `finTrack-be` dari commit yang sudah ditinjau. Di direktori aplikasi pada server, jalankan:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
composer check-platform-reqs --no-dev
```

Build frontend dibuat dari `package-lock.json` dengan Node.js 20.19+ atau 22.12+:

```sh
npm ci
npm run typecheck
npm run build
```

Jika Node.js pada shared hosting belum memenuhi syarat, jalankan tiga perintah itu di lingkungan build/CI yang sesuai lalu unggah `public/build` lengkap dengan `manifest.json` dari hasil build yang sama. Node.js tidak perlu berjalan terus di server produksi. Jangan unggah `node_modules` atau berkas `public/hot`. [Panduan Vite Laravel](https://laravel.com/framework/docs/12.x/vite)

## 3. Atur environment server

Sediakan `.env` hanya di direktori aplikasi server. Contoh kunci yang perlu diisi sesuai lingkungan:

```dotenv
APP_NAME=FinTrack
APP_ENV=production
APP_DEBUG=false
APP_URL=https://DOMAIN_FINTRACK
APP_KEY=KUNCI_APLIKASI_YANG_SUDAH_ADA
DB_CONNECTION=mysql
DB_HOST=HOST_DATABASE
DB_PORT=3306
DB_DATABASE=NAMA_DATABASE
DB_USERNAME=USER_DATABASE
DB_PASSWORD=RAHASIA_DATABASE
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Gunakan `APP_KEY` lama bila ini perpindahan aplikasi yang sudah digunakan; jangan menggantinya saat deploy ulang. Pada instalasi baru, buat kunci sekali melalui `php artisan key:generate` setelah `.env` tersedia. Sesuaikan mail, Firebase, dan Gemini hanya bila fitur terkait digunakan. Web dan API memakai origin/domain Laravel yang sama; jangan set cookie domain lintas host tanpa kebutuhan.

## 4. Migrasi dan optimasi

Jalankan dari direktori aplikasi dengan environment produksi yang sudah lengkap:

```sh
php artisan migrate --force
php artisan db:seed --class=CategorySeeder --force
php artisan optimize
```

`CategorySeeder` menyiapkan pilihan kategori untuk form transaksi. `DatabaseSeeder` kini juga aman untuk production, tetapi gunakan seeder kategori secara eksplisit agar data contoh tidak ikut terpasang. Jangan jalankan `WalletSeeder`, `TransactionSeeder`, atau `BudgetSeeder` pada database pengguna. Jika API lama mengembalikan URL berkas dari disk `public`, jalankan `php artisan storage:link` jika symlink didukung hosting. Foto profil web disajikan melalui route sesi sehingga tidak bergantung pada symlink tersebut.

Saat merilis versi berikutnya, cadangkan database sesuai prosedur hosting, perbarui kode dan artefak build dari versi yang sama, jalankan `composer install`/`php artisan migrate --force` bila perlu, lalu `php artisan optimize`. Jika memakai queue worker di kemudian hari, kelola proses worker dan restart saat rilis; konfigurasi `QUEUE_CONNECTION=sync` di atas tidak memerlukan worker. [Optimasi Laravel](https://laravel.com/framework/docs/12.x/deployment#optimization)

## 5. Pemeriksaan setelah deploy

1. Buka `https://DOMAIN_FINTRACK/up` dan pastikan status sehat.
2. Buka `/login` dari desktop dan Safari iPhone 14; pastikan halaman dan aset CSS/JS termuat dari domain HTTPS yang sama.
3. Masuk/daftar, buat dompet dan transaksi uji, cek dashboard/anggaran/analitik, unduh PDF dan Excel, ubah profil, lalu logout.
4. Periksa route API yang dipakai Flutter dengan token uji dan pastikan kontraknya tetap sama.
5. Pastikan aset di `/build/` termuat, sementara berkas `.env` dan direktori kode Laravel di luar `public` tidak terbuka sebagai konten publik.
6. Jika ada error, cek log Laravel di `storage/logs` melalui panel hosting tanpa menyalin rahasia ke tiket publik.

Status saat ini: panduan siap, deployment Arenhost menunggu detail domain/panel dari pemilik hosting. Tidak ada perubahan yang dikirim ke server produksi oleh tahap ini.

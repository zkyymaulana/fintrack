---
type: Verification Guide
title: Verifikasi browser FinTrack web
description: Hasil smoke test Chromium dan skenario uji Safari iPhone 14 sebelum rilis.
tags: [web, browser, safari, accessibility, verification]
status: in-progress
---

# Verifikasi browser

## Yang sudah diuji otomatis

Skrip [`tests/Browser/smoke.mjs`](../../tests/Browser/smoke.mjs) memakai Chrome headless tanpa dependency npm tambahan. Pada 14 Oktober 2026, skrip lulus pada 30 kombinasi: 10 halaman dengan viewport 320 px, 390 px (emulasi sentuh), dan 1280 px. Pemeriksaan meliputi heading utama, elemen `main`, tidak ada overflow halaman, ukuran target navigasi ponsel, tidak ada exception JavaScript pada alur normal, pendaftaran akun, penyimpanan dompet, form transaksi yang aktif, dan pesan saat permintaan Inertia gagal karena jaringan. Screenshot dashboard ponsel dan analitik desktop diperiksa visual. Pengujian ini memakai mesin Chromium di Windows, bukan Safari iOS.

Untuk mengulang, siapkan server Laravel lokal dengan database uji terpisah dan aset Vite produksi, lalu jalankan:

```sh
FINTRACK_BASE_URL=http://127.0.0.1:8765 node tests/Browser/smoke.mjs
```

Di PowerShell, set variabel lingkungan melalui `$env:FINTRACK_BASE_URL='http://127.0.0.1:8765'` sebelum memanggil `node`. `CHROME_PATH` dapat diisi bila executable Chrome berada di lokasi lain. Skrip membuat akun baru dan dompet pada database server yang dituju; jangan arahkan ke production. Artefak screenshot/hasil dibuat di `storage/app/stage5-browser` secara lokal.

## Uji Safari pada iPhone 14

Pemilik perangkat perlu membuka build yang dapat dijangkau dari iPhone melalui HTTPS. Catat hasil setiap skenario di bawah sebelum menandai tahap rilis selesai:

| Skenario | Pemeriksaan | Hasil |
| --- | --- | --- |
| Masuk/daftar/logout | Keyboard email dan kata sandi nyaman, validasi terlihat, sesi berakhir saat logout. | Belum diuji |
| Navigasi bawah | Lima tab dapat disentuh; Laporan dan Analitik dapat dibuka dari Profil. | Belum diuji |
| Layar 320–390 px | Tidak ada scroll horizontal halaman; kartu dan tombol tidak terpotong saat zoom. | Belum diuji |
| Form keuangan | Buat/ubah dompet, transaksi, transfer, dan anggaran; keyboard virtual tidak menutupi aksi simpan. | Belum diuji |
| Grafik dan tabel | Tren/distribusi terbaca, tabel bisa digulir di dalam kartu. | Belum diuji |
| Laporan | Pilih tanggal, unduh PDF/Excel; lihat umpan balik bila jaringan gagal. | Belum diuji |
| Profil | Ubah nama/email/kata sandi, unggah/hapus foto, kemudian logout. | Belum diuji |
| VoiceOver dan keyboard eksternal | Heading, label, error form, fokus, skip link, dan tabel dapat ditelusuri. | Belum diuji |

Jika ada kegagalan, catat URL halaman, versi iOS, lebar/orientasi layar, langkah reproduksi, dan screenshot tanpa data sensitif. Status tahap 5 tetap belum completed sampai hasil Safari dan rilis production sudah diverifikasi.

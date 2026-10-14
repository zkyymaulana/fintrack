---
type: Business Logic
title: FinTrack web budgets and analytics
description: Anggaran bulanan, realisasi, dan analitik per periode untuk web Laravel/Inertia.
tags: [budgets, analytics, web, charts]
sources:
  - resource: ../../../app/Services/BudgetOverview.php
    title: Budget overview rules
  - resource: ../../../app/Services/FinanceAnalytics.php
    title: Web financial analytics
  - resource: ../../../app/Http/Controllers/Web/BudgetController.php
    title: Web budget operations
  - resource: ../../../app/Http/Controllers/Web/AnalyticsController.php
    title: Web analytics period selection
---

# Anggaran

- Anggaran disimpan per user, kategori pengeluaran, dan `month_year` berformat `MM-YYYY`, mengikuti model dan API yang sudah ada.
- Untuk bulan yang belum memiliki anggaran khusus, web menampilkan batas dari catatan terbaru kategori tersebut seperti endpoint API. Tampilan menandainya sebagai nilai bawaan; membuat anggaran pada bulan pilihan menyimpan catatan baru untuk bulan itu.
- Realisasi kategori biasa menjumlahkan `amount + admin_fee` dari transaksi pengeluaran dalam bulan terpilih. Kategori investasi mengikuti aturan API: transfer yang memakai kategori tersebut atau memiliki kata kunci investasi pada judul/catatan dihitung sebagai realisasi.
- Persentase dan status dihitung dari realisasi terhadap batas: aman di bawah 80%, mendekati batas mulai 80%, dan mencapai atau melewati batas mulai 100%. Sisa anggaran dapat bernilai negatif.
- Ubah dan hapus hanya berlaku pada catatan anggaran milik user yang sedang login. Mengubah batas tidak mengganti nama kategori global atau memindahkan transaksi.

# Analitik

- Mode bulanan menampilkan ringkasan bulan pilihan dan tren enam bulan hingga bulan tersebut. Mode rentang tanggal menerima tanggal awal/akhir dengan maksimal 12 bulan kalender; ringkasan dan setiap titik tren hanya menghitung transaksi dalam tanggal yang dipilih.
- Pemasukan memakai `amount`; pengeluaran memakai `amount + admin_fee`. Transfer antar dompet tidak masuk arus kas pemasukan/pengeluaran. Distribusi pengeluaran dikelompokkan menurut ID kategori agar kategori bernama sama tetap dapat dibedakan.
- Angka dihitung dengan BCMath dua digit desimal dan hanya berasal dari transaksi milik user aktif. Grafik bar dan donut web dibuat dengan CSS/SVG ringan; tabel angka tetap menjadi alternatif yang dapat dibaca pembaca layar.
- Endpoint API analitik mempertahankan bentuk responsnya; pemasukan dengan biaya admin kini mengikuti aturan saldo yang sama, yaitu hanya `amount`.

# Terkait

Lihat [aturan transaksi](financial-transaction-rules.md), [model data](financial-data-model.md), dan [rancangan web](../../plans/active/fintrack-web-ui.md).

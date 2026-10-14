---
type: System Integration
title: FinTrack backend integrations
description: Backend terhubung ke Gemini, Firebase Cloud Messaging, dan generator laporan.
tags: [integrations, gemini, firebase, reports]
sources:
  - resource: ../../../config/services.php
    title: Service credentials configuration
  - resource: ../../../app/Http/Controllers/Api/ChatbotController.php
    title: Gemini chatbot integration
  - resource: ../../../app/Http/Controllers/Api/TransactionController.php
    title: Receipt and voice scanning
  - resource: ../../../app/Http/Controllers/Api/ExportController.php
    title: PDF and Excel exports
  - resource: ../../../app/Http/Controllers/Web/ReportController.php
    title: Web PDF and Excel reports
---

# Gemini

`ChatbotController` mengirim prompt dan data percakapan ke Gemini. `TransactionController` memakai Gemini untuk menafsirkan hasil scan struk dan input audio. API key dan nama model dibaca dari konfigurasi `services.gemini`, yang berasal dari `GEMINI_API_KEY` dan `GEMINI_MODEL`.

# Firebase Cloud Messaging

Pengguna dapat mengirim token FCM melalui endpoint `/api/update-fcm-token`. Kode backend memakai Firebase Messaging untuk mengirim notifikasi. Konfigurasi kredensial Firebase berada di `config/firebase.php`.

# Report exports

`ExportController` menyediakan ekspor laporan PDF dan Excel. Paket backend mencakup DomPDF dan Laravel Excel.

Web Inertia menggunakan `ReportController` untuk memilih rentang tanggal maksimal 12 bulan kalender dan mengunduh PDF atau Excel lewat sesi web. `WebReportData` membatasi transaksi pada user aktif; pemasukan hanya menjumlahkan nominal, pengeluaran mencakup biaya admin, dan transfer tercantum tanpa memengaruhi arus kas. Berkas dibuat saat diminta dan tidak disimpan sebagai entri `reports`. Endpoint API bulanan dan kontraknya tetap terpisah.

---
type: Data Model
title: FinTrack financial data model
description: Model utama mencatat pengguna, kategori, transaksi, dompet, anggaran, laporan, dan notifikasi.
tags: [database, finance, models]
sources:
  - resource: ../../../app/Models/User.php
    title: User relationships
  - resource: ../../../app/Models/Transaction.php
    title: Transaction relationships
  - resource: ../../../database/migrations/2026_05_31_073532_create_transactions_table.php
    title: Initial transaction table
  - resource: ../../../database/migrations/2026_06_24_030703_create_wallets_table.php
    title: Wallet table
  - resource: ../../../database/migrations/2026_05_31_073533_create_budgets_table.php
    title: Budget table
---

# Core entities

- `User` memiliki transaksi, dompet, anggaran, laporan, notifikasi, dan pesan chatbot.
- `Transaction` terhubung ke pengguna, kategori, dompet sumber, dan opsional dompet tujuan.
- `Category` mengelompokkan transaksi dan anggaran menurut tipe pemasukan atau pengeluaran.
- `Wallet` menyimpan nama dan saldo milik pengguna.
- `Budget` menyimpan batas per kategori untuk nilai `month_year`.
- `Report`, `Notification`, dan `ChatMessage` menyimpan laporan yang dibuat, notifikasi, dan riwayat chatbot.

# Transaction fields

Transaksi memiliki jumlah, biaya admin, tipe, tanggal, judul/catatan, kategori, dan dompet. Kolom `to_wallet_id` digunakan untuk tujuan transfer. Bukti struk disimpan sebagai path file; `is_ocr` menandai transaksi hasil scan struk.

# Related concepts

Lihat [financial transaction rules](/concepts/financial-transaction-rules.md) untuk dampak transaksi pada saldo dan [API authentication](/concepts/api-authentication.md) untuk pembatasan akses.

---
type: Business Logic
title: FinTrack transaction and wallet rules
description: Controller transaksi menyesuaikan saldo dompet untuk pemasukan, pengeluaran, dan transfer.
tags: [transactions, wallets, balances]
sources:
  - resource: ../../../app/Http/Controllers/Api/TransactionController.php
    title: Transaction operations
  - resource: ../../../app/Http/Controllers/Api/AnalyticsController.php
    title: Financial summaries
  - resource: ../../../app/Services/WebTransactionService.php
    title: Atomic web transaction balance updates
---

# Wallet effects

- Pemasukan menambah saldo dompet sumber sebesar jumlah transaksi.
- Pengeluaran mengurangi saldo dompet sumber sebesar jumlah ditambah biaya admin.
- Transfer mengurangi dompet sumber sebesar jumlah ditambah biaya admin dan menambah dompet tujuan sebesar jumlah transaksi.
- Update transaksi mengembalikan efek transaksi lama ke dompet lalu menerapkan efek transaksi baru.
- Hapus transaksi mengembalikan efek transaksi ke saldo dompet.
- Untuk web, tambah/ubah/hapus transaksi dilakukan dalam satu transaksi database. Dompet asal dan tujuan milik user dikunci saat saldo berubah. BCMath menjaga dua digit desimal.
- Kategori pemasukan harus bertipe `income`; pengeluaran dan transfer menggunakan kategori `expense`. Dompet asal dan tujuan transfer harus aktif, milik user, dan berbeda.
- Dompet yang masih dipakai sebagai asal atau tujuan transaksi tidak dapat dihapus melalui web maupun API.
- API transfer menyimpan `to_wallet_id` agar riwayat dan pembalikan saldo tujuan dapat dilakukan. Bentuk respons API tetap sama.

# Analytics

Controller analitik mengagregasi transaksi per periode dan kategori. Pemasukan dihitung dari `amount`, pengeluaran dari `amount + admin_fee`, dan transfer tidak masuk arus kas biasa. Sebagian transfer dapat diklasifikasikan sebagai tabungan berdasarkan isi transaksi. Aturan web dijelaskan di [budgets and analytics](budget-analytics.md); detail tambahan API berada pada `AnalyticsController`.

# Related concepts

Lihat [financial data model](financial-data-model.md) untuk struktur transaksi/dompet dan [API authentication](api-authentication.md) untuk konteks pengguna.

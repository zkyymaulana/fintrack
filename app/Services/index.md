# app/Services

Layanan aturan bisnis yang digunakan alur web.

Laporan web menggunakan [WebReportData.php](./WebReportData.php) untuk memuat transaksi dan menghitung ringkasan sesuai akun serta periode.

- [WebTransactionService.php](./WebTransactionService.php) — perubahan saldo atomik ketika transaksi ditambah, diubah, atau dihapus.
- [BudgetOverview.php](./BudgetOverview.php) — realisasi dan status anggaran bulanan dengan nilai bawaan dari periode lain.
- [FinanceAnalytics.php](./FinanceAnalytics.php) — ringkasan, tren, dan distribusi transaksi untuk periode pilihan.

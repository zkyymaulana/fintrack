# app/Http/Controllers/Web

Controller halaman web Inertia yang menggunakan sesi Laravel.

## Isi folder

- [Auth/](./Auth/index.md)
- [DashboardController.php](./DashboardController.php) - ringkasan saldo dan transaksi akun.
- [WalletController.php](./WalletController.php) - CRUD dompet reguler/investasi dan jadwal pengeluaran atau transfer.
- [MotorcycleController.php](./MotorcycleController.php) - data motor, odometer, dan ringkasan riwayat perawatan.
- [MotorcycleMaintenanceItemController.php](./MotorcycleMaintenanceItemController.php) - CRUD jadwal sparepart dan perawatan motor.
- [MotorcycleMaintenanceRecordController.php](./MotorcycleMaintenanceRecordController.php) - pencatatan servis dan pengeluaran terkait.
- [TransactionController.php](./TransactionController.php) - daftar, filter, dan CRUD transaksi web.
- [BudgetController.php](./BudgetController.php) - CRUD anggaran dan realisasi per bulan.
- [AnalyticsController.php](./AnalyticsController.php) - analitik periode bulanan atau rentang tanggal.
- [ReportController.php](./ReportController.php) - pratinjau dan unduhan laporan PDF/Excel sesuai akun dan periode.
- [ProfileController.php](./ProfileController.php) - detail akun, kata sandi, dan avatar web.

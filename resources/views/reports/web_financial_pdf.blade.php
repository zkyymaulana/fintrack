<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan FinTrack</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #172c29; font-size: 10px; }
        h1 { color: #194b40; margin-bottom: 4px; }
        p { margin: 3px 0; }
        .summary { width: 100%; margin: 20px 0; border-collapse: collapse; }
        .summary td { width: 33%; background: #e9f4eb; padding: 12px; border: 4px solid white; }
        .summary strong { display: block; margin-top: 6px; font-size: 15px; }
        table.detail { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .detail th { background: #194b40; color: white; text-align: left; padding: 8px 5px; }
        .detail td { border-bottom: 1px solid #dfe8df; padding: 7px 5px; overflow-wrap: break-word; }
        .number { text-align: right; }
        .muted { color: #657a6d; }
    </style>
</head>
<body>
    <h1>Laporan Keuangan FinTrack</h1>
    <p>{{ $user->name }} · {{ $user->email }}</p>
    <p class="muted">Periode {{ $report['start'] }} sampai {{ $report['end'] }} · {{ $report['count'] }} transaksi</p>
    <table class="summary"><tr>
        <td>Pemasukan<strong>Rp {{ number_format((float) $report['summary']['income'], 2, ',', '.') }}</strong></td>
        <td>Pengeluaran termasuk biaya admin<strong>Rp {{ number_format((float) $report['summary']['expense'], 2, ',', '.') }}</strong></td>
        <td>Arus kas bersih<strong>Rp {{ number_format((float) $report['summary']['net'], 2, ',', '.') }}</strong></td>
    </tr></table>
    <p class="muted">Transfer antar dompet tercantum di rincian, tetapi tidak masuk arus kas pemasukan atau pengeluaran.</p>
    <table class="detail">
        <thead><tr><th style="width: 9%">Tanggal</th><th style="width: 20%">Transaksi</th><th style="width: 9%">Jenis</th><th style="width: 13%">Kategori</th><th style="width: 14%">Dompet</th><th class="number" style="width: 11%">Nominal</th><th class="number" style="width: 10%">Admin</th><th class="number" style="width: 14%">Total</th></tr></thead>
        <tbody>
        @forelse ($report['transactions'] as $transaction)
            <tr>
                <td>{{ $transaction['date'] }}</td><td>{{ $transaction['title'] }}</td>
                <td>{{ match ($transaction['type']) { 'income' => 'Masuk', 'expense' => 'Keluar', default => 'Transfer' } }}</td>
                <td>{{ $transaction['category'] }}</td>
                <td>{{ $transaction['wallet'] }}{{ $transaction['destination'] ? ' → '.$transaction['destination'] : '' }}</td>
                <td class="number">{{ number_format((float) $transaction['amount'], 2, ',', '.') }}</td>
                <td class="number">{{ number_format((float) $transaction['admin_fee'], 2, ',', '.') }}</td>
                <td class="number">{{ number_format((float) $transaction['total'], 2, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="8">Belum ada transaksi pada periode ini.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>

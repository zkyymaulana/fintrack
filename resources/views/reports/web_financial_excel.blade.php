<table>
    <thead>
        <tr><th colspan="8" style="font-weight: bold; font-size: 16px; background-color: #194b40; color: #FFFFFF;">LAPORAN KEUANGAN FINTRACK</th></tr>
        <tr><th colspan="8" data-type="s">{{ $user->name }} ({{ $user->email }}) · {{ $report['start'] }} sampai {{ $report['end'] }}</th></tr>
        <tr><th colspan="8">Transfer antar dompet tidak masuk arus kas pemasukan atau pengeluaran.</th></tr>
        <tr><th>Pemasukan</th><th>Pengeluaran termasuk admin</th><th>Arus kas bersih</th><th>Jumlah transaksi</th></tr>
        <tr><td>{{ $report['summary']['income'] }}</td><td>{{ $report['summary']['expense'] }}</td><td>{{ $report['summary']['net'] }}</td><td>{{ $report['count'] }}</td></tr>
        <tr><th colspan="8"></th></tr>
        <tr style="font-weight: bold; background-color: #e9f4eb;"><th>Tanggal</th><th>Transaksi</th><th>Jenis</th><th>Kategori</th><th>Dompet</th><th>Nominal</th><th>Biaya admin</th><th>Total</th></tr>
    </thead>
    <tbody>
        @forelse ($report['transactions'] as $transaction)
            <tr>
                <td>{{ $transaction['date'] }}</td>
                <td data-type="s">{{ $transaction['title'] }}</td>
                <td>{{ match ($transaction['type']) { 'income' => 'Masuk', 'expense' => 'Keluar', default => 'Transfer' } }}</td>
                <td data-type="s">{{ $transaction['category'] }}</td>
                <td data-type="s">{{ $transaction['wallet'] }}{{ $transaction['destination'] ? ' → '.$transaction['destination'] : '' }}</td>
                <td>{{ $transaction['amount'] }}</td><td>{{ $transaction['admin_fee'] }}</td><td>{{ $transaction['total'] }}</td>
            </tr>
        @empty
            <tr><td colspan="8">Belum ada transaksi pada periode ini.</td></tr>
        @endforelse
    </tbody>
</table>

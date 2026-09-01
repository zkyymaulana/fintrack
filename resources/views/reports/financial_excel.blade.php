<table>
    <thead>
        <!-- Title & Metadata -->
        <tr>
            <th colspan="7" style="font-size: 16px; font-weight: bold; text-align: center; background-color: #2563EB; color: #FFFFFF;">
                LAPORAN KEUANGAN FINTRACK
            </th>
        </tr>
        <tr>
            <th colspan="7" style="text-align: center; color: #4B5563; font-style: italic;">
                Periode: {{ $period }} | Pengguna: {{ $user->name }} ({{ $user->email }}) | Dicetak: {{ $generatedAt }}
            </th>
        </tr>
        <tr><th colspan="7"></th></tr>

        <!-- Ringkasan Finansial -->
        <tr>
            <th colspan="2" style="font-weight: bold; background-color: #10B981; color: #FFFFFF;">Total Pemasukan</th>
            <th colspan="2" style="font-weight: bold; background-color: #EF4444; color: #FFFFFF;">Total Pengeluaran</th>
            <th colspan="2" style="font-weight: bold; background-color: #3B82F6; color: #FFFFFF;">Arus Kas Bersih (Net Flow)</th>
            <th style="font-weight: bold; background-color: #6366F1; color: #FFFFFF;">Savings Rate</th>
        </tr>
        <tr>
            <td colspan="2" style="font-weight: bold; color: #059669;">Rp {{ number_format($totalIncome, 0, ',', '.') }}</td>
            <td colspan="2" style="font-weight: bold; color: #DC2626;">Rp {{ number_format($totalExpense, 0, ',', '.') }}</td>
            <td colspan="2" style="font-weight: bold; color: #2563EB;">{{ $netCashFlow >= 0 ? '+' : '-' }} Rp {{ number_format(abs($netCashFlow), 0, ',', '.') }}</td>
            <td style="font-weight: bold;">{{ $savingsRate }}%</td>
        </tr>
        <tr><th colspan="7"></th></tr>

        <!-- Header Tabel Transaksi -->
        <tr style="background-color: #0F172A; color: #FFFFFF;">
            <th style="font-weight: bold; background-color: #0F172A; color: #FFFFFF; text-align: center;">No</th>
            <th style="font-weight: bold; background-color: #0F172A; color: #FFFFFF; text-align: center;">Tanggal</th>
            <th style="font-weight: bold; background-color: #0F172A; color: #FFFFFF;">Judul Transaksi</th>
            <th style="font-weight: bold; background-color: #0F172A; color: #FFFFFF; text-align: center;">Tipe</th>
            <th style="font-weight: bold; background-color: #0F172A; color: #FFFFFF;">Kategori</th>
            <th style="font-weight: bold; background-color: #0F172A; color: #FFFFFF; text-align: right;">Nominal (Rp)</th>
            <th style="font-weight: bold; background-color: #0F172A; color: #FFFFFF; text-align: right;">Total Biaya (Rp)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($transactions as $index => $tx)
            @php
                $txTotal = (float)($tx->amount + ($tx->admin_fee ?? 0));
            @endphp
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td style="text-align: center;">{{ \Carbon\Carbon::parse($tx->date)->format('d/m/Y') }}</td>
                <td>{{ $tx->title }} {{ $tx->note ? '(' . $tx->note . ')' : '' }}</td>
                <td style="text-align: center; color: {{ $tx->type === 'income' ? '#059669' : '#DC2626' }};">
                    {{ strtoupper($tx->type) }}
                </td>
                <td>{{ $tx->category ? $tx->category->name : '-' }}</td>
                <td style="text-align: right;">{{ $tx->amount }}</td>
                <td style="text-align: right; font-weight: bold; color: {{ $tx->type === 'income' ? '#059669' : '#DC2626' }};">
                    {{ $tx->type === 'income' ? $txTotal : -$txTotal }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align: center; color: #64748B;">Tidak ada data transaksi.</td>
            </tr>
        @endforelse
    </tbody>
    @if($transactions->isNotEmpty())
        <tfoot>
            <tr>
                <th colspan="5" style="text-align: right; font-weight: bold; background-color: #F1F5F9;">TOTAL:</th>
                <th style="text-align: right; font-weight: bold; background-color: #F1F5F9;">{{ $transactions->sum('amount') }}</th>
                <th style="text-align: right; font-weight: bold; background-color: #F1F5F9; color: {{ $netCashFlow >= 0 ? '#059669' : '#DC2626' }};">
                    {{ $netCashFlow }}
                </th>
            </tr>
        </tfoot>
    @endif
</table>


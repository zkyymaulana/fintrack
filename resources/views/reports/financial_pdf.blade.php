<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Keuangan - {{ $period }}</title>
    <style>
        @page {
            margin: 25px 30px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            line-height: 1.4;
            background-color: #ffffff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .brand-title {
            font-size: 22px;
            font-weight: bold;
            color: #1e40af;
            letter-spacing: -0.5px;
            margin: 0;
        }
        .brand-subtitle {
            font-size: 10px;
            color: #64748b;
            margin-top: 2px;
        }
        .report-meta {
            text-align: right;
            font-size: 10px;
            color: #475569;
        }
        .meta-period {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 2px;
        }

        /* Summary Cards */
        .summary-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: separate;
            border-spacing: 8px 0;
        }
        .card {
            padding: 10px 12px;
            border-radius: 6px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        .card-income {
            border-left: 4px solid #10b981;
        }
        .card-expense {
            border-left: 4px solid #ef4444;
        }
        .card-net {
            border-left: 4px solid #2563eb;
        }
        .card-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .card-value {
            font-size: 13px;
            font-weight: bold;
            color: #0f172a;
        }
        .val-income { color: #059669; }
        .val-expense { color: #dc2626; }
        .val-surplus { color: #2563eb; }

        /* Section Heading */
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Transactions Table */
        .tx-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .tx-table th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 7px 8px;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .tx-table th.text-right { text-align: right; }
        .tx-table th.text-center { text-align: center; }

        .tx-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 9.5px;
            vertical-align: middle;
        }
        .tx-table tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .tx-table td.text-right { text-align: right; }
        .tx-table td.text-center { text-align: center; }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-income {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-expense {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .total-row td {
            background-color: #f1f5f9 !important;
            font-weight: bold;
            border-top: 2px solid #cbd5e1;
            border-bottom: 2px solid #0f172a;
            padding: 8px;
        }

        /* Footer */
        .footer {
            margin-top: 25px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 8.5px;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="vertical-align: top;">
                <div class="brand-title">FINTRACK</div>
                <div class="brand-subtitle">Smart Personal & Enterprise Financial Tracker</div>
            </td>
            <td class="report-meta" style="vertical-align: top;">
                <div class="meta-period">LAPORAN KEUANGAN</div>
                <div>Periode: <strong>{{ $period }}</strong></div>
                <div>Pengguna: <strong>{{ $user->name }}</strong> ({{ $user->email }})</div>
                <div>Dicetak: {{ $generatedAt }}</div>
            </td>
        </tr>
    </table>

    <!-- Financial Health Summary -->
    <div class="section-title">Ringkasan Arus Kas (Financial Summary)</div>
    <table class="summary-table" cellpadding="0" cellspacing="0">
        <tr>
            <td width="33%">
                <div class="card card-income">
                    <div class="card-label">Total Pemasukan</div>
                    <div class="card-value val-income">Rp {{ number_format($totalIncome, 0, ',', '.') }}</div>
                </div>
            </td>
            <td width="33%">
                <div class="card card-expense">
                    <div class="card-label">Total Pengeluaran</div>
                    <div class="card-value val-expense">Rp {{ number_format($totalExpense, 0, ',', '.') }}</div>
                </div>
            </td>
            <td width="34%">
                <div class="card card-net">
                    <div class="card-label">Arus Kas Bersih (Net Flow)</div>
                    <div class="card-value val-surplus">
                        {{ $netCashFlow >= 0 ? '+' : '-' }} Rp {{ number_format(abs($netCashFlow), 0, ',', '.') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Health Indicators Bar -->
    <table style="width: 100%; margin-bottom: 20px; font-size: 9.5px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 6px 12px;">
        <tr>
            <td width="50%">
                Savings Rate: <strong>{{ $savingsRate }}%</strong> 
                <span style="color: {{ $savingsRate >= 20 ? '#10b981' : ($savingsRate >= 0 ? '#f59e0b' : '#ef4444') }}; font-weight: bold;">
                    ({{ $savingsRate >= 20 ? 'Sangat Sehat' : ($savingsRate >= 10 ? 'Sehat' : ($savingsRate >= 1 ? 'Rentan' : 'Defisit')) }})
                </span>
            </td>
            <td width="50%" style="text-align: right;">
                Budget Utilization: <strong>{{ $budgetUtilization }}%</strong> 
                (Limit: Rp {{ number_format($totalBudgetLimit, 0, ',', '.') }})
            </td>
        </tr>
    </table>

    <!-- Transaction Details Table -->
    <div class="section-title">Rincian Riwayat Transaksi</div>
    <table class="tx-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th class="text-center" width="4%">No</th>
                <th width="10%">Tanggal</th>
                <th width="28%">Judul Transaksi</th>
                <th class="text-center" width="9%">Tipe</th>
                <th width="18%">Kategori</th>
                <th class="text-right" width="15%">Nominal</th>
                <th class="text-right" width="16%">Total Biaya</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $tx)
                @php
                    $txTotal = (float)($tx->amount + ($tx->admin_fee ?? 0));
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($tx->date)->format('d/m/Y') }}</td>
                    <td>
                        <strong>{{ $tx->title }}</strong>
                        @if($tx->note)
                            <div style="font-size: 8.5px; color: #64748b;">{{ $tx->note }}</div>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $tx->type === 'income' ? 'badge-income' : 'badge-expense' }}">
                            {{ strtoupper($tx->type) }}
                        </span>
                    </td>
                    <td>{{ $tx->category ? $tx->category->name : '-' }}</td>
                    <td class="text-right">Rp {{ number_format($tx->amount, 0, ',', '.') }}</td>
                    <td class="text-right" style="font-weight: bold; color: {{ $tx->type === 'income' ? '#059669' : '#dc2626' }};">
                        {{ $tx->type === 'income' ? '+' : '-' }} Rp {{ number_format($txTotal, 0, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 20px; color: #64748b;">
                        Tidak ada catatan transaksi pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($transactions->isNotEmpty())
            <tfoot>
                <tr class="total-row">
                    <td colspan="5" style="text-align: right; text-transform: uppercase;">Total Akumulasi:</td>
                    <td class="text-right">Rp {{ number_format($transactions->sum('amount'), 0, ',', '.') }}</td>
                    <td class="text-right" style="color: {{ $netCashFlow >= 0 ? '#059669' : '#dc2626' }};">
                        {{ $netCashFlow >= 0 ? '+' : '-' }} Rp {{ number_format(abs($netCashFlow), 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- Footer -->
    <div class="footer">
        Dokumen ini dihasilkan secara otomatis oleh Sistem FinTrack pada {{ $generatedAt }}.<br>
        Laporan keuangan ini bersifat rahasia dan diterbitkan untuk keperluan pencatatan finansial pemilik akun.
    </div>
</body>
</html>


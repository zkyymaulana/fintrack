<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class WebFinancialReportExport implements FromView, ShouldAutoSize, WithTitle
{
    public function __construct(private User $user, private array $report) {}

    public function view(): View
    {
        return view('reports.web_financial_excel', ['user' => $this->user, 'report' => $this->report]);
    }

    public function title(): string
    {
        return 'Laporan Keuangan';
    }
}

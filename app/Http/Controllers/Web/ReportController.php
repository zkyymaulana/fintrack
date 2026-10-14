<?php

namespace App\Http\Controllers\Web;

use App\Exports\WebFinancialReportExport;
use App\Http\Controllers\Controller;
use App\Services\WebReportData;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ReportController extends Controller
{
    public function index(Request $request, WebReportData $reports): Response
    {
        [$start, $end] = $this->period($request, false);
        $report = $reports->build($request->user('web'), $start, $end);

        return Inertia::render('Reports', [
            'start' => $report['start'],
            'end' => $report['end'],
            'summary' => $report['summary'],
            'count' => $report['count'],
            'recentTransactions' => array_slice(array_reverse($report['transactions']), 0, 5),
        ]);
    }

    public function download(Request $request, string $format, WebReportData $reports): HttpResponse
    {
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);
        [$start, $end] = $this->period($request, true);
        $report = $reports->build($request->user('web'), $start, $end);
        $filename = 'Laporan_FinTrack_'.$report['start'].'_'.$report['end'];

        if ($format === 'pdf') {
            return Pdf::loadView('reports.web_financial_pdf', [
                'user' => $request->user('web'),
                'report' => $report,
            ])->setPaper('a4', 'landscape')->download($filename.'.pdf')
                ->header('Cache-Control', 'private, no-store');
        }

        $response = Excel::download(new WebFinancialReportExport($request->user('web'), $report), $filename.'.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    private function period(Request $request, bool $required): array
    {
        $filter = $request->validate([
            'from' => [$required ? 'required' : 'required_with:to', 'date_format:Y-m-d'],
            'to' => [$required ? 'required' : 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $start = isset($filter['from']) ? CarbonImmutable::parse($filter['from']) : CarbonImmutable::now()->startOfMonth();
        $end = isset($filter['to']) ? CarbonImmutable::parse($filter['to']) : CarbonImmutable::now()->endOfMonth();
        if ($end->lessThan($start)) {
            throw ValidationException::withMessages(['to' => 'Tanggal akhir harus sama atau setelah tanggal awal.']);
        }
        $months = ($end->year - $start->year) * 12 + $end->month - $start->month + 1;
        if ($months > 12) {
            throw ValidationException::withMessages(['to' => 'Rentang laporan maksimal 12 bulan kalender.']);
        }

        return [$start, $end];
    }
}

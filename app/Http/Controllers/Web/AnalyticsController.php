<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\FinanceAnalytics;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request, FinanceAnalytics $analytics): Response
    {
        $filter = $request->validate([
            'mode' => ['nullable', Rule::in(['month', 'range'])],
            'month' => ['nullable', 'date_format:Y-m'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $mode = $filter['mode'] ?? 'month';

        if ($mode === 'range') {
            $request->validate(['from' => ['required', 'date_format:Y-m-d'], 'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from']]);
            $start = CarbonImmutable::parse($filter['from']);
            $end = CarbonImmutable::parse($filter['to']);
            $months = ($end->year - $start->year) * 12 + $end->month - $start->month + 1;
            if ($months > 12) {
                throw ValidationException::withMessages(['to' => 'Rentang analitik maksimal 12 bulan kalender.']);
            }
            $trendStart = $start;
        } else {
            $month = isset($filter['month']) ? CarbonImmutable::parse($filter['month'].'-01') : CarbonImmutable::now()->startOfMonth();
            $start = $month->startOfMonth();
            $end = $month->endOfMonth();
            $trendStart = $month->subMonths(5)->startOfMonth();
            $filter['month'] = $month->format('Y-m');
        }

        return Inertia::render('Analytics', [
            'mode' => $mode,
            'filters' => $filter,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            ...$analytics->report($request->user(), $start, $end, $trendStart),
        ]);
    }
}

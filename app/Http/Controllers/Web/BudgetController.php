<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\Category;
use App\Services\BudgetOverview;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function index(Request $request, BudgetOverview $overview): Response
    {
        $month = $this->month($request);

        return Inertia::render('Budgets/Index', [
            'month' => $month->format('Y-m'),
            ...$overview->forMonth($request->user(), $month),
        ]);
    }

    public function create(Request $request): Response
    {
        $month = $this->month($request);
        $data = $request->validate([
            'category_id' => ['nullable', Rule::exists('categories', 'id')->where('type', 'expense')],
        ]);

        return $this->form($month->format('Y-m'), null, $data['category_id'] ?? null);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('type', 'expense')],
            'limit_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999.99'],
        ]);
        $month = CarbonImmutable::parse($data['month'].'-01');
        $request->user()->budgets()->updateOrCreate(
            ['category_id' => $data['category_id'], 'month_year' => $month->format('m-Y')],
            ['limit_amount' => $data['limit_amount']],
        );

        return redirect()->route('budgets.index', ['month' => $data['month']])->with('success', 'Anggaran berhasil disimpan.');
    }

    public function edit(Request $request, string $budget): Response
    {
        $owned = $request->user()->budgets()->with('category:id,name,type')->findOrFail($budget);

        return $this->form($this->budgetMonth($owned), $owned);
    }

    public function update(Request $request, string $budget): RedirectResponse
    {
        $owned = $request->user()->budgets()->findOrFail($budget);
        $data = $request->validate([
            'limit_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999.99'],
        ]);
        $owned->update($data);

        return redirect()->route('budgets.index', ['month' => $this->budgetMonth($owned)])->with('success', 'Anggaran berhasil diperbarui.');
    }

    public function destroy(Request $request, string $budget): RedirectResponse
    {
        $owned = $request->user()->budgets()->findOrFail($budget);
        $month = $this->budgetMonth($owned);
        $owned->delete();

        return redirect()->route('budgets.index', ['month' => $month])->with('success', 'Anggaran bulan tersebut berhasil dihapus.');
    }

    private function form(string $month, ?Budget $budget, ?int $categoryId = null): Response
    {
        return Inertia::render('Budgets/Form', [
            'month' => $month,
            'budget' => $budget ? [
                'id' => $budget->id,
                'category_id' => $budget->category_id,
                'category_name' => $budget->category?->name,
                'limit_amount' => (string) $budget->limit_amount,
            ] : null,
            'categoryId' => $categoryId,
            'categories' => Category::where('type', 'expense')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function month(Request $request): CarbonImmutable
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);

        return isset($data['month']) ? CarbonImmutable::parse($data['month'].'-01') : CarbonImmutable::now()->startOfMonth();
    }

    private function budgetMonth(Budget $budget): string
    {
        if (preg_match('/^(0[1-9]|1[0-2])-([0-9]{4})$/', $budget->month_year, $matches)) {
            return $matches[2].'-'.$matches[1];
        }

        abort(404);
    }
}

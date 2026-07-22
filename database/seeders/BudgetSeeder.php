<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class BudgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'zakymaulana363@gmail.com')->first();
        if (!$user) {
            return;
        }

        $monthYear = '07-2026';

        $budgets = [
            ['category' => 'Wajib', 'limit_amount' => 800000],
            ['category' => 'Gaya Hidup', 'limit_amount' => 300000],
            ['category' => 'Fleksibel', 'limit_amount' => 100000],
            ['category' => 'Lainnya', 'limit_amount' => 100000],
        ];

        foreach ($budgets as $item) {
            $category = Category::where('name', $item['category'])
                ->where('type', 'expense')
                ->first();

            if ($category) {
                Budget::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'category_id' => $category->id,
                        'month_year' => $monthYear,
                    ],
                    [
                        'limit_amount' => $item['limit_amount'],
                    ]
                );
            }
        }
    }
}

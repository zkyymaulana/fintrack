<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Pemasukan
            ['name' => 'Salary', 'icon' => 'payments', 'type' => 'income'],
            ['name' => 'Investment', 'icon' => 'trending_up', 'type' => 'income'],
            
            // Pengeluaran
            ['name' => 'Food & Dining', 'icon' => 'restaurant', 'type' => 'expense'],
            ['name' => 'Transportation', 'icon' => 'directions_car', 'type' => 'expense'],
            ['name' => 'Shopping', 'icon' => 'shopping_bag', 'type' => 'expense'],
            ['name' => 'Housing', 'icon' => 'home', 'type' => 'expense'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}

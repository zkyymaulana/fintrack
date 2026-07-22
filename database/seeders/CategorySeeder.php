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
            ['name' => 'Gaji', 'icon' => 'payments', 'type' => 'income'],
            ['name' => 'Uang Saku', 'icon' => 'account_balance_wallet', 'type' => 'income'],
            ['name' => 'Lainnya', 'icon' => 'add_circle_outline', 'type' => 'income'],
            ['name' => 'Pindah Akun', 'icon' => 'swap_horiz', 'type' => 'income'],
            
            // Pengeluaran
            ['name' => 'Wajib', 'icon' => 'priority_high', 'type' => 'expense'],
            ['name' => 'Gaya Hidup', 'icon' => 'style', 'type' => 'expense'],
            ['name' => 'Fleksibel', 'icon' => 'tune', 'type' => 'expense'],
            ['name' => 'Lainnya', 'icon' => 'more_horiz', 'type' => 'expense'],
            ['name' => 'Pindah Akun', 'icon' => 'swap_horiz', 'type' => 'expense'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name'], 'type' => $category['type']],
                ['icon' => $category['icon']]
            );
        }
    }
}

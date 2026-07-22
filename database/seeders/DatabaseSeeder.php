<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'zakymaulana363@gmail.com'],
            [
                'name' => 'Zaky Maulana',
                'password' => bcrypt('password'),
            ]
        );

        $this->call([
            CategorySeeder::class,
            WalletSeeder::class,
            TransactionSeeder::class,
            BudgetSeeder::class,
        ]);
    }
}

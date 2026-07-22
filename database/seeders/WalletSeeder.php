<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'zakymaulana363@gmail.com')->first() ?? User::factory()->create([
            'name' => 'Zaky Maulana',
            'email' => 'zakymaulana363@gmail.com',
            'password' => bcrypt('password'),
        ]);

        $wallets = [
            ['name' => 'CASH', 'balance' => 135500],
            ['name' => 'Bank Jatim', 'balance' => 2220483],
            ['name' => 'Bank BRI', 'balance' => 1044489],
            ['name' => 'Dana', 'balance' => 0],
            ['name' => 'Saham', 'balance' => 54127039],
            ['name' => 'Bank BSI', 'balance' => 12500],
            ['name' => 'Bank Jago', 'balance' => 115],
        ];

        foreach ($wallets as $wallet) {
            Wallet::firstOrCreate(
                ['user_id' => $user->id, 'name' => $wallet['name']],
                ['balance' => $wallet['balance']]
            );
        }
    }
}

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
        $user = User::first() ?? User::factory()->create([
            'name' => 'Testing User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $wallets = [
            [
                'name' => 'BCA',
                'balance' => 5000000,
            ],
            [
                'name' => 'Coinbase',
                'balance' => 1500000,
            ],
            [
                'name' => 'Tunai',
                'balance' => 300000,
            ],
        ];

        foreach ($wallets as $wallet) {
            Wallet::create([
                'user_id' => $user->id,
                'name'    => $wallet['name'],
                'balance' => $wallet['balance'],
            ]);
        }
    }
}

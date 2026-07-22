<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
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

        // Ambil wallet pengguna berdasarkan nama
        $wallets = Wallet::where('user_id', $user->id)->get()->keyBy('name');

        // Helper untuk mendapatkan category_id
        $getCategoryId = function ($name, $type) {
            $catType = $type === 'transfer' ? 'expense' : $type;
            $cat = Category::where('name', $name)->where('type', $catType)->first()
                ?? Category::where('name', $name)->first()
                ?? Category::first();
            return $cat ? $cat->id : null;
        };

        // Data transaksi sesuai permintaan user
        $rawTransactions = [
            // 01 Jul 2026
            ['date' => '2026-07-01', 'type' => 'income', 'category' => 'Gaji', 'wallet' => 'Bank Jatim', 'amount' => 1983200, 'title' => 'Gaji'],
            ['date' => '2026-07-01', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 9000, 'title' => 'rujak'],
            ['date' => '2026-07-01', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 25000, 'title' => 'martabak+bakso'],
            ['date' => '2026-07-01', 'type' => 'income', 'category' => 'Uang Saku', 'wallet' => 'Bank BRI', 'amount' => 1000000, 'title' => 'Uang Saku'],

            // 02 Jul 2026
            ['date' => '2026-07-02', 'type' => 'transfer', 'category' => 'Pindah Akun', 'wallet' => 'Bank Jatim', 'to_wallet' => 'Bank BRI', 'amount' => 2266800, 'title' => 'Pindah Akun ipo', 'note' => 'ipo'],
            ['date' => '2026-07-02', 'type' => 'transfer', 'category' => 'Pindah Akun', 'wallet' => 'Bank BRI', 'to_wallet' => 'Saham', 'amount' => 2000000, 'title' => 'Pindah Akun ipo', 'note' => 'ipo (Pemasukan Saham Rp 2.005.000)'],

            // 03 Jul 2026
            ['date' => '2026-07-03', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 8000, 'title' => 'bakso'],
            ['date' => '2026-07-03', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'Bank BRI', 'amount' => 160880, 'title' => 'listrik'],

            // 04 Jul 2026
            ['date' => '2026-07-04', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 12000, 'title' => 'telur 1/2, mendingan beli 1'],
            ['date' => '2026-07-04', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 7500, 'title' => 'bumbu nasgor + kerupuk'],
            ['date' => '2026-07-04', 'type' => 'expense', 'category' => 'Lainnya', 'wallet' => 'Bank BRI', 'amount' => 15000, 'title' => 'obeng 1 set'],

            // 05 Jul 2026
            ['date' => '2026-07-05', 'type' => 'income', 'category' => 'Lainnya', 'wallet' => 'CASH', 'amount' => 50000, 'title' => 'bulek han'],
            ['date' => '2026-07-05', 'type' => 'expense', 'category' => 'Lainnya', 'wallet' => 'Bank BRI', 'amount' => 840000, 'title' => 'topup'],

            // 06 Jul 2026
            ['date' => '2026-07-06', 'type' => 'transfer', 'category' => 'Pindah Akun', 'wallet' => 'Bank BRI', 'to_wallet' => 'CASH', 'amount' => 300000, 'title' => 'Pindah Akun BRI ke CASH'],
            ['date' => '2026-07-06', 'type' => 'expense', 'category' => 'Lainnya', 'wallet' => 'CASH', 'amount' => 197000, 'title' => 'ganti kampas ganda, lampu kota'],
            ['date' => '2026-07-06', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 39000, 'title' => 'bensin'],
            ['date' => '2026-07-06', 'type' => 'income', 'category' => 'Lainnya', 'wallet' => 'CASH', 'amount' => 50000, 'title' => 'mbak ida'],
            ['date' => '2026-07-06', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 4000, 'title' => 'isoplus'],
            ['date' => '2026-07-06', 'type' => 'expense', 'category' => 'Gaya Hidup', 'wallet' => 'CASH', 'amount' => 30000, 'title' => 'nasgor'],
            ['date' => '2026-07-06', 'type' => 'expense', 'category' => 'Fleksibel', 'wallet' => 'CASH', 'amount' => 20000, 'title' => 'potong rambut'],

            // 07 Jul 2026
            ['date' => '2026-07-07', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 24000, 'title' => 'nasi gila'],
            ['date' => '2026-07-07', 'type' => 'income', 'category' => 'Lainnya', 'wallet' => 'Dana', 'amount' => 12000, 'title' => 'nasi gila(frq)'],

            // 08 Jul 2026
            ['date' => '2026-07-08', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 8000, 'title' => 'sm'],
            ['date' => '2026-07-08', 'type' => 'expense', 'category' => 'Lainnya', 'wallet' => 'CASH', 'amount' => 66500, 'title' => 'laritta'],
            ['date' => '2026-07-08', 'type' => 'expense', 'category' => 'Gaya Hidup', 'wallet' => 'CASH', 'amount' => 24000, 'title' => 'indomaret(salad)'],
            ['date' => '2026-07-08', 'type' => 'expense', 'category' => 'Gaya Hidup', 'wallet' => 'CASH', 'amount' => 57000, 'title' => 'indomaret'],
            ['date' => '2026-07-08', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'Dana', 'amount' => 15000, 'title' => 'dua tema'],
            ['date' => '2026-07-08', 'type' => 'expense', 'category' => 'Gaya Hidup', 'wallet' => 'Bank BRI', 'amount' => 696000, 'title' => 'topup'],
            ['date' => '2026-07-08', 'type' => 'expense', 'category' => 'Gaya Hidup', 'wallet' => 'CASH', 'amount' => 28000, 'title' => 'pundak'],
            ['date' => '2026-07-08', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 30000, 'title' => 'bensin'],
            ['date' => '2026-07-08', 'type' => 'expense', 'category' => 'Gaya Hidup', 'wallet' => 'CASH', 'amount' => 42000, 'title' => 'mie ayam'],
            ['date' => '2026-07-08', 'type' => 'expense', 'category' => 'Gaya Hidup', 'wallet' => 'CASH', 'amount' => 5000, 'title' => 'parkir'],

            // 09 Jul 2026
            ['date' => '2026-07-09', 'type' => 'transfer', 'category' => 'Pindah Akun', 'wallet' => 'Bank BRI', 'to_wallet' => 'CASH', 'amount' => 100000, 'title' => 'Pindah Akun BRI ke CASH'],

            // 10 Jul 2026
            ['date' => '2026-07-10', 'type' => 'expense', 'category' => 'Lainnya', 'wallet' => 'Bank BRI', 'amount' => 25000, 'title' => 'hikmah'],
            ['date' => '2026-07-10', 'type' => 'income', 'category' => 'Lainnya', 'wallet' => 'Bank BRI', 'amount' => 200000, 'title' => 'jutif'],

            // 11 Jul 2026
            ['date' => '2026-07-11', 'type' => 'expense', 'category' => 'Lainnya', 'wallet' => 'Bank BRI', 'amount' => 252500, 'title' => 'fg mas defan'],
            ['date' => '2026-07-11', 'type' => 'expense', 'category' => 'Lainnya', 'wallet' => 'Bank BRI', 'amount' => 15000, 'title' => 'cetak foto'],
            ['date' => '2026-07-11', 'type' => 'income', 'category' => 'Lainnya', 'wallet' => 'CASH', 'amount' => 150000, 'title' => 'mbak'],

            // 12 Jul 2026
            ['date' => '2026-07-12', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 10000, 'title' => 'andrik'],
            ['date' => '2026-07-12', 'type' => 'expense', 'category' => 'Gaya Hidup', 'wallet' => 'CASH', 'amount' => 32000, 'title' => 'mixue'],
            ['date' => '2026-07-12', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 10000, 'title' => 'andrik'],
            ['date' => '2026-07-12', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 36000, 'title' => 'nasi gila'],
            ['date' => '2026-07-12', 'type' => 'expense', 'category' => 'Lainnya', 'wallet' => 'CASH', 'amount' => 50000, 'title' => 'jas hilmi'],

            // 13 Jul 2026
            ['date' => '2026-07-13', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 8000, 'title' => 'sm'],
            ['date' => '2026-07-13', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'Bank Jatim', 'amount' => 26000, 'title' => 'pisang keju'],
            ['date' => '2026-07-13', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 15000, 'title' => 'dua tema'],

            // 14 Jul 2026
            ['date' => '2026-07-14', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 8000, 'title' => 'pecel'],
            ['date' => '2026-07-14', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 20000, 'title' => 'bensin'],
            ['date' => '2026-07-14', 'type' => 'transfer', 'category' => 'Pindah Akun', 'wallet' => 'Bank Jatim', 'to_wallet' => 'Bank BRI', 'amount' => 400000, 'title' => 'Pindah Akun Bank Jatim ke Bank BRI'],

            // 15 Jul 2026
            ['date' => '2026-07-15', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 20000, 'title' => 'galon'],
            ['date' => '2026-07-15', 'type' => 'expense', 'category' => 'Lainnya', 'wallet' => 'CASH', 'amount' => 11500, 'title' => 'fc+ matrai'],

            // 16 Jul 2026
            ['date' => '2026-07-16', 'type' => 'expense', 'category' => 'Lainnya', 'wallet' => 'CASH', 'amount' => 11000, 'title' => 'matrai'],
            ['date' => '2026-07-16', 'type' => 'transfer', 'category' => 'Pindah Akun', 'wallet' => 'Bank Jatim', 'to_wallet' => 'Bank BRI', 'amount' => 1200000, 'title' => 'Pindah Akun Bank Jatim ke Bank BRI'],
            ['date' => '2026-07-16', 'type' => 'expense', 'category' => 'Lainnya', 'wallet' => 'Bank BRI', 'amount' => 600000, 'title' => 'ptsl'],
            ['date' => '2026-07-16', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 5000, 'title' => 'krupuk'],
            ['date' => '2026-07-16', 'type' => 'expense', 'category' => 'Wajib', 'wallet' => 'CASH', 'amount' => 2500, 'title' => 'bumbu nasgor'],
            ['date' => '2026-07-16', 'type' => 'income', 'category' => 'Lainnya', 'wallet' => 'CASH', 'amount' => 100000, 'title' => 'bude yah'],
        ];

        $defaultWalletId = isset($wallets['CASH']) ? $wallets['CASH']->id : ($wallets->first() ? $wallets->first()->id : null);

        foreach ($rawTransactions as $trx) {
            $categoryId = $getCategoryId($trx['category'], $trx['type']);
            
            $sourceWallet = (isset($trx['wallet']) && isset($wallets[$trx['wallet']]))
                ? $wallets[$trx['wallet']]
                : (isset($wallets['CASH']) ? $wallets['CASH'] : $wallets->first());
            $sourceWalletId = $sourceWallet ? $sourceWallet->id : null;

            $destWallet = isset($trx['to_wallet']) && isset($wallets[$trx['to_wallet']]) ? $wallets[$trx['to_wallet']] : null;
            $destWalletId = $destWallet ? $destWallet->id : null;

            Transaction::create([
                'user_id' => $user->id,
                'category_id' => $categoryId,
                'wallet_id' => $sourceWalletId,
                'to_wallet_id' => $destWalletId,
                'title' => $trx['title'],
                'amount' => $trx['amount'],
                'admin_fee' => 0,
                'type' => $trx['type'],
                'date' => $trx['date'],
                'note' => $trx['note'] ?? $trx['title'],
            ]);
        }
    }
}

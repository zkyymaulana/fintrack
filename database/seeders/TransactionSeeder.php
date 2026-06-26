<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        
        // Ambil dompet berdasarkan nama untuk dipasangkan ke transaksi
        $walletBCA = Wallet::where('name', 'BCA')->first();
        $walletCrypto = Wallet::where('name', 'Coinbase')->first();
        $walletCash = Wallet::where('name', 'Tunai')->first();

        // Asumsi category_id 1 adalah Income, dan category_id 2 adalah Expense
        // (Pastikan tabel categories kamu minimal punya 2 data ID ini)
        
        $transactions = [
            [
                'user_id'     => $user->id,
                'category_id' => 1,
                'wallet_id'   => $walletBCA->id,
                'title'       => 'Bayar Freelance',
                'amount'      => 1250000,
                'admin_fee'   => 0,
                'type'        => 'income',
                'date'        => Carbon::now()->subDays(3),
                'note'        => 'Pembayaran layanan kreatif Saturasa Desain',
            ],
            [
                'user_id'     => $user->id,
                'category_id' => 2,
                'wallet_id'   => $walletBCA->id,
                'title'       => 'Bayar Freelance',
                'amount'      => 450000,
                'admin_fee'   => 2500, // Simulasi transfer antar bank
                'type'        => 'expense',
                'date'        => Carbon::now()->subDays(2),
                'note'        => 'Beli pakan untuk ternak ayam petelur',
            ],
            [
                'user_id'     => $user->id,
                'category_id' => 2,
                'wallet_id'   => $walletBCA->id,
                'title'       => 'Bayar Freelance',
                'amount'      => 1000000,
                'admin_fee'   => 1500, // Biaya topup RDN
                'type'        => 'expense',
                'date'        => Carbon::now()->subDays(1),
                'note'        => 'Topup RDN untuk cicil saham BBRI dan WIKA',
            ],
            [
                'user_id'     => $user->id,
                'category_id' => 1,
                'wallet_id'   => $walletCrypto->id,
                'title'       => 'Bayar Freelance',
                'amount'      => 350000,
                'admin_fee'   => 0,
                'type'        => 'income',
                'date'        => Carbon::now()->subHours(12),
                'note'        => 'Profit take trading harian',
            ],
            [
                'user_id'     => $user->id,
                'category_id' => 2,
                'wallet_id'   => $walletCash->id,
                'title'       => 'Bayar Freelance',
                'amount'      => 50000,
                'admin_fee'   => 0,
                'type'        => 'expense',
                'date'        => Carbon::now(),
                'note'        => 'Makan siang bareng Shofi',
            ],
        ];

        foreach ($transactions as $trx) {
            Transaction::create($trx);
        }
    }
}

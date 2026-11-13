<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table): void {
            $table->string('auto_deduction_type', 16)->default('expense');
            $table->foreignId('auto_deduction_to_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table): void {
            $table->dropForeign(['auto_deduction_to_wallet_id']);
            $table->dropColumn(['auto_deduction_type', 'auto_deduction_to_wallet_id']);
        });
    }
};

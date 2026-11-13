<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table): void {
            $table->boolean('auto_deduction_enabled')->default(false);
            $table->decimal('auto_deduction_amount', 15, 2)->nullable();
            $table->foreignId('auto_deduction_category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('auto_deduction_title')->nullable();
            $table->text('auto_deduction_note')->nullable();
            $table->string('auto_deduction_frequency', 16)->nullable();
            $table->date('auto_deduction_next_run_on')->nullable();
            $table->unsignedTinyInteger('auto_deduction_day_of_month')->nullable();
            $table->date('auto_deduction_last_run_on')->nullable();
            $table->string('auto_deduction_last_status', 32)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table): void {
            $table->dropForeign(['auto_deduction_category_id']);
            $table->dropColumn([
                'auto_deduction_enabled',
                'auto_deduction_amount',
                'auto_deduction_category_id',
                'auto_deduction_title',
                'auto_deduction_note',
                'auto_deduction_frequency',
                'auto_deduction_next_run_on',
                'auto_deduction_day_of_month',
                'auto_deduction_last_run_on',
                'auto_deduction_last_status',
            ]);
        });
    }
};

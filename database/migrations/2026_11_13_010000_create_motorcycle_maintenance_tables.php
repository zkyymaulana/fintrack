<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motorcycles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('license_plate')->nullable();
            $table->unsignedInteger('current_odometer_km')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'name']);
        });

        Schema::create('motorcycle_maintenance_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('motorcycle_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('kind', 16);
            $table->unsignedInteger('interval_km');
            $table->unsignedInteger('baseline_odometer_km')->default(0);
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['motorcycle_id', 'is_active']);
        });

        Schema::create('motorcycle_odometer_readings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('motorcycle_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('odometer_km');
            $table->timestamp('recorded_at');
            $table->string('source', 16)->default('manual');
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['motorcycle_id', 'recorded_at']);
        });

        Schema::create('motorcycle_maintenance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('motorcycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_item_id')->nullable()->constrained('motorcycle_maintenance_items')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->unique()->constrained('transactions')->nullOnDelete();
            $table->string('title');
            $table->unsignedInteger('odometer_km');
            $table->date('performed_on');
            $table->decimal('cost', 15, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['motorcycle_id', 'performed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motorcycle_maintenance_records');
        Schema::dropIfExists('motorcycle_odometer_readings');
        Schema::dropIfExists('motorcycle_maintenance_items');
        Schema::dropIfExists('motorcycles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_cancellations', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 32)->unique();
            $table->foreignId('purchase_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('sale_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('stock_adjustment_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->text('reason');
            $table->foreignId('cancelled_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at');
            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE transaction_cancellations ADD CONSTRAINT transaction_cancellations_one_source CHECK (((purchase_id IS NOT NULL) + (sale_id IS NOT NULL) + (stock_adjustment_id IS NOT NULL)) = 1)');
        }

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('transaction_cancellation_id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['transaction_cancellation_id']);
            $table->dropColumn('transaction_cancellation_id');
        });

        Schema::dropIfExists('transaction_cancellations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transaction_number_sequences')) {
            Schema::create('transaction_number_sequences', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_type', 12);
                $table->date('sequence_date');
                $table->unsignedBigInteger('last_number')->default(0);
            });
        }

        if (! Schema::hasIndex('transaction_number_sequences', 'tns_type_date_unique')) {
            Schema::table('transaction_number_sequences', function (Blueprint $table) {
                $table->unique(['transaction_type', 'sequence_date'], 'tns_type_date_unique');
            });
        }

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 32)->unique();
            $table->uuid('submission_key')->unique();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->date('business_date');
            $table->text('reason');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at');
            $table->string('status', 16)->default('confirmed');
            $table->timestamps();
            $table->index(['business_date', 'status']);
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('direction', 12);
            $table->decimal('quantity', 15, 3);
            $table->string('unit', 16);
            $table->timestamps();
            $table->unique(['stock_adjustment_id', 'product_id']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('stock_adjustment_item_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('reverses_movement_id')->nullable()->unique()->constrained('inventory_movements')->restrictOnDelete();
            $table->string('movement_type', 16);
            $table->decimal('quantity', 15, 3);
            $table->decimal('balance_after', 15, 3);
            $table->date('business_date');
            $table->timestamp('confirmed_at');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['product_id', 'business_date', 'confirmed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('transaction_number_sequences');
    }
};

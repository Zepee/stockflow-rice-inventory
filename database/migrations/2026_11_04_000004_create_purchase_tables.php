<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 32)->unique();
            $table->uuid('submission_key')->unique();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->date('business_date');
            $table->unsignedBigInteger('total_value')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at');
            $table->string('status', 16)->default('confirmed');
            $table->timestamps();
            $table->index(['business_date', 'status']);
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('default_purchase_price');
            $table->unsignedBigInteger('purchase_price');
            $table->text('override_reason')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->string('unit', 16);
            $table->unsignedBigInteger('line_subtotal');
            $table->timestamps();
            $table->unique(['purchase_id', 'product_id']);
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('purchase_item_id')->nullable()->unique()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['purchase_item_id']);
            $table->dropUnique(['purchase_item_id']);
            $table->dropColumn('purchase_item_id');
        });

        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
    }
};

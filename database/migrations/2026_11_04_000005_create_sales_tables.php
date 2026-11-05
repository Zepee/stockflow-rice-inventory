<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->text('notes')->nullable();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 32)->unique();
            $table->uuid('submission_key')->unique();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->date('business_date');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('total_value')->default(0);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('confirmed_at');
            $table->string('status', 16)->default('confirmed');
            $table->timestamps();
            $table->index(['business_date', 'status']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('configured_selling_price');
            $table->unsignedBigInteger('selling_price');
            $table->text('override_reason')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->string('unit', 16);
            $table->unsignedBigInteger('line_subtotal');
            $table->timestamps();
            $table->unique(['sale_id', 'product_id']);
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('sale_item_id')->nullable()->unique()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['sale_item_id']);
            $table->dropUnique(['sale_item_id']);
            $table->dropColumn('sale_item_id');
        });

        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
};

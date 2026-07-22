<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_pos_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('marketplace_sellers')->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->decimal('total', 12, 4);
            $table->string('payment_method')->default('cash');
            $table->string('customer_name')->nullable();
            $table->timestamps();
        });

        Schema::create('marketplace_pos_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_sale_id')->constrained('marketplace_pos_sales')->cascadeOnDelete();
            $table->unsignedBigInteger('seller_product_id');
            $table->foreign('seller_product_id')->references('id')->on('marketplace_seller_products')->cascadeOnDelete();
            $table->string('product_name');
            $table->decimal('price', 12, 4);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 12, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_pos_sale_items');
        Schema::dropIfExists('marketplace_pos_sales');
    }
};

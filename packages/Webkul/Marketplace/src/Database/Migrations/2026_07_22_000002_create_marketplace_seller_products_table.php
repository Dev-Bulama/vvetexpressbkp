<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_seller_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('marketplace_sellers')->cascadeOnDelete();
            $table->unsignedInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->decimal('price', 12, 4);
            $table->unsignedInteger('quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['seller_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_seller_products');
    }
};

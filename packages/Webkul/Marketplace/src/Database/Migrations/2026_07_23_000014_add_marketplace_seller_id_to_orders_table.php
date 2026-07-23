<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The single vendor that fulfils this order - the authoritative record of
 * "one buyer, one complete vendor, one order". Nullable because orders
 * placed before this marketplace existed (or non-marketplace channels)
 * have no vendor to assign.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('marketplace_seller_id')->nullable()->after('cart_id');

            $table->foreign('marketplace_seller_id')
                ->references('id')
                ->on('marketplace_sellers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['marketplace_seller_id']);
            $table->dropColumn('marketplace_seller_id');
        });
    }
};

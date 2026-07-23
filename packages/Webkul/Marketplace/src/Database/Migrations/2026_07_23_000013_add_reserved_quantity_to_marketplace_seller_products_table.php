<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distinguishes on-hand stock (quantity, set by the seller) from stock
 * already committed to a placed order (reserved_quantity) so eligibility
 * checks can compute true available stock as quantity - reserved_quantity,
 * without changing what "quantity" already means everywhere it's used
 * today (seller/admin UI, seeders, existing offer queries).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_seller_products', function (Blueprint $table) {
            $table->unsignedInteger('reserved_quantity')->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_seller_products', function (Blueprint $table) {
            $table->dropColumn('reserved_quantity');
        });
    }
};

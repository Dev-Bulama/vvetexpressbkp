<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per moment the eligibility engine finds zero vendors that can
 * fulfil a customer's complete cart. Drives which vendors get restock
 * reminders and lets the admin see where the catalogue is failing
 * customers geographically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_failed_cart_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('customer_id')->nullable();
            $table->string('guest_session_id')->nullable();
            $table->decimal('customer_latitude', 10, 8)->nullable();
            $table->decimal('customer_longitude', 11, 8)->nullable();
            $table->json('cart_snapshot');
            $table->decimal('cart_value', 12, 4)->default(0);
            $table->json('vendors_evaluated');
            $table->unsignedBigInteger('nearest_vendor_id')->nullable();
            $table->unsignedBigInteger('nearest_almost_eligible_vendor_id')->nullable();
            $table->string('customer_action')->nullable();
            $table->boolean('cart_saved')->default(false);
            $table->boolean('items_removed')->default(false);
            $table->boolean('checkout_abandoned')->default(false);
            $table->timestamps();

            $table->index('customer_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_failed_cart_matches');
    }
};

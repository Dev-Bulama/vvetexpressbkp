<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * History of every catalogue-completeness reminder sent to a vendor -
 * powers the admin's reminder-history view and the cooldown check that
 * prevents duplicate reminders for the same unresolved issue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_vendor_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id');
            $table->unsignedInteger('sent_by_admin_id')->nullable();
            $table->string('type')->default('automated');
            $table->string('channel')->default('email');
            $table->decimal('coverage_percent_at_send', 5, 2)->nullable();
            $table->unsignedInteger('missing_products_count')->default(0);
            $table->json('product_ids')->nullable();
            $table->string('delivery_status')->default('sent');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('follow_up_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->foreign('seller_id')->references('id')->on('marketplace_sellers')->cascadeOnDelete();
            $table->index(['seller_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_vendor_reminders');
    }
};

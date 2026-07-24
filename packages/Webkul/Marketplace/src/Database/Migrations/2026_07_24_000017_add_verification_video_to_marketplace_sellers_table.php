<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_sellers', function (Blueprint $table) {
            $table->string('verification_video_path')->nullable()->after('longitude');
            $table->timestamp('verification_video_recorded_at')->nullable()->after('verification_video_path');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_sellers', function (Blueprint $table) {
            $table->dropColumn(['verification_video_path', 'verification_video_recorded_at']);
        });
    }
};

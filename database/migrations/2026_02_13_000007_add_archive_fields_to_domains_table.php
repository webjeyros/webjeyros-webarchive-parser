<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->integer('first_captured')->nullable()->after('archived_url');
            $table->integer('last_captured')->nullable()->after('first_captured');
            $table->integer('capture_count')->nullable()->default(0)->after('last_captured');
            $table->integer('webpage_count')->nullable()->default(0)->after('capture_count');
            $table->integer('image_count')->nullable()->default(0)->after('webpage_count');
            $table->integer('video_count')->nullable()->default(0)->after('image_count');
            $table->integer('audio_count')->nullable()->default(0)->after('video_count');
        });
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn([
                'first_captured',
                'last_captured',
                'capture_count',
                'webpage_count',
                'image_count',
                'video_count',
                'audio_count',
            ]);
        });
    }
};

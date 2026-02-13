<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->unique()->constrained('domains')->cascadeOnDelete();
            $table->integer('da')->nullable()->index();
            $table->integer('pa')->nullable()->index();
            $table->bigInteger('alexa_rank')->nullable();
            $table->bigInteger('semrush_rank')->nullable();
            $table->bigInteger('backlinks_count')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_metrics');
    }
};

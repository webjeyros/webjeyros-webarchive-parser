<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('keyword_id')->constrained('keywords')->cascadeOnDelete();
            $table->string('domain', 255)->index();
            $table->enum('status', ['new', 'checking', 'available', 'occupied', 'dead', 'in_work'])->default('new')->index();
            $table->boolean('available')->default(false)->index();
            $table->integer('http_status')->nullable();
            $table->string('title')->nullable();
            $table->text('snippet')->nullable();
            $table->string('archived_url')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'domain']);
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};

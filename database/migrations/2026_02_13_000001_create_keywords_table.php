<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('keyword', 255)->index();
            $table->enum('status', ['pending', 'parsing', 'parsed', 'error'])->default('pending');
            $table->integer('parsed_count')->default(0);
            $table->timestamp('last_parsed_at')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'keyword']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keywords');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();
            $table->string('title', 500);
            $table->text('summary')->nullable();
            $table->string('source_url', 1000);
            $table->string('source_name', 100);
            $table->string('image_url', 1000)->nullable();
            $table->string('category', 50)->default('fuel'); // fuel, crisis, general
            $table->string('hash', 64)->unique(); // prevent duplicates
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['category', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
};

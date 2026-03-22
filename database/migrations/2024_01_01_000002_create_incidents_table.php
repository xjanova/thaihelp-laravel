<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('category', ['accident', 'flood', 'roadblock', 'checkpoint', 'construction', 'other']);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->double('latitude');
            $table->double('longitude');
            $table->string('image_url', 500)->nullable();
            $table->integer('upvotes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'expires_at']);
            $table->index(['latitude', 'longitude']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};

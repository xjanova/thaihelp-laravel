<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('achievements')) Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('icon', 50);
            $table->timestamp('earned_at');
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });

        if (!Schema::hasTable('daily_challenges')) Schema::create('daily_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('description');
            $table->string('target_type', 50); // reports, confirmations, stations, distance
            $table->integer('target_count');
            $table->integer('reward_stars');
            $table->date('date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['date', 'is_active']);
        });

        if (!Schema::hasTable('user_challenges')) Schema::create('user_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('challenge_id')->constrained('daily_challenges')->cascadeOnDelete();
            $table->integer('progress')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'challenge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_challenges');
        Schema::dropIfExists('daily_challenges');
        Schema::dropIfExists('achievements');
    }
};

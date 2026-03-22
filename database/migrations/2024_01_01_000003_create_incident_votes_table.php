<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_votes', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->string('user_ip', 45);
            $table->timestamps();

            $table->unique(['incident_id', 'user_ip']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_votes');
    }
};

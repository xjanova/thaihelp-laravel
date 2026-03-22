<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_reports', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();
            $table->foreignId('report_id')->constrained('station_reports')->cascadeOnDelete();
            $table->enum('fuel_type', [
                'gasohol95', 'gasohol91', 'e20', 'e85',
                'diesel', 'diesel_b7', 'premium_diesel',
                'ngv', 'lpg',
            ]);
            $table->enum('status', ['available', 'low', 'empty', 'unknown'])->default('unknown');
            $table->decimal('price', 6, 2)->nullable();
            $table->timestamps();

            $table->index('report_id');
            $table->index('fuel_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_reports');
    }
};

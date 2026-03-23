<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fuel_prices')) return;
        Schema::create('fuel_prices', function (Blueprint $table) {
            $table->id();
            $table->string('brand', 50);
            $table->string('fuel_type', 50);
            $table->decimal('price', 8, 2);
            $table->date('date');
            $table->timestamps();
            $table->unique(['brand', 'fuel_type', 'date']);
            $table->index(['fuel_type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_prices');
    }
};

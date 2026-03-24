<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('station_reports', function (Blueprint $table) {
            $table->index(['latitude', 'longitude'], 'station_reports_lat_lng_index');
        });
    }

    public function down(): void
    {
        Schema::table('station_reports', function (Blueprint $table) {
            $table->dropIndex('station_reports_lat_lng_index');
        });
    }
};

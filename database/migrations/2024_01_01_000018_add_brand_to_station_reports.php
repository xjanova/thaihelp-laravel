<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('station_reports', 'brand')) {
            Schema::table('station_reports', function (Blueprint $table) {
                $table->string('brand', 50)->nullable()->after('station_name');
            });
        }
    }

    public function down(): void
    {
        Schema::table('station_reports', function (Blueprint $table) {
            $table->dropColumn('brand');
        });
    }
};

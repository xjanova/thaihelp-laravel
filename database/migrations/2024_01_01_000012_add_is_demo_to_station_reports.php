<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('station_reports', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('confirmed_ips');
            $table->index('is_demo');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('station_reports', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });
    }
};

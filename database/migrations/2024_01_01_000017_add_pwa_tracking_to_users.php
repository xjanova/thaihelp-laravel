<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('pwa_installed')->default(false)->after('total_confirmations');
            $table->timestamp('pwa_installed_at')->nullable()->after('pwa_installed');
            $table->string('device_type', 20)->nullable()->after('pwa_installed_at'); // ios, android, desktop
            $table->timestamp('last_active_at')->nullable()->after('device_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pwa_installed', 'pwa_installed_at', 'device_type', 'last_active_at']);
        });
    }
};

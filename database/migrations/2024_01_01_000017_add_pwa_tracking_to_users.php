<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $cols = Schema::getColumnListing('users');
        Schema::table('users', function (Blueprint $table) use ($cols) {
            if (!in_array('pwa_installed', $cols)) $table->boolean('pwa_installed')->default(false)->after('total_confirmations');
            if (!in_array('pwa_installed_at', $cols)) $table->timestamp('pwa_installed_at')->nullable()->after('total_confirmations');
            if (!in_array('device_type', $cols)) $table->string('device_type', 20)->nullable()->after('total_confirmations');
            if (!in_array('last_active_at', $cols)) $table->timestamp('last_active_at')->nullable()->after('total_confirmations');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pwa_installed', 'pwa_installed_at', 'device_type', 'last_active_at']);
        });
    }
};

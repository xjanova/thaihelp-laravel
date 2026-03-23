<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            // New categories support (expand enum → varchar)
            $table->string('category', 50)->change();

            // Severity & status
            $table->string('severity', 20)->default('medium')->after('description');
            $table->string('status', 20)->default('active')->after('severity');

            // Location details
            $table->string('location_name', 500)->nullable()->after('longitude');
            $table->string('road_name', 255)->nullable()->after('location_name');

            // Rich media
            $table->string('video_url', 500)->nullable()->after('photos');

            // Incident details
            $table->timestamp('incident_at')->nullable()->after('video_url');
            $table->tinyInteger('affected_lanes')->nullable()->after('incident_at');
            $table->boolean('has_injuries')->default(false)->after('affected_lanes');
            $table->boolean('emergency_notified')->default(false)->after('has_injuries');

            // Reporter info
            $table->string('reporter_ip', 45)->nullable()->after('emergency_notified');
            $table->string('report_source', 20)->default('app')->after('reporter_ip');

            // Confirmation system
            $table->unsignedInteger('confirmation_count')->default(0)->after('upvotes');

            // Resolution
            $table->timestamp('resolved_at')->nullable()->after('expires_at');

            // Indexes
            $table->index(['status', 'is_active']);
            $table->index(['category', 'status']);
            $table->index(['severity', 'is_active']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn([
                'severity', 'status', 'location_name', 'road_name',
                'video_url', 'incident_at', 'affected_lanes',
                'has_injuries', 'emergency_notified', 'reporter_ip',
                'report_source', 'confirmation_count', 'resolved_at',
            ]);
        });
    }
};

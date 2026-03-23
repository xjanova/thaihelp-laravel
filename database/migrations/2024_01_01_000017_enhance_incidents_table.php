<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $cols = Schema::getColumnListing('incidents');

        // Convert category from enum to varchar to support new categories
        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE incidents MODIFY category VARCHAR(50) NOT NULL DEFAULT 'other'");
        } catch (\Exception $e) {
            // Already varchar or doesn't exist — skip
        }

        Schema::table('incidents', function (Blueprint $table) use ($cols) {
            if (!in_array('severity', $cols)) $table->string('severity', 20)->default('medium')->after('description');
            if (!in_array('status', $cols)) $table->string('status', 20)->default('active')->after('description');
            if (!in_array('location_name', $cols)) $table->string('location_name', 500)->nullable()->after('longitude');
            if (!in_array('road_name', $cols)) $table->string('road_name', 255)->nullable()->after('longitude');
            if (!in_array('video_url', $cols)) $table->string('video_url', 500)->nullable()->after('image_url');
            if (!in_array('incident_at', $cols)) $table->timestamp('incident_at')->nullable()->after('image_url');
            if (!in_array('affected_lanes', $cols)) $table->tinyInteger('affected_lanes')->nullable()->after('image_url');
            if (!in_array('has_injuries', $cols)) $table->boolean('has_injuries')->default(false)->after('image_url');
            if (!in_array('emergency_notified', $cols)) $table->boolean('emergency_notified')->default(false)->after('image_url');
            if (!in_array('reporter_ip', $cols)) $table->string('reporter_ip', 45)->nullable()->after('upvotes');
            if (!in_array('report_source', $cols)) $table->string('report_source', 20)->default('app')->after('upvotes');
            if (!in_array('confirmation_count', $cols)) $table->unsignedInteger('confirmation_count')->default(0)->after('upvotes');
            if (!in_array('is_danger_zone', $cols)) $table->boolean('is_danger_zone')->default(false)->after('is_active');
            if (!in_array('danger_radius_km', $cols)) $table->double('danger_radius_km')->default(0.5)->after('is_active');
            if (!in_array('resolved_at', $cols)) $table->timestamp('resolved_at')->nullable()->after('expires_at');
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

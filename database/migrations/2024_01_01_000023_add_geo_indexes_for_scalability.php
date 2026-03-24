<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add composite indexes for lat/lng bounding box queries.
 * Critical for 10,000+ concurrent users — without these,
 * every chat request triggers full table scans.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Incidents: lat/lng + status + created_at for active incident lookups
        if (Schema::hasTable('incidents')) {
            Schema::table('incidents', function (Blueprint $table) {
                $table->index(['latitude', 'longitude'], 'incidents_lat_lng_index');
                $table->index(['status', 'latitude', 'longitude'], 'incidents_status_geo_index');
            });
        }

        // Hospital reports: lat/lng + created_at for recent reports
        if (Schema::hasTable('hospital_reports')) {
            Schema::table('hospital_reports', function (Blueprint $table) {
                $table->index(['latitude', 'longitude'], 'hospital_reports_lat_lng_index');
            });
        }

        // Station reports: add compound index with created_at for time-filtered queries
        if (Schema::hasTable('station_reports')) {
            Schema::table('station_reports', function (Blueprint $table) {
                $table->index(['latitude', 'longitude', 'created_at'], 'station_reports_geo_time_index');
            });
        }

        // Users: last_active_at for heartbeat queries
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('last_active_at', 'users_last_active_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('incidents')) {
            Schema::table('incidents', function (Blueprint $table) {
                $table->dropIndex('incidents_lat_lng_index');
                $table->dropIndex('incidents_status_geo_index');
            });
        }
        if (Schema::hasTable('hospital_reports')) {
            Schema::table('hospital_reports', function (Blueprint $table) {
                $table->dropIndex('hospital_reports_lat_lng_index');
            });
        }
        if (Schema::hasTable('station_reports')) {
            Schema::table('station_reports', function (Blueprint $table) {
                $table->dropIndex('station_reports_geo_time_index');
            });
        }
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_last_active_index');
            });
        }
    }
};

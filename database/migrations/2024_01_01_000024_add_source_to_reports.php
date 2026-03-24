<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add 'source' field to track who created the report:
 * - 'user' = ผู้ใช้รายงานเอง (default)
 * - 'ai_ying' = น้องหญิง AI รับแจ้งแล้วบันทึกให้
 * - 'government' = ข้อมูลจากหน่วยงานราชการ
 * - 'admin' = Admin สร้างจากหลังบ้าน
 */
return new class extends Migration
{
    public function up(): void
    {
        // Incidents already has 'report_source' — skip
        // Station reports needs 'source' field
        if (Schema::hasTable('station_reports')) {
            Schema::table('station_reports', function (Blueprint $table) {
                if (!Schema::hasColumn('station_reports', 'source')) {
                    $table->string('source', 20)->default('user')->after('is_verified');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('station_reports') && Schema::hasColumn('station_reports', 'source')) {
            Schema::table('station_reports', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }
    }
};

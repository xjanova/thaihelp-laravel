<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('breaking_news')) return;
        Schema::create('breaking_news', function (Blueprint $table) {
            $table->id();
            $table->string('title', 500);
            $table->text('content'); // Written by น้องหญิง AI
            $table->string('category', 50);
            $table->double('latitude');
            $table->double('longitude');
            $table->string('location_name', 255)->nullable();
            $table->json('image_urls')->nullable(); // Array of image URLs from reporters
            $table->json('source_incident_ids'); // Which incidents triggered this
            $table->integer('reporter_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'created_at']);
            $table->index(['latitude', 'longitude']);
        });

        // Add photo upload support to incidents
        if (!Schema::hasColumn('incidents', 'photos')) {
            Schema::table('incidents', function (Blueprint $table) {
                $table->json('photos')->nullable()->after('image_url');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('breaking_news');
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropColumn('photos');
        });
    }
};

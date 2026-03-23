<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('hospital_reports')) {
            Schema::create('hospital_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('hospital_name', 255);
                $table->string('hospital_type', 50)->default('general'); // general, community, private, clinic
                $table->string('google_place_id', 255)->nullable();
                $table->double('latitude');
                $table->double('longitude');
                $table->string('address', 500)->nullable();
                $table->string('phone', 50)->nullable();
                $table->integer('total_beds')->nullable();
                $table->integer('available_beds')->nullable();
                $table->integer('icu_beds')->nullable();
                $table->integer('icu_available')->nullable();
                $table->string('er_status', 20)->default('unknown'); // open, busy, full, closed
                $table->text('note')->nullable();
                $table->string('reporter_ip', 45)->nullable();
                $table->boolean('is_demo')->default(false);
                $table->boolean('is_verified')->default(false);
                $table->integer('confirmation_count')->default(1);
                $table->timestamps();
                $table->index(['latitude', 'longitude']);
                $table->index(['er_status', 'created_at']);
                $table->index('google_place_id');
            });
        }
    }
    public function down(): void {
        Schema::dropIfExists('hospital_reports');
    }
};

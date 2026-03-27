<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Per-user memories — what users teach น้องหญิง
        Schema::create('ying_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 64)->nullable()->index();
            $table->string('category', 50)->index(); // preference, fact, correction, nickname, location, vehicle
            $table->string('key', 100)->index();
            $table->text('value');
            $table->text('source_message')->nullable();
            $table->string('status', 20)->default('active');
            $table->boolean('admin_approved')->default(true);
            $table->unsignedInteger('use_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'category', 'status']);
        });

        // Training data — conversations for fine-tuning
        Schema::create('ying_training_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('system_prompt')->nullable();
            $table->text('user_message');
            $table->text('assistant_message');
            $table->json('context_data')->nullable();
            $table->string('category', 50)->nullable()->index();
            $table->unsignedTinyInteger('quality_score')->default(0);
            $table->string('status', 20)->default('pending');
            $table->text('admin_notes')->nullable();
            $table->string('exported_to', 100)->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'quality_score']);
        });

        // User behavior patterns
        Schema::create('ying_user_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 64)->nullable()->index();
            $table->string('pattern_type', 50)->index();
            $table->string('pattern_key', 100)->index();
            $table->json('pattern_data');
            $table->unsignedInteger('occurrence_count')->default(1);
            $table->float('confidence', 3, 2)->default(0.5);
            $table->timestamps();
            $table->index(['user_id', 'pattern_type']);
        });

        // Learning config
        Schema::create('ying_learning_config', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value');
            $table->string('type', 20)->default('string');
            $table->string('group', 50)->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Seed defaults
        DB::table('ying_learning_config')->insert([
            ['key' => 'learning_enabled', 'value' => 'true', 'type' => 'bool', 'group' => 'general', 'description' => 'เปิด/ปิดระบบเรียนรู้', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'auto_collect_training', 'value' => 'true', 'type' => 'bool', 'group' => 'training', 'description' => 'เก็บข้อมูลเทรนอัตโนมัติ', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'memory_enabled', 'value' => 'true', 'type' => 'bool', 'group' => 'memory', 'description' => 'เปิด/ปิดความจำผู้ใช้', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'memory_max_per_user', 'value' => '50', 'type' => 'int', 'group' => 'memory', 'description' => 'ความจำสูงสุดต่อผู้ใช้', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'training_min_quality', 'value' => '3', 'type' => 'int', 'group' => 'training', 'description' => 'คะแนนขั้นต่ำสำหรับ export', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'huggingface_repo', 'value' => '', 'type' => 'string', 'group' => 'huggingface', 'description' => 'HuggingFace dataset repo ID', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'huggingface_token', 'value' => '', 'type' => 'string', 'group' => 'huggingface', 'description' => 'HuggingFace API token', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'behavior_tracking', 'value' => 'true', 'type' => 'bool', 'group' => 'patterns', 'description' => 'ติดตามพฤติกรรมผู้ใช้', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ying_learning_config');
        Schema::dropIfExists('ying_user_patterns');
        Schema::dropIfExists('ying_training_data');
        Schema::dropIfExists('ying_memories');
    }
};

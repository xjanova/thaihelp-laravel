<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ying_training_jobs', function (Blueprint $table) {
            $table->id();
            $table->enum('platform', ['colab', 'kaggle', 'huggingface_spaces', 'local']);
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->string('base_model');
            $table->string('dataset_version');
            $table->string('adapter_repo')->nullable(); // repo ที่เก็บ adapter หลังเทรนเสร็จ
            $table->json('training_config'); // hyperparameters ที่ใช้เทรน
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metrics')->nullable(); // training loss, eval metrics
            $table->timestamps();

            $table->index(['platform', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ying_training_jobs');
    }
};

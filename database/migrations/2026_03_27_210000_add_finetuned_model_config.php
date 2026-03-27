<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $configs = [
            ['key' => 'finetuned_model_repo', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'inference_endpoint', 'value' => '', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'use_finetuned_model', 'value' => 'false', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($configs as $config) {
            DB::table('ying_learning_config')->updateOrInsert(
                ['key' => $config['key']],
                $config
            );
        }
    }

    public function down(): void
    {
        DB::table('ying_learning_config')
            ->whereIn('key', ['finetuned_model_repo', 'inference_endpoint', 'use_finetuned_model'])
            ->delete();
    }
};

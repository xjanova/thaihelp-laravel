<?php

namespace App\Console\Commands;

use App\Models\YingTrainingData;
use App\Services\YingTrainingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class YingTrainingScheduler extends Command
{
    protected $signature = 'ying:train
        {--export : ส่งออกข้อมูลเป็น JSONL แล้ว push ขึ้น HuggingFace}
        {--status : แสดงสถิติข้อมูลเทรนและ job ที่รัน}
        {--colab : สร้างลิงก์ Google Colab พร้อมคำแนะนำ}
        {--base-model=unsloth/Qwen2.5-1.5B-Instruct-bnb-4bit : โมเดลพื้นฐานสำหรับ fine-tune}
        {--min-pairs=100 : จำนวนคู่สนทนาขั้นต่ำที่ต้องมี}';

    protected $description = 'จัดการระบบเทรน น้องหญิง AI — export ข้อมูล, สร้าง Colab notebook, ติดตามสถานะ';

    /** แพลตฟอร์มฟรีที่ใช้เทรน (round-robin) */
    private const PLATFORMS = ['colab', 'kaggle', 'huggingface_spaces'];

    /** ค่า hyperparameters เริ่มต้น */
    private const DEFAULT_CONFIG = [
        'max_seq_length' => 2048,
        'lora_r' => 16,
        'lora_alpha' => 16,
        'epochs' => 3,
        'batch_size' => 2,
        'gradient_accumulation_steps' => 4,
        'learning_rate' => 2e-4,
        'warmup_steps' => 5,
        'optimizer' => 'adamw_8bit',
    ];

    public function handle(YingTrainingService $service): int
    {
        // แต่ละ sub-command ทำงานแยกกัน
        if ($this->option('status')) {
            return $this->showStatus($service);
        }

        if ($this->option('export')) {
            return $this->exportData($service);
        }

        if ($this->option('colab')) {
            return $this->generateColabLink();
        }

        // ไม่มี flag — รัน full pipeline
        return $this->runFullPipeline($service);
    }

    /**
     * แสดงสถิติข้อมูลเทรนและ training jobs
     */
    private function showStatus(YingTrainingService $service): int
    {
        $this->info('=== สถิติข้อมูลเทรน น้องหญิง ===');
        $this->newLine();

        // สถิติข้อมูลจาก YingTrainingService
        $stats = $service->getStats();

        $this->table(
            ['รายการ', 'จำนวน'],
            [
                ['ข้อมูลทั้งหมด', $stats['total']],
                ['รอตรวจสอบ (pending)', $stats['pending']],
                ['อนุมัติแล้ว (approved)', $stats['approved']],
                ['ส่งออกแล้ว (exported)', $stats['exported']],
                ['ปฏิเสธ (rejected)', $stats['rejected']],
                ['คะแนนเฉลี่ย', $stats['avg_quality']],
            ]
        );

        // สถิติแยกตามหมวดหมู่
        if (!empty($stats['by_category'])) {
            $this->info('แยกตามหมวดหมู่:');
            $categoryRows = [];
            foreach ($stats['by_category'] as $category => $count) {
                $categoryRows[] = [$category, $count];
            }
            $this->table(['หมวดหมู่', 'จำนวน'], $categoryRows);
        }

        // สถิติ training jobs
        $jobs = DB::table('ying_training_jobs')
            ->select('platform', 'status', DB::raw('count(*) as count'))
            ->groupBy('platform', 'status')
            ->get();

        if ($jobs->isNotEmpty()) {
            $this->newLine();
            $this->info('=== Training Jobs ===');
            $jobRows = $jobs->map(fn ($j) => [$j->platform, $j->status, $j->count])->toArray();
            $this->table(['แพลตฟอร์ม', 'สถานะ', 'จำนวน'], $jobRows);

            // แสดง job ล่าสุด
            $latest = DB::table('ying_training_jobs')->orderByDesc('created_at')->first();
            if ($latest) {
                $this->newLine();
                $this->info("Job ล่าสุด: #{$latest->id} [{$latest->platform}] — {$latest->status}");
                if ($latest->error_message) {
                    $this->error("  Error: {$latest->error_message}");
                }
            }
        } else {
            $this->newLine();
            $this->comment('ยังไม่มี training jobs');
        }

        return self::SUCCESS;
    }

    /**
     * ส่งออกข้อมูลเป็น JSONL แล้ว push ขึ้น HuggingFace
     */
    private function exportData(YingTrainingService $service): int
    {
        $minPairs = (int) $this->option('min-pairs');

        // ตรวจสอบจำนวนข้อมูลที่พร้อม export
        $exportableCount = YingTrainingData::exportable()->count();

        if ($exportableCount < $minPairs) {
            $this->error("ข้อมูลไม่เพียงพอ: มี {$exportableCount} คู่ (ต้องการขั้นต่ำ {$minPairs} คู่)");
            $this->comment('ลองลดค่า --min-pairs หรืออนุมัติข้อมูลเพิ่มเติมใน admin panel');
            return self::FAILURE;
        }

        $this->info("พบข้อมูลพร้อม export: {$exportableCount} คู่");

        // Export เป็น JSONL
        $this->info('กำลัง export เป็น JSONL...');
        $filePath = $service->exportJsonl();
        $this->info("  บันทึกไฟล์: {$filePath}");

        // Push ขึ้น HuggingFace
        $this->info('กำลัง push ขึ้น HuggingFace Hub...');
        $result = $service->pushToHuggingFace();

        if ($result['success']) {
            $this->info("  สำเร็จ! ส่งข้อมูล {$result['exported_count']} คู่ ไปยัง {$result['repo']}");
            return self::SUCCESS;
        }

        $this->error("  Push ล้มเหลว: {$result['error']}");
        return self::FAILURE;
    }

    /**
     * สร้างลิงก์ Google Colab พร้อม pre-filled parameters
     */
    private function generateColabLink(): int
    {
        $baseModel = $this->option('base-model');
        $hfRepo = DB::table('ying_learning_config')->where('key', 'huggingface_repo')->value('value');

        if (!$hfRepo) {
            $this->error('ยังไม่ได้ตั้งค่า huggingface_repo ใน ying_learning_config');
            return self::FAILURE;
        }

        // ตรวจสอบว่ามี notebook template หรือไม่
        $notebookPath = storage_path('app/training/ying_finetune_colab.ipynb');
        $hasNotebook = file_exists($notebookPath);

        // สร้าง Colab URL พร้อม parameters
        // ใช้ GitHub Gist หรือ direct open format
        $params = http_build_query([
            'dataset' => $hfRepo,
            'base_model' => $baseModel,
            'max_seq_length' => self::DEFAULT_CONFIG['max_seq_length'],
            'lora_r' => self::DEFAULT_CONFIG['lora_r'],
            'epochs' => self::DEFAULT_CONFIG['epochs'],
            'batch_size' => self::DEFAULT_CONFIG['batch_size'],
            'lr' => self::DEFAULT_CONFIG['learning_rate'],
        ]);

        $this->newLine();
        $this->info('=== Google Colab Training Setup ===');
        $this->newLine();

        if ($hasNotebook) {
            $this->info("Notebook template: {$notebookPath}");
            $this->comment('อัปโหลด notebook นี้ไปยัง Google Colab แล้วรันตามขั้นตอน');
        }

        $this->newLine();
        $this->info('คำแนะนำ:');
        $this->line('  1. เปิด Google Colab: https://colab.research.google.com/');
        $this->line('  2. เลือก Runtime > Change runtime type > T4 GPU');
        $this->line('  3. รันคำสั่งต่อไปนี้ใน cell แรก:');
        $this->newLine();

        // แสดง code snippet สำหรับ Colab
        $installCode = '!pip install unsloth datasets trl peft';
        $this->line("  <comment>{$installCode}</comment>");
        $this->newLine();
        $this->line('  4. โหลด dataset และเริ่มเทรน:');
        $this->newLine();

        $trainSnippet = <<<PYTHON
from unsloth import FastLanguageModel
from datasets import load_dataset

model, tokenizer = FastLanguageModel.from_pretrained(
    model_name="{$baseModel}",
    max_seq_length={$this->getDefaultConfig()['max_seq_length']},
    load_in_4bit=True,
)

model = FastLanguageModel.get_peft_model(
    model,
    r={$this->getDefaultConfig()['lora_r']},
    lora_alpha={$this->getDefaultConfig()['lora_alpha']},
    target_modules=["q_proj","k_proj","v_proj","o_proj","gate_proj","up_proj","down_proj"],
)

dataset = load_dataset("{$hfRepo}", split="train")
PYTHON;

        // แสดงทีละบรรทัด
        foreach (explode("\n", $trainSnippet) as $line) {
            $this->line("  <comment>{$line}</comment>");
        }

        $this->newLine();
        $this->line("  5. หลังเทรนเสร็จ push adapter ขึ้น HuggingFace:");
        $this->line("  <comment>model.push_to_hub_merged(\"{$hfRepo}-adapter\", tokenizer)</comment>");
        $this->newLine();

        $this->info("Parameters: {$params}");

        return self::SUCCESS;
    }

    /**
     * รัน full pipeline: ตรวจสอบ > export > สร้าง job > แสดงคำแนะนำ
     */
    private function runFullPipeline(YingTrainingService $service): int
    {
        $minPairs = (int) $this->option('min-pairs');
        $baseModel = $this->option('base-model');

        $this->info('=== น้องหญิง Training Pipeline ===');
        $this->newLine();

        // ขั้นที่ 1: ตรวจสอบข้อมูล
        $this->info('[1/4] ตรวจสอบข้อมูลเทรน...');
        $exportableCount = YingTrainingData::exportable()->count();

        if ($exportableCount < $minPairs) {
            $this->error("ข้อมูลไม่เพียงพอ: มี {$exportableCount} คู่ (ต้องการขั้นต่ำ {$minPairs} คู่)");
            $this->comment('รอให้มีข้อมูลอนุมัติเพิ่ม หรือใช้ --min-pairs เพื่อปรับค่าขั้นต่ำ');
            return self::FAILURE;
        }

        $this->info("  พบข้อมูลพร้อมเทรน: {$exportableCount} คู่");

        // ขั้นที่ 2: Export ข้อมูล
        $this->info('[2/4] ส่งออกข้อมูล JSONL + push HuggingFace...');
        $result = $service->pushToHuggingFace();

        if (!$result['success']) {
            $this->error("  Export ล้มเหลว: {$result['error']}");
            return self::FAILURE;
        }

        $this->info("  สำเร็จ! ส่ง {$result['exported_count']} คู่ ไปยัง {$result['repo']}");
        $datasetVersion = now()->format('Ymd_His');

        // ขั้นที่ 3: เลือกแพลตฟอร์ม (round-robin จาก job ล่าสุด)
        $this->info('[3/4] เลือกแพลตฟอร์มเทรน...');
        $platform = $this->selectNextPlatform();
        $this->info("  เลือก: {$platform}");

        // ขั้นที่ 4: สร้าง training job
        $this->info('[4/4] สร้าง training job...');

        $jobId = DB::table('ying_training_jobs')->insertGetId([
            'platform' => $platform,
            'status' => 'pending',
            'base_model' => $baseModel,
            'dataset_version' => $datasetVersion,
            'adapter_repo' => null,
            'training_config' => json_encode(self::DEFAULT_CONFIG),
            'started_at' => null,
            'completed_at' => null,
            'error_message' => null,
            'metrics' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info("  สร้าง Job #{$jobId} สำเร็จ");

        Log::info('Ying training job created', [
            'job_id' => $jobId,
            'platform' => $platform,
            'base_model' => $baseModel,
            'dataset_version' => $datasetVersion,
            'exported_count' => $result['exported_count'],
        ]);

        // แสดงขั้นตอนถัดไป
        $this->newLine();
        $this->info('=== ขั้นตอนถัดไป ===');

        if ($platform === 'colab') {
            $this->line('  รัน: php artisan ying:train --colab');
            $this->line('  เพื่อดูคำแนะนำเปิด Google Colab');
        } elseif ($platform === 'kaggle') {
            $this->line('  1. เปิด https://www.kaggle.com/code');
            $this->line('  2. สร้าง Notebook ใหม่ + เปิด GPU T4x2');
            $this->line("  3. โหลด dataset จาก {$result['repo']}");
        } else {
            $this->line("  1. สร้าง Space ใหม่บน HuggingFace (Training)");
            $this->line("  2. ใช้ dataset: {$result['repo']}");
        }

        $this->newLine();
        $this->comment("หลังเทรนเสร็จ อัปเดต job ด้วย:");
        $this->line("  UPDATE ying_training_jobs SET status='completed', completed_at=NOW() WHERE id={$jobId}");

        return self::SUCCESS;
    }

    /**
     * เลือกแพลตฟอร์มถัดไปแบบ round-robin
     */
    private function selectNextPlatform(): string
    {
        $lastJob = DB::table('ying_training_jobs')
            ->orderByDesc('created_at')
            ->first();

        if (!$lastJob) {
            return self::PLATFORMS[0]; // เริ่มจาก colab
        }

        $lastIndex = array_search($lastJob->platform, self::PLATFORMS);

        if ($lastIndex === false) {
            return self::PLATFORMS[0];
        }

        // วนไปแพลตฟอร์มถัดไป
        $nextIndex = ($lastIndex + 1) % count(self::PLATFORMS);

        return self::PLATFORMS[$nextIndex];
    }

    private function getDefaultConfig(): array
    {
        return self::DEFAULT_CONFIG;
    }
}

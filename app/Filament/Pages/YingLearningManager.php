<?php

namespace App\Filament\Pages;

use App\Models\YingMemory;
use App\Models\YingTrainingData;
use App\Models\YingUserPattern;
use App\Services\HuggingFaceInferenceService;
use App\Services\YingTrainingService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Illuminate\Support\Facades\DB;

class YingLearningManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'AI น้องหญิง';
    protected static ?string $navigationLabel = 'ระบบเรียนรู้';
    protected static ?string $title = 'ระบบเรียนรู้ น้องหญิง';
    protected static ?string $slug = 'ying-learning';
    protected static ?int $navigationSort = 50;

    protected static string $view = 'filament.pages.ying-learning-manager';

    public ?string $activeTab = 'training';
    public array $stats = [];
    public array $configs = [];

    // Training data review
    public array $pendingData = [];
    public array $approvedData = [];

    // Memories
    public array $memories = [];

    // Patterns
    public array $patterns = [];

    // Config form
    public bool $learningEnabled = true;
    public bool $autoCollect = true;
    public bool $memoryEnabled = true;
    public bool $behaviorTracking = true;
    public int $memoryMaxPerUser = 50;
    public int $trainingMinQuality = 3;
    public string $huggingfaceRepo = '';
    public string $huggingfaceToken = '';

    // Deploy config
    public string $finetunedModelRepo = '';
    public string $inferenceEndpoint = '';
    public bool $useFinetunedModel = false;
    public array $modelStatus = [];
    public array $trainingJobs = [];

    public function mount(): void
    {
        $this->loadData();
        $this->loadConfig();
    }

    public function loadData(): void
    {
        $trainingService = app(YingTrainingService::class);
        $this->stats = $trainingService->getStats();

        $this->pendingData = YingTrainingData::pending()
            ->latest()
            ->limit(50)
            ->get()
            ->toArray();

        $this->approvedData = YingTrainingData::approved()
            ->latest()
            ->limit(50)
            ->get()
            ->toArray();

        $this->memories = YingMemory::with('user')
            ->latest()
            ->limit(100)
            ->get()
            ->toArray();

        $this->patterns = YingUserPattern::with('user')
            ->orderByDesc('confidence')
            ->limit(100)
            ->get()
            ->toArray();
    }

    public function loadConfig(): void
    {
        $configs = DB::table('ying_learning_config')->pluck('value', 'key')->toArray();
        $this->learningEnabled = ($configs['learning_enabled'] ?? 'true') === 'true';
        $this->autoCollect = ($configs['auto_collect_training'] ?? 'true') === 'true';
        $this->memoryEnabled = ($configs['memory_enabled'] ?? 'true') === 'true';
        $this->behaviorTracking = ($configs['behavior_tracking'] ?? 'true') === 'true';
        $this->memoryMaxPerUser = (int) ($configs['memory_max_per_user'] ?? 50);
        $this->trainingMinQuality = (int) ($configs['training_min_quality'] ?? 3);
        $this->huggingfaceRepo = $configs['huggingface_repo'] ?? '';
        $encToken = $configs['huggingface_token'] ?? '';
        try {
            $this->huggingfaceToken = $encToken ? decrypt($encToken) : '';
        } catch (\Exception $e) {
            $this->huggingfaceToken = $encToken; // fallback for legacy plain text
        }

        // Deploy config
        $this->finetunedModelRepo = $configs['finetuned_model_repo'] ?? '';
        $this->inferenceEndpoint = $configs['inference_endpoint'] ?? '';
        $this->useFinetunedModel = ($configs['use_finetuned_model'] ?? 'false') === 'true';

        // Training jobs
        $this->trainingJobs = DB::table('ying_training_jobs')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->toArray();
    }

    public function saveConfig(): void
    {
        $settings = [
            'learning_enabled' => $this->learningEnabled ? 'true' : 'false',
            'auto_collect_training' => $this->autoCollect ? 'true' : 'false',
            'memory_enabled' => $this->memoryEnabled ? 'true' : 'false',
            'behavior_tracking' => $this->behaviorTracking ? 'true' : 'false',
            'memory_max_per_user' => (string) $this->memoryMaxPerUser,
            'training_min_quality' => (string) $this->trainingMinQuality,
            'huggingface_repo' => $this->huggingfaceRepo,
            'huggingface_token' => $this->huggingfaceToken ? encrypt($this->huggingfaceToken) : '',
            'finetuned_model_repo' => $this->finetunedModelRepo,
            'inference_endpoint' => $this->inferenceEndpoint,
            'use_finetuned_model' => $this->useFinetunedModel ? 'true' : 'false',
        ];

        foreach ($settings as $key => $value) {
            DB::table('ying_learning_config')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now()]);
        }

        Notification::make()->title('บันทึกการตั้งค่าแล้ว')->success()->send();
    }

    public function approveTraining(int $id, int $quality = 4): void
    {
        YingTrainingData::where('id', $id)->update([
            'status' => 'approved',
            'quality_score' => $quality,
        ]);
        $this->loadData();
        Notification::make()->title('อนุมัติแล้ว')->success()->send();
    }

    public function rejectTraining(int $id): void
    {
        YingTrainingData::where('id', $id)->update(['status' => 'rejected']);
        $this->loadData();
        Notification::make()->title('ปฏิเสธแล้ว')->warning()->send();
    }

    public function bulkApprove(): void
    {
        $count = YingTrainingData::pending()->update([
            'status' => 'approved',
            'quality_score' => 3,
        ]);
        $this->loadData();
        Notification::make()->title("อนุมัติ {$count} รายการ")->success()->send();
    }

    public function exportJsonl(): void
    {
        $service = app(YingTrainingService::class);
        $path = $service->exportJsonl($this->trainingMinQuality);
        $this->loadData();
        Notification::make()->title("Export แล้ว: {$path}")->success()->send();
    }

    public function pushToHuggingFace(): void
    {
        $service = app(YingTrainingService::class);
        $result = $service->pushToHuggingFace();

        if ($result['success']) {
            Notification::make()
                ->title("Push สำเร็จ! {$result['exported_count']} รายการ → {$result['repo']}")
                ->success()->send();
        } else {
            Notification::make()
                ->title("Push ไม่สำเร็จ: {$result['error']}")
                ->danger()->send();
        }
        $this->loadData();
    }

    public function deleteMemory(int $id): void
    {
        YingMemory::destroy($id);
        $this->loadData();
        Notification::make()->title('ลบความจำแล้ว')->success()->send();
    }

    public function toggleMemoryApproval(int $id): void
    {
        $memory = YingMemory::find($id);
        if ($memory) {
            $memory->update(['admin_approved' => !$memory->admin_approved]);
        }
        $this->loadData();
    }

    /**
     * ตรวจสอบสถานะโมเดลที่ fine-tune แล้ว
     */
    public function checkModelStatus(): void
    {
        $service = app(HuggingFaceInferenceService::class);
        $this->modelStatus = $service->checkModelStatus();

        $status = $this->modelStatus['status'] ?? 'unknown';
        $msg = $this->modelStatus['message'] ?? '';

        if ($status === 'ready') {
            Notification::make()->title("✅ {$msg}")->success()->send();
        } else {
            Notification::make()->title("⚠️ {$msg}")->warning()->send();
        }
    }

    /**
     * ทดสอบ inference กับโมเดลที่ fine-tune แล้ว
     */
    public function testFinetunedModel(): void
    {
        $service = app(HuggingFaceInferenceService::class);

        if (!$service->isAvailable()) {
            Notification::make()->title('ยังไม่ได้ตั้งค่าโมเดล fine-tune')->danger()->send();
            return;
        }

        $result = $service->testInference();

        if ($result['success']) {
            Notification::make()
                ->title("✅ โมเดลตอบได้ ({$result['latency_ms']}ms)")
                ->body(mb_substr($result['reply'], 0, 200))
                ->success()->send();
        } else {
            Notification::make()
                ->title("❌ {$result['message']}")
                ->danger()->send();
        }
    }

    /**
     * อัปเดตสถานะ training job
     */
    public function updateJobStatus(int $jobId, string $status): void
    {
        $update = ['status' => $status, 'updated_at' => now()];

        if ($status === 'running') {
            $update['started_at'] = now();
        } elseif (in_array($status, ['completed', 'failed'])) {
            $update['completed_at'] = now();
        }

        DB::table('ying_training_jobs')->where('id', $jobId)->update($update);
        $this->loadConfig();

        Notification::make()->title("Job #{$jobId} → {$status}")->success()->send();
    }
}

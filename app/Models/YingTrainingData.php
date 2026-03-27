<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YingTrainingData extends Model
{
    protected $table = 'ying_training_data';

    protected $fillable = [
        'user_id', 'system_prompt', 'user_message', 'assistant_message',
        'context_data', 'category', 'quality_score', 'status',
        'admin_notes', 'exported_to', 'exported_at',
    ];

    protected $casts = [
        'context_data' => 'array',
        'exported_at' => 'datetime',
    ];

    const STATUSES = ['pending', 'approved', 'rejected', 'exported'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeExportable($query, int $minQuality = 3)
    {
        return $query->where('status', 'approved')->where('quality_score', '>=', $minQuality);
    }

    /**
     * Convert to HuggingFace chat format for fine-tuning.
     */
    public function toTrainingFormat(): array
    {
        return [
            'messages' => [
                ['role' => 'system', 'content' => $this->system_prompt ?? ''],
                ['role' => 'user', 'content' => $this->user_message],
                ['role' => 'assistant', 'content' => $this->assistant_message],
            ],
            'category' => $this->category,
            'quality' => $this->quality_score,
        ];
    }
}

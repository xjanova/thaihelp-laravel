<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Achievement extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'description',
        'icon',
        'earned_at',
    ];

    protected function casts(): array
    {
        return [
            'earned_at' => 'datetime',
        ];
    }

    const BADGES = [
        'first_report' => ['name' => 'รายงานแรก', 'icon' => '🌟', 'description' => 'รายงานเหตุการณ์ครั้งแรก'],
        'reporter_10' => ['name' => 'นักรายงาน', 'icon' => '📢', 'description' => 'รายงาน 10 ครั้ง'],
        'reporter_50' => ['name' => 'นักข่าวชุมชน', 'icon' => '📰', 'description' => 'รายงาน 50 ครั้ง'],
        'reporter_100' => ['name' => 'นักข่าวระดับชาติ', 'icon' => '🏆', 'description' => 'รายงาน 100 ครั้ง'],
        'confirmer_10' => ['name' => 'ผู้ยืนยัน', 'icon' => '✅', 'description' => 'ยืนยันเหตุการณ์ 10 ครั้ง'],
        'confirmer_50' => ['name' => 'ผู้พิทักษ์ชุมชน', 'icon' => '🛡️', 'description' => 'ยืนยัน 50 ครั้ง'],
        'helper_fuel' => ['name' => 'ผู้ช่วยเติมน้ำมัน', 'icon' => '⛽', 'description' => 'รายงานสถานะปั๊ม 10 ครั้ง'],
        'early_bird' => ['name' => 'คนแรกที่แจ้ง', 'icon' => '🐤', 'description' => 'เป็นคนแรกที่แจ้งเหตุ 5 ครั้ง'],
        'streak_7' => ['name' => '7 วันต่อเนื่อง', 'icon' => '🔥', 'description' => 'ใช้งานติดต่อกัน 7 วัน'],
        'streak_30' => ['name' => '30 วันต่อเนื่อง', 'icon' => '💎', 'description' => 'ใช้งานติดต่อกัน 30 วัน'],
        'pwa_installed' => ['name' => 'ติดตั้งแอป', 'icon' => '📱', 'description' => 'ติดตั้ง ThaiHelp ลงเครื่อง'],
        'voice_reporter' => ['name' => 'นักพูด', 'icon' => '🎤', 'description' => 'รายงานด้วยเสียง 5 ครั้ง'],
    ];

    const STAR_LEVELS = [
        0 => ['name' => 'สมาชิกใหม่', 'stars' => 0, 'icon' => '⭐', 'min_score' => 0],
        1 => ['name' => 'ผู้ช่วยชุมชน', 'stars' => 1, 'icon' => '⭐', 'min_score' => 10],
        2 => ['name' => 'นักรายงาน', 'stars' => 2, 'icon' => '⭐⭐', 'min_score' => 50],
        3 => ['name' => 'ผู้พิทักษ์', 'stars' => 3, 'icon' => '⭐⭐⭐', 'min_score' => 150],
        4 => ['name' => 'ฮีโร่ชุมชน', 'stars' => 4, 'icon' => '⭐⭐⭐⭐', 'min_score' => 500],
        5 => ['name' => 'ตำนาน ThaiHelp', 'stars' => 5, 'icon' => '⭐⭐⭐⭐⭐', 'min_score' => 1000],
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

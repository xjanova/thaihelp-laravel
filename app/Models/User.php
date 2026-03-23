<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'nickname',
        'avatar_url',
        'provider',
        'provider_id',
        'is_admin',
        'reputation_score',
        'total_reports',
        'total_confirmations',
        'pwa_installed',
        'pwa_installed_at',
        'device_type',
        'last_active_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'pwa_installed' => 'boolean',
            'reputation_score' => 'integer',
            'total_reports' => 'integer',
            'total_confirmations' => 'integer',
            'pwa_installed_at' => 'datetime',
            'last_active_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function stationReports(): HasMany
    {
        return $this->hasMany(StationReport::class);
    }

    public function adminLogs(): HasMany
    {
        return $this->hasMany(AdminLog::class);
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(Achievement::class);
    }

    public function addReputation(int $points): void
    {
        $this->increment('reputation_score', $points);
    }

    public function incrementReports(): void
    {
        $this->increment('total_reports');
        $this->addReputation(5);
    }

    public function incrementConfirmations(): void
    {
        $this->increment('total_confirmations');
        $this->addReputation(2);
    }

    public function getStarLevel(): array
    {
        $score = $this->reputation_score ?? 0;
        if ($score >= 500) return ['level' => 5, 'stars' => '⭐⭐⭐⭐⭐', 'title' => 'ฮีโร่ชุมชน'];
        if ($score >= 101) return ['level' => 4, 'stars' => '⭐⭐⭐⭐', 'title' => 'ผู้ช่วยเหลือดีเด่น'];
        if ($score >= 51) return ['level' => 3, 'stars' => '⭐⭐⭐', 'title' => 'นักรายงานตัวยง'];
        if ($score >= 11) return ['level' => 2, 'stars' => '⭐⭐', 'title' => 'สมาชิกกระตือรือร้น'];
        return ['level' => 1, 'stars' => '⭐', 'title' => 'สมาชิกใหม่'];
    }
}

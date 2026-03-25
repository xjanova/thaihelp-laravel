<?php

namespace App\Services;

use App\Models\BreakingNews;
use App\Models\Incident;
use Illuminate\Support\Facades\Log;

class BreakingNewsService
{
    private const MIN_REPORTERS = 3;
    private const NEARBY_RADIUS_KM = 2; // Same area = within 2km

    /**
     * Check if a new incident should trigger breaking news.
     * Called after every new incident is created.
     */
    public function checkForBreakingNews(Incident $newIncident): ?BreakingNews
    {
        if ($newIncident->latitude === null || $newIncident->longitude === null) {
            return null;
        }

        // Find similar incidents in the same area (last 2 hours)
        $similar = Incident::where('id', '!=', $newIncident->id)
            ->where('category', $newIncident->category)
            ->where('is_active', true)
            ->where('created_at', '>=', now()->subHours(2))
            ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < ?', [
                $newIncident->latitude, $newIncident->longitude, $newIncident->latitude, self::NEARBY_RADIUS_KM,
            ])
            ->get();

        $totalReporters = $similar->count() + 1; // +1 for the new incident

        if ($totalReporters < self::MIN_REPORTERS) {
            return null; // Not enough reports yet
        }

        // Check if breaking news already exists for this cluster
        $incidentIds = $similar->pluck('id')->push($newIncident->id)->toArray();
        $existing = BreakingNews::where('is_active', true)
            ->where('category', $newIncident->category)
            ->where('created_at', '>=', now()->subHours(6))
            ->whereRaw('(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) < ?', [
                $newIncident->latitude, $newIncident->longitude, $newIncident->latitude, self::NEARBY_RADIUS_KM,
            ])
            ->first();

        if ($existing) {
            // Update existing breaking news
            $existing->update([
                'reporter_count' => $totalReporters,
                'source_incident_ids' => $incidentIds,
                'image_urls' => $this->collectImages($similar->push($newIncident)),
            ]);
            return $existing;
        }

        // Generate breaking news content via AI
        $content = $this->generateNewsContent($newIncident, $similar, $totalReporters);

        // Collect all images from reporters
        $images = $this->collectImages($similar->push($newIncident));

        $breakingNews = BreakingNews::create([
            'title' => $this->generateTitle($newIncident, $totalReporters),
            'content' => $content,
            'category' => $newIncident->category,
            'latitude' => $newIncident->latitude,
            'longitude' => $newIncident->longitude,
            'location_name' => $newIncident->title,
            'image_urls' => $images,
            'source_incident_ids' => $incidentIds,
            'reporter_count' => $totalReporters,
            'is_active' => true,
        ]);

        Log::info('Breaking news created', [
            'id' => $breakingNews->id,
            'category' => $newIncident->category,
            'reporters' => $totalReporters,
        ]);

        return $breakingNews;
    }

    /**
     * Generate news title.
     */
    private function generateTitle(Incident $incident, int $reporters): string
    {
        $emoji = Incident::CATEGORY_EMOJI[$incident->category] ?? '🔴';
        $label = Incident::CATEGORY_LABELS[$incident->category] ?? $incident->category;

        return "{$emoji} ด่วน! {$label} — {$incident->title} ({$reporters} คนรายงาน)";
    }

    /**
     * Generate news content using AI (น้องหญิงเขียนข่าว).
     */
    private function generateNewsContent(Incident $main, $similar, int $reporters): string
    {
        $groq = app(GroqAIService::class);

        if (!$groq->isAvailable()) {
            return $this->fallbackContent($main, $reporters);
        }

        $descriptions = $similar->pluck('description')->filter()->implode(' | ');
        $titles = $similar->pluck('title')->push($main->title)->implode(', ');

        $messages = [
            ['role' => 'system', 'content' => 'คุณคือ "น้องหญิง" นักข่าวของ ThaiHelp เขียนข่าวด่วนจากรายงานของผู้ใช้หลายคน เขียนเป็นภาษาไทย สั้นกระชับ 3-5 ประโยค ระบุบริเวณ สถานการณ์ คำแนะนำ ลงท้ายด้วย "— น้องหญิง รายงาน"'],
            ['role' => 'user', 'content' => "เขียนข่าวด่วนจากรายงาน {$reporters} คน:\nประเภท: {$main->category}\nหัวข้อ: {$titles}\nรายละเอียด: {$descriptions}\nพิกัด: {$main->latitude}, {$main->longitude}"],
        ];

        try {
            return $groq->chat($messages);
        } catch (\Exception $e) {
            return $this->fallbackContent($main, $reporters);
        }
    }

    /**
     * Fallback content when AI unavailable.
     */
    private function fallbackContent(Incident $incident, int $reporters): string
    {
        $emoji = Incident::CATEGORY_EMOJI[$incident->category] ?? '🔴';
        $label = Incident::CATEGORY_LABELS[$incident->category] ?? $incident->category;

        return "{$emoji} มีผู้รายงาน {$reporters} คน เกี่ยวกับ{$label}บริเวณ{$incident->title} "
            . "กรุณาหลีกเลี่ยงเส้นทาง และระมัดระวังในการเดินทางนะคะ "
            . "— น้องหญิง รายงาน";
    }

    /**
     * Collect all image URLs from incidents.
     */
    private function collectImages($incidents): array
    {
        $images = [];
        foreach ($incidents as $i) {
            if ($i->image_url) $images[] = $i->image_url;
            if ($i->photos && is_array($i->photos)) {
                $images = array_merge($images, $i->photos);
            }
        }
        return array_unique(array_slice($images, 0, 10)); // Max 10 images
    }
}

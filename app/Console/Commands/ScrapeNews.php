<?php

namespace App\Console\Commands;

use App\Services\NewsScraperService;
use Illuminate\Console\Command;

class ScrapeNews extends Command
{
    protected $signature = 'news:scrape';
    protected $description = 'Scrape fuel/crisis news from Thai news sources';

    public function handle(NewsScraperService $service): int
    {
        $this->info('Scraping news from all sources...');

        $count = $service->scrapeAll();

        $this->info("Done! {$count} new articles added.");

        return self::SUCCESS;
    }
}

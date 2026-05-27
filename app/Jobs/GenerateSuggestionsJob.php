<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Site;
use App\Services\LlmClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class GenerateSuggestionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Site $site,
        public array $posts,
    ) {}

    public function handle(LlmClient $client): void
    {
        $summary = $this->buildSummary(collect($this->posts));
        $suggestions = $client->suggestTopics($summary);

        foreach ($suggestions as $suggestion) {
            $this->site->suggestions()->create($suggestion);
        }
    }

    private function buildSummary(Collection $posts): array
    {
        return [
            'site' => [
                'id' => $this->site->id,
                'name' => $this->site->name,
                'url' => $this->site->url,
            ],
            'total_posts' => $posts->count(),
            'top_categories' => $posts->countBy('category')->sortDesc()->take(5)->toArray(),
            'top_authors' => $posts->countBy('author')->sortDesc()->take(5)->toArray(),
            'recent_titles' => $posts->pluck('title')->take(20)->values()->all(),
        ];
    }
}

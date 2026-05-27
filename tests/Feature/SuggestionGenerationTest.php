<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Site;
use App\Models\Suggestion;
use App\Services\LlmClient;
use App\Services\WordPressClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Mockery;
use Tests\TestCase;

class SuggestionGenerationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Bloqueia geração quando há menos de 5 posts disponíveis.
     * A LLM não deve ser chamada nesse caso.
     */
    public function test_it_blocks_suggestion_generation_when_site_has_insufficient_data(): void
    {
        $site = Site::query()->create([
            'name'  => 'Portal Teste',
            'url'   => 'https://portal.test',
            'token' => 'secret-token',
        ]);

        $client = Mockery::mock(WordPressClient::class);
        $client->shouldReceive('posts')->once()->andReturn([
            ['title' => 'Pauta A', 'author_name' => 'Ana', 'categories' => ['Política'], 'published_at' => now()->subDay()->toIso8601String(), 'url' => ''],
            ['title' => 'Pauta B', 'author_name' => 'Ana', 'categories' => ['Política'], 'published_at' => now()->subDays(2)->toIso8601String(), 'url' => ''],
        ]);

        $llm = Mockery::mock(LlmClient::class);
        $llm->shouldNotReceive('suggestTopics');

        $this->app->instance(WordPressClient::class, $client);
        $this->app->instance(LlmClient::class, $llm);

        $this->post(route('suggestions.store', $site), ['period' => 30])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('suggestions', 0);
    }

    /**
     * Bloqueia geração quando o rate limit de 3 tentativas/minuto foi atingido.
     * Nem o WP nem a LLM devem ser consultados.
     */
    public function test_it_blocks_suggestion_generation_when_rate_limit_is_exceeded(): void
    {
        $site = Site::query()->create([
            'name'  => 'Portal Teste',
            'url'   => 'https://portal.test',
            'token' => 'secret-token',
        ]);

        $rateKey = 'suggestions-site-' . $site->id;
        RateLimiter::clear($rateKey);
        RateLimiter::hit($rateKey, 60);
        RateLimiter::hit($rateKey, 60);
        RateLimiter::hit($rateKey, 60);

        $client = Mockery::mock(WordPressClient::class);
        $client->shouldNotReceive('posts');

        $llm = Mockery::mock(LlmClient::class);
        $llm->shouldNotReceive('suggestTopics');

        $this->app->instance(WordPressClient::class, $client);
        $this->app->instance(LlmClient::class, $llm);

        $this->post(route('suggestions.store', $site), ['period' => 30])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('suggestions', 0);
    }

    /**
     * Gera sugestões com sucesso quando há dados suficientes.
     * A LLM é mockada para retornar JSON estruturado sem chamar a API real.
     */
    public function test_it_generates_and_persists_suggestions_when_data_is_sufficient(): void
    {
        $site = Site::query()->create([
            'name'  => 'Portal Teste',
            'url'   => 'https://portal.test',
            'token' => 'secret-token',
        ]);

        $posts = collect(range(1, 6))->map(fn (int $i): array => [
            'title'        => "Pauta {$i}",
            'author_name'  => 'Ana',
            'categories'   => ['Tecnologia'],
            'published_at' => now()->subDays($i)->toIso8601String(),
            'url'          => '',
        ])->all();

        $client = Mockery::mock(WordPressClient::class);
        $client->shouldReceive('posts')->once()->andReturn($posts);

        $llm = Mockery::mock(LlmClient::class);
        $llm->shouldReceive('suggestTopics')->once()->andReturn([
            ['title' => 'Nova pauta 1', 'hook' => 'Gancho 1', 'seo_keywords' => ['kw1', 'kw2', 'kw3']],
            ['title' => 'Nova pauta 2', 'hook' => 'Gancho 2', 'seo_keywords' => ['kw4', 'kw5', 'kw6']],
        ]);

        $this->app->instance(WordPressClient::class, $client);
        $this->app->instance(LlmClient::class, $llm);

        $this->post(route('suggestions.store', $site), ['period' => 30])
            ->assertSessionHas('success');

        $this->assertDatabaseCount('suggestions', 2);
        $this->assertDatabaseHas('suggestions', ['title' => 'Nova pauta 1', 'site_id' => $site->id]);
    }
}

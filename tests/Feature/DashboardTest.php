<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Site;
use App\Services\WordPressClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Verifica que o dashboard recebe dados do WordPressClient (mockado),
     * normaliza corretamente e exibe métricas agregadas.
     *
     * Usamos mock para não depender de um WordPress real nos testes.
     */
    public function test_dashboard_receives_wordpress_data_and_aggregates_metrics(): void
    {
        $site = Site::query()->create([
            'name'  => 'Portal Teste',
            'url'   => 'https://portal.test',
            'token' => 'secret-token',
        ]);

        // Simula o retorno do plugin com os campos reais (author_name, categories, published_at)
        $client = Mockery::mock(WordPressClient::class);
        $client->shouldReceive('posts')
            ->once()
            ->with(Mockery::on(fn (Site $received): bool => $received->is($site)))
            ->andReturn([
                ['title' => 'Pauta A', 'author_name' => 'Ana', 'categories' => ['Política'],  'published_at' => now()->subDays(1)->toIso8601String(), 'url' => ''],
                ['title' => 'Pauta B', 'author_name' => 'Ana', 'categories' => ['Política'],  'published_at' => now()->subDays(2)->toIso8601String(), 'url' => ''],
                ['title' => 'Pauta C', 'author_name' => 'Bruno', 'categories' => ['Economia'], 'published_at' => now()->subDays(3)->toIso8601String(), 'url' => ''],
                ['title' => 'Pauta D', 'author_name' => 'Carla', 'categories' => ['Cultura'],  'published_at' => now()->subDays(4)->toIso8601String(), 'url' => ''],
            ]);

        $this->app->instance(WordPressClient::class, $client);

        $this->get(route('dashboard', ['site_id' => $site->id, 'period' => 30]))
            ->assertOk()
            ->assertSee('Total de pautas no período')
            ->assertSee('4')
            ->assertSee('Ana')
            ->assertSee('Política')
            ->assertSee('Pauta A');
    }

    /**
     * Garante que, quando o WordPress retorna erro, o dashboard
     * mostra mensagem amigável em vez de quebrar.
     */
    public function test_dashboard_shows_friendly_error_when_wordpress_is_unreachable(): void
    {
        $site = Site::query()->create([
            'name'  => 'Portal Offline',
            'url'   => 'https://offline.test',
            'token' => 'any-token',
        ]);

        $client = Mockery::mock(WordPressClient::class);
        $client->shouldReceive('posts')
            ->once()
            ->andThrow(new \RuntimeException('Não foi possível consultar o WordPress.'));

        $this->app->instance(WordPressClient::class, $client);

        $this->get(route('dashboard', ['site_id' => $site->id]))
            ->assertOk()
            ->assertSee('Erro ao consultar o WordPress');
    }
}

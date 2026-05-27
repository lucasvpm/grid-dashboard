<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Site;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WordPressClient
{
    /**
     * Retorna TODOS os posts publicados do site, buscando página por página.
     * O resultado completo fica cacheado por 10 minutos.
     */
    public function posts(Site $site): array
    {
        return Cache::remember($this->cacheKey($site), now()->addMinutes(10), function () use ($site): array {
            return $this->fetchAllPages($site);
        });
    }

    /**
     * Limpa o cache do site para forçar nova consulta ao WordPress.
     */
    public function forget(Site $site): void
    {
        Cache::forget($this->cacheKey($site));
    }

    // -------------------------------------------------------------------------

    /**
     * Percorre todas as páginas do endpoint REST e retorna um array único.
     * Isso garante que filtros e métricas usem dados completos, não só a página 1.
     */
    private function fetchAllPages(Site $site): array
    {
        $allPosts   = [];
        $page       = 1;
        $perPage    = 100; // máximo permitido pelo plugin

        do {
            $response = Http::withToken($site->token)
                ->acceptJson()
                ->timeout(15)
                ->get(rtrim($site->url, '/') . '/wp-json/grid/v1/posts', [
                    'page'     => $page,
                    'per_page' => $perPage,
                ]);

            if ($response->status() === 401 || $response->status() === 403) {
                throw new RuntimeException('Token inválido ou sem permissão para consultar este WordPress.');
            }

            if (! $response->successful()) {
                throw new RuntimeException('Não foi possível consultar o WordPress. Verifique a URL, o token e se o plugin está ativo.');
            }

            $body = $response->json();

            if (! is_array($body) || ! isset($body['data']) || ! is_array($body['data'])) {
                throw new RuntimeException('O WordPress respondeu em um formato inesperado.');
            }

            // O plugin retorna { data: [...], meta: { total_pages: N } }
            $allPosts   = array_merge($allPosts, $body['data']);
            $totalPages = (int) data_get($body, 'meta.total_pages', 1);

            $page++;
        } while ($page <= $totalPages);

        return $allPosts;
    }

    private function cacheKey(Site $site): string
    {
        return "grid_wp_posts_site_{$site->id}";
    }
}

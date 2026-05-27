<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class PostFilterService
{
    /**
     * Normaliza o payload bruto do plugin para um formato interno consistente.
     *
     * O plugin retorna: id, title, author_name, categories (array),
     *                   published_at (ISO 8601), url
     *
     * Internamente usamos: title, author, category, date (Y-m-d)
     */
    public function normalize(array $posts): Collection
    {
        return collect($posts)
            ->map(function (array $post): array {
                // Data: plugin envia published_at; aceita fallback de campos antigos
                $rawDate = $post['published_at']
                    ?? $post['date']
                    ?? $post['post_date']
                    ?? $post['created_at']
                    ?? null;

                // Categoria: plugin envia array; pegamos a primeira ou "Sem categoria"
                $rawCategory = $post['categories'] ?? $post['category'] ?? null;
                if (is_array($rawCategory)) {
                    $rawCategory = $rawCategory[0] ?? 'Sem categoria';
                }

                return [
                    'title'    => (string) ($post['title'] ?? $post['post_title'] ?? 'Sem título'),
                    'author'   => (string) ($post['author_name'] ?? $post['author'] ?? 'Autor desconhecido'),
                    'category' => (string) ($rawCategory ?? 'Sem categoria'),
                    'date'     => $rawDate ? Carbon::parse($rawDate)->toDateString() : now()->toDateString(),
                    'url'      => (string) ($post['url'] ?? ''),
                ];
            })
            ->values();
    }

    /**
     * Aplica os filtros de período, autor e categoria.
     */
    public function apply(Collection $posts, array $filters): Collection
    {
        $period = (int) ($filters['period'] ?? 30);
        $from   = now()->subDays($period)->startOfDay();

        return $posts
            ->filter(fn (array $post): bool => Carbon::parse($post['date'])->greaterThanOrEqualTo($from))
            ->when(
                ! empty($filters['author']),
                fn (Collection $c): Collection => $c->where('author', $filters['author'])
            )
            ->when(
                ! empty($filters['category']),
                fn (Collection $c): Collection => $c->where('category', $filters['category'])
            )
            ->sortByDesc('date')
            ->values();
    }

    /**
     * Agrega métricas para exibição nos cards do dashboard.
     */
    public function metrics(Collection $posts): array
    {
        return [
            'total'          => $posts->count(),
            'topAuthors'     => $posts->countBy('author')->sortDesc()->take(5),
            'topCategories'  => $posts->countBy('category')->sortDesc()->take(5),
            'postsByDay'     => $posts->groupBy('date')->map->count()->sortKeys(),
            'authors'        => $posts->pluck('author')->unique()->sort()->values(),
            'categories'     => $posts->pluck('category')->unique()->sort()->values(),
        ];
    }
}

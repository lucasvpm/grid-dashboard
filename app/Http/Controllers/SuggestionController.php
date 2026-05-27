<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\GenerateSuggestionsJob;
use App\Models\Site;
use App\Services\PostFilterService;
use App\Services\WordPressClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class SuggestionController extends Controller
{
    public function store(Request $request, Site $site, WordPressClient $client, PostFilterService $filterService): RedirectResponse
    {
        $rateKey = 'suggestions-site-' . $site->id;

        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            return back()->with('error', 'Limite atingido: máximo de 3 gerações por minuto para este site.');
        }

        try {
            $posts = $filterService->normalize($client->posts($site));
            $filteredPosts = $filterService->apply($posts, $request->only(['author', 'category', 'period']));

            if ($filteredPosts->count() < 5) {
                return back()->with('error', 'Não há dados suficientes para gerar sugestões. Cadastre ou filtre pelo menos 5 posts.');
            }

            RateLimiter::hit($rateKey, 60);

            GenerateSuggestionsJob::dispatchSync($site, $filteredPosts->values()->all());

            return redirect()
                ->route('dashboard', array_filter([
                    'site_id' => $site->id,
                    'author' => $request->input('author'),
                    'category' => $request->input('category'),
                    'period' => $request->input('period'),
                ]))
                ->with('success', 'Sugestões geradas com sucesso.');
        } catch (Throwable $throwable) {
            return back()->with('error', $throwable->getMessage());
        }
    }
}

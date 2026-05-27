<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\PostFilterService;
use App\Services\WordPressClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class DashboardController extends Controller
{
    public function index(Request $request, WordPressClient $client, PostFilterService $filterService): View
    {
        $sites = Site::query()->orderBy('name')->get();
        $site = $request->filled('site_id')
            ? Site::query()->find($request->integer('site_id'))
            : $sites->first();

        $posts = collect();
        $filteredPosts = collect();
        $metrics = $filterService->metrics(collect());
        $error = null;

        if ($site instanceof Site) {
            try {
                $posts = $filterService->normalize($client->posts($site));
                $filteredPosts = $filterService->apply($posts, $request->only(['author', 'category', 'period']));
                $metrics = $filterService->metrics($filteredPosts);
            } catch (Throwable $throwable) {
                $error = $throwable->getMessage();
            }
        }

        return view('dashboard.index', [
            'sites' => $sites,
            'site' => $site,
            'posts' => $posts,
            'filteredPosts' => $filteredPosts,
            'metrics' => $metrics,
            'error' => $error,
            'filters' => [
                'author' => $request->string('author')->toString(),
                'category' => $request->string('category')->toString(),
                'period' => $request->integer('period', 30),
            ],
            'suggestions' => $site
                ? $site->suggestions()->latest()->take(12)->get()
                : collect(),
        ]);
    }

    public function refresh(Site $site, WordPressClient $client): RedirectResponse
    {
        $client->forget($site);

        return redirect()
            ->route('dashboard', ['site_id' => $site->id])
            ->with('success', 'Cache limpo. Os dados serão consultados novamente no WordPress.');
    }
}

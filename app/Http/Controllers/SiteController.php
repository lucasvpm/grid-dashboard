<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Models\Site;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function index(): View
    {
        return view('sites.index', [
            'sites' => Site::query()->latest()->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('sites.create', [
            'site' => new Site(),
        ]);
    }

    public function store(StoreSiteRequest $request): RedirectResponse
    {
        Site::query()->create($request->validated());

        return redirect()
            ->route('sites.index')
            ->with('success', 'Site cadastrado com sucesso.');
    }

    public function edit(Site $site): View
    {
        return view('sites.edit', [
            'site' => $site,
        ]);
    }

    public function update(UpdateSiteRequest $request, Site $site): RedirectResponse
    {
        $site->update($request->validated());

        return redirect()
            ->route('sites.index')
            ->with('success', 'Site atualizado com sucesso.');
    }

    public function destroy(Site $site): RedirectResponse
    {
        $site->delete();

        return redirect()
            ->route('sites.index')
            ->with('success', 'Site removido com sucesso.');
    }
}

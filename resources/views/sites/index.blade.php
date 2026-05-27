@extends('layout', ['title' => 'Sites - Grid Connector'])

@section('content')

<div class="mb-6 flex items-center justify-between">
    <h2 class="text-xl font-bold text-slate-100">Sites cadastrados</h2>
    <a href="{{ route('sites.create') }}"
       class="rounded-xl bg-cyan-400 px-4 py-2 text-sm font-bold text-slate-950 hover:bg-cyan-300 transition">
        + Novo site
    </a>
</div>

@if ($sites->isEmpty())
    <div class="rounded-2xl border border-white/10 bg-slate-800/40 p-10 text-center">
        <p class="text-slate-400">Nenhum site cadastrado ainda.</p>
        <a href="{{ route('sites.create') }}" class="mt-3 inline-block text-cyan-400 underline text-sm">
            Cadastrar o primeiro site
        </a>
    </div>
@else
    <div class="overflow-hidden rounded-2xl border border-white/10">
        <table class="w-full text-sm">
            <thead class="bg-slate-800/80">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-widest text-slate-400">Nome</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-widest text-slate-400">URL</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-widest text-slate-400">Token</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-widest text-slate-400">Cadastrado em</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sites as $site)
                    <tr class="border-t border-white/5 hover:bg-slate-800/40 transition">
                        <td class="px-5 py-3 font-medium text-slate-100">{{ $site->name }}</td>
                        <td class="px-5 py-3 text-slate-400">
                            <a href="{{ $site->url }}" target="_blank"
                               class="hover:text-cyan-400 transition truncate max-w-[200px] block">
                                {{ $site->url }}
                            </a>
                        </td>
                        <td class="px-5 py-3">
                            <span class="font-mono text-xs text-slate-500">
                                {{ substr($site->token, 0, 8) }}••••••••
                            </span>
                        </td>
                        <td class="px-5 py-3 text-slate-500">{{ $site->created_at->format('d/m/Y') }}</td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('dashboard', ['site_id' => $site->id]) }}"
                                   class="rounded-lg bg-cyan-400/10 px-3 py-1.5 text-xs font-medium text-cyan-300 hover:bg-cyan-400/20 transition">
                                    Ver dashboard
                                </a>
                                <a href="{{ route('sites.edit', $site) }}"
                                   class="rounded-lg bg-slate-700 px-3 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-600 transition">
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('sites.destroy', $site) }}"
                                      onsubmit="return confirm('Remover este site?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="rounded-lg bg-red-400/10 px-3 py-1.5 text-xs font-medium text-red-300 hover:bg-red-400/20 transition">
                                        Remover
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $sites->links() }}
    </div>
@endif

@endsection

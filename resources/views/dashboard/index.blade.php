@extends('layout', ['title' => 'Dashboard - Grid Connector'])

@section('content')

{{-- ── Seletor de site ── --}}
<div class="mb-8 flex flex-wrap items-end gap-4">
    <div class="flex-1 min-w-[220px]">
        <label class="mb-1 block text-xs font-semibold uppercase tracking-widest text-slate-400">Site</label>
        @if ($sites->isEmpty())
            <p class="text-slate-400">Nenhum site cadastrado.
                <a href="{{ route('sites.create') }}" class="text-cyan-400 underline">Cadastre um agora</a>.
            </p>
        @else
            <form method="GET" action="{{ route('dashboard') }}" id="siteForm">
                <div class="flex gap-2">
                    <select name="site_id" id="site_id"
                        onchange="document.getElementById('siteForm').submit()"
                        class="flex-1 rounded-xl border border-white/10 bg-slate-800 px-4 py-2.5 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                        @foreach ($sites as $s)
                            <option value="{{ $s->id }}" {{ optional($site)->id === $s->id ? 'selected' : '' }}>
                                {{ $s->name }}
                            </option>
                        @endforeach
                    </select>
                    {{-- Preserva filtros actuais ao trocar de site --}}
                    <input type="hidden" name="period"   value="{{ $filters['period'] }}">
                    <input type="hidden" name="author"   value="{{ $filters['author'] }}">
                    <input type="hidden" name="category" value="{{ $filters['category'] }}">
                </div>
            </form>
        @endif
    </div>

    @if ($site)
    {{-- Botão Atualizar dados --}}
    <form method="POST" action="{{ route('dashboard.refresh', $site) }}">
        @csrf
        <button type="submit"
            class="rounded-xl border border-white/10 bg-slate-800 px-4 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-700 hover:text-white transition">
            ↺ Atualizar dados
        </button>
    </form>
    @endif
</div>

{{-- ── Erro de conexão ── --}}
@if ($error)
    <div class="mb-8 rounded-2xl border border-red-400/30 bg-red-400/10 px-5 py-4 text-red-200">
        <strong class="font-semibold">Erro ao consultar o WordPress:</strong> {{ $error }}
    </div>
@endif

@if ($site)

{{-- ── Filtros ── --}}
<form method="GET" action="{{ route('dashboard') }}" class="mb-8">
    <input type="hidden" name="site_id" value="{{ $site->id }}">

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        {{-- Autor --}}
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-widest text-slate-400">Autor</label>
            <select name="author"
                class="w-full rounded-xl border border-white/10 bg-slate-800 px-4 py-2.5 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                <option value="">Todos</option>
                @foreach ($metrics['authors'] as $author)
                    <option value="{{ $author }}" {{ $filters['author'] === $author ? 'selected' : '' }}>
                        {{ $author }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Categoria --}}
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-widest text-slate-400">Categoria</label>
            <select name="category"
                class="w-full rounded-xl border border-white/10 bg-slate-800 px-4 py-2.5 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                <option value="">Todas</option>
                @foreach ($metrics['categories'] as $cat)
                    <option value="{{ $cat }}" {{ $filters['category'] === $cat ? 'selected' : '' }}>
                        {{ $cat }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Período --}}
        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-widest text-slate-400">Período</label>
            <select name="period"
                class="w-full rounded-xl border border-white/10 bg-slate-800 px-4 py-2.5 text-sm text-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-400">
                @foreach ([7 => 'Últimos 7 dias', 30 => 'Últimos 30 dias', 60 => 'Últimos 60 dias'] as $val => $label)
                    <option value="{{ $val }}" {{ (int) $filters['period'] === $val ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-4 flex justify-end">
        <button type="submit"
            class="rounded-xl bg-cyan-400 px-6 py-2.5 text-sm font-bold text-slate-950 hover:bg-cyan-300 transition">
            Filtrar
        </button>
    </div>
</form>

{{-- ── Métricas (cards) ── --}}
<div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
    <div class="col-span-2 rounded-2xl border border-white/10 bg-slate-800/60 p-6 sm:col-span-1">
        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Total de pautas no período</p>
        <p class="mt-2 text-4xl font-black text-cyan-400">{{ $metrics['total'] }}</p>
    </div>

    {{-- Top autores --}}
    <div class="rounded-2xl border border-white/10 bg-slate-800/60 p-6">
        <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-slate-400">Top autores</p>
        @forelse ($metrics['topAuthors'] as $author => $count)
            <div class="mb-1 flex items-center justify-between text-sm">
                <span class="truncate text-slate-200">{{ $author }}</span>
                <span class="ml-2 shrink-0 rounded-full bg-cyan-400/20 px-2 py-0.5 text-xs font-bold text-cyan-300">{{ $count }}</span>
            </div>
        @empty
            <p class="text-xs text-slate-500">Sem dados</p>
        @endforelse
    </div>

    {{-- Top categorias --}}
    <div class="rounded-2xl border border-white/10 bg-slate-800/60 p-6">
        <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-slate-400">Top categorias</p>
        @forelse ($metrics['topCategories'] as $cat => $count)
            <div class="mb-1 flex items-center justify-between text-sm">
                <span class="truncate text-slate-200">{{ $cat }}</span>
                <span class="ml-2 shrink-0 rounded-full bg-violet-400/20 px-2 py-0.5 text-xs font-bold text-violet-300">{{ $count }}</span>
            </div>
        @empty
            <p class="text-xs text-slate-500">Sem dados</p>
        @endforelse
    </div>

    {{-- Pautas por dia (mini gráfico de barras em HTML puro) --}}
    <div class="rounded-2xl border border-white/10 bg-slate-800/60 p-6">
        <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-slate-400">Pautas por dia</p>
        @if ($metrics['postsByDay']->isNotEmpty())
            @php $maxDay = $metrics['postsByDay']->max(); @endphp
            <div class="flex h-16 items-end gap-0.5">
                @foreach ($metrics['postsByDay']->take(-14) as $day => $count)
                    @php $height = $maxDay > 0 ? round(($count / $maxDay) * 100) : 0; @endphp
                    <div class="group relative flex-1 cursor-default">
                        <div class="rounded-sm bg-cyan-400/70 hover:bg-cyan-400 transition-colors"
                             style="height: {{ $height }}%"
                             title="{{ $day }}: {{ $count }}">
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="mt-1 text-right text-xs text-slate-500">últimos 14 dias</p>
        @else
            <p class="text-xs text-slate-500">Sem dados suficientes</p>
        @endif
    </div>
</div>

{{-- ── Lista de posts filtrados ── --}}
<div class="mb-10">
    <h3 class="mb-4 text-sm font-semibold uppercase tracking-widest text-slate-400">
        Posts filtrados ({{ $filteredPosts->count() }})
    </h3>

    @if ($filteredPosts->isEmpty())
        <p class="rounded-2xl border border-white/10 bg-slate-800/40 p-6 text-center text-slate-500">
            Nenhum post encontrado com esses filtros.
        </p>
    @else
        <div class="overflow-hidden rounded-2xl border border-white/10">
            <table class="w-full text-sm">
                <thead class="bg-slate-800/80">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-widest text-slate-400">Título</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-widest text-slate-400">Autor</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-widest text-slate-400">Categoria</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-widest text-slate-400">Data</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filteredPosts as $post)
                        <tr class="border-t border-white/5 hover:bg-slate-800/40 transition">
                            <td class="px-5 py-3 text-slate-200">{{ $post['title'] }}</td>
                            <td class="px-5 py-3 text-slate-400">{{ $post['author'] }}</td>
                            <td class="px-5 py-3">
                                <span class="rounded-full bg-violet-400/20 px-2.5 py-0.5 text-xs font-medium text-violet-300">
                                    {{ $post['category'] }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $post['date'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ── Sugestões de pauta ── --}}
<div class="border-t border-white/10 pt-10">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-bold text-slate-100">Sugestões de pauta via IA</h3>
            <p class="mt-0.5 text-xs text-slate-500">Geradas com base no histórico editorial do site. Máx. 3 gerações/min.</p>
        </div>

        <form method="POST" action="{{ route('suggestions.store', $site) }}">
            @csrf
            <input type="hidden" name="period"   value="{{ $filters['period'] }}">
            <input type="hidden" name="author"   value="{{ $filters['author'] }}">
            <input type="hidden" name="category" value="{{ $filters['category'] }}">
            <button type="submit"
                class="rounded-xl bg-violet-500 px-5 py-2.5 text-sm font-bold text-white hover:bg-violet-400 transition">
                ✦ Sugerir novas pautas
            </button>
        </form>
    </div>

    @if ($suggestions->isEmpty())
        <p class="rounded-2xl border border-white/10 bg-slate-800/40 p-6 text-center text-slate-500">
            Nenhuma sugestão gerada ainda. Clique em "Sugerir novas pautas".
        </p>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($suggestions as $suggestion)
                <div class="rounded-2xl border border-white/10 bg-slate-800/60 p-5">
                    <p class="mb-2 text-xs text-slate-500">{{ $suggestion->created_at->diffForHumans() }}</p>
                    <h4 class="mb-2 font-semibold text-slate-100 leading-snug">{{ $suggestion->title }}</h4>
                    <p class="mb-4 text-sm text-slate-400 leading-relaxed">{{ $suggestion->hook }}</p>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($suggestion->seo_keywords as $kw)
                            <span class="rounded-full border border-cyan-400/30 bg-cyan-400/10 px-2.5 py-0.5 text-xs font-medium text-cyan-300">
                                #{{ $kw }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

@endif {{-- end if $site --}}

@endsection

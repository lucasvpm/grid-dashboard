@extends('layout', ['title' => 'Editar site - Grid Connector'])

@section('content')

<div class="mx-auto max-w-xl">
    <div class="mb-6">
        <a href="{{ route('sites.index') }}" class="text-sm text-slate-400 hover:text-slate-200 transition">
            ← Voltar para Sites
        </a>
        <h2 class="mt-2 text-xl font-bold text-slate-100">Editar: {{ $site->name }}</h2>
    </div>

    <div class="rounded-2xl border border-white/10 bg-slate-800/60 p-8">
        <form method="POST" action="{{ route('sites.update', $site) }}">
            @csrf
            @method('PUT')
            @include('sites._form', ['site' => $site])

            <div class="mt-6 flex justify-end">
                <button type="submit"
                    class="rounded-xl bg-cyan-400 px-6 py-2.5 text-sm font-bold text-slate-950 hover:bg-cyan-300 transition">
                    Salvar alterações
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

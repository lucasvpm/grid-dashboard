{{-- Partial compartilhado entre create e edit --}}

<div class="mb-5">
    <label for="name" class="mb-1.5 block text-sm font-medium text-slate-300">
        Nome do site <span class="text-red-400">*</span>
    </label>
    <input type="text" id="name" name="name"
           value="{{ old('name', $site->name) }}"
           placeholder="Ex.: Portal de Notícias"
           class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm text-slate-100
                  placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-cyan-400
                  @error('name') border-red-400 @enderror">
    @error('name')
        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
    @enderror
</div>

<div class="mb-5">
    <label for="url" class="mb-1.5 block text-sm font-medium text-slate-300">
        URL do WordPress <span class="text-red-400">*</span>
    </label>
    <input type="url" id="url" name="url"
           value="{{ old('url', $site->url) }}"
           placeholder="https://meusite.com"
           class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 text-sm text-slate-100
                  placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-cyan-400
                  @error('url') border-red-400 @enderror">
    @error('url')
        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
    @enderror
    <p class="mt-1 text-xs text-slate-500">Deve bater com a URL configurada no plugin Grid Connector.</p>
</div>

<div class="mb-5">
    <label for="token" class="mb-1.5 block text-sm font-medium text-slate-300">
        Bearer Token <span class="text-red-400">*</span>
    </label>
    <input type="text" id="token" name="token"
           value="{{ old('token', $site->token) }}"
           placeholder="Cole o token gerado no plugin"
           class="w-full rounded-xl border border-white/10 bg-slate-900 px-4 py-2.5 font-mono text-sm text-slate-100
                  placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-cyan-400
                  @error('token') border-red-400 @enderror">
    @error('token')
        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
    @enderror
    <p class="mt-1 text-xs text-slate-500">
        O mesmo valor salvo em <strong>Grid Connector → Bearer Token</strong> no WordPress.
    </p>
</div>

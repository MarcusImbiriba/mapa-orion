@props(['id' => 'operations-sidebar'])

<operations-sidebar {{ $attributes->merge(['id' => $id]) }} data-open>
    <aside id="{{ $id }}-content" data-sidebar-content aria-labelledby="{{ $id }}-title"
           class="flex h-full flex-col overflow-hidden border-r border-slate-800 bg-slate-900/95 text-slate-100 shadow-2xl backdrop-blur-md">
        <div data-sidebar-scroll class="flex min-h-0 flex-1 flex-col">
            <header class="flex shrink-0 flex-col gap-3 bg-slate-950/40 p-4">
                <h2 id="{{ $id }}-title" class="flex items-center gap-1.5 font-mono text-xs font-bold tracking-wider text-slate-400 uppercase">
                    <svg class="size-4 shrink-0 text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m16.24 7.76-2.12 6.36-6.36 2.12 2.12-6.36z"/></svg>
                    Painel de Unidades & Áreas
                </h2>

                @if (isset($search) && $search->hasActualContent())
                    <div data-sidebar-search>{{ $search }}</div>
                @endif

                @if (isset($layers) && $layers->hasActualContent())
                    <div data-sidebar-layers>{{ $layers }}</div>
                @endif
            </header>

            @if (isset($units) && $units->hasActualContent())
                <div data-sidebar-units class="min-h-0 flex-1 overflow-y-auto overscroll-contain border-t border-slate-800 p-3">
                    {{ $units }}
                </div>
            @endif
        </div>

        <footer class="shrink-0 border-t border-slate-800 bg-slate-950 p-3 text-center font-mono text-[10px] text-slate-500">
            DGTI - Polícia Militar do Maranhão © {{ now()->year }}
        </footer>
    </aside>

    <button type="button" data-sidebar-toggle aria-expanded="true" aria-controls="{{ $id }}-content"
            aria-label="Recolher painel" title="Recolher painel"
            class="absolute top-2 left-full flex size-9 items-center justify-center rounded-r-xl border border-l-0 border-slate-700 bg-slate-900 text-amber-400 shadow-xl transition-colors hover:bg-slate-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400">
        <svg data-sidebar-chevron class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
    </button>
</operations-sidebar>

<x-layouts.app title="Mapa">
    <div class="flex h-svh min-h-[28rem] flex-col">
        <header class="shrink-0 border-b border-slate-800 bg-slate-900">
            <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold tracking-tight sm:text-xl">Mapa Orion</h1>
                    <p class="truncate text-xs text-slate-400">Bem-vindo, {{ auth()->user()->name }}.</p>
                </div>
                <form method="POST" action="{{ route('logout') }}"
                      data-signals:signingout="false"
                      data-indicator:signingout
                      data-on:submit="{{ datastar()->post(route('logout'), ['contentType' => 'form', 'retry' => 'never']) }}">
                    @csrf
                    <button type="submit" data-attr:disabled="$signingout"
                            class="rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium transition hover:border-amber-300 hover:text-amber-200 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-300 disabled:opacity-60">
                        <span data-text="$signingout ? 'Saindo…' : 'Sair'">Sair</span>
                    </button>
                </form>
            </div>
        </header>

        <main class="min-h-0 flex-1" aria-label="Mapa de São Luís">
            <mapa-orion-map id="orion-map"
                            latitude="{{ config('map.center.latitude') }}"
                            longitude="{{ config('map.center.longitude') }}"
                            zoom="{{ config('map.zoom') }}"
                            max-zoom="{{ config('map.max_zoom') }}"
                            tile-url="{{ config('map.tile_url') }}"
                            attribution="{{ config('map.attribution') }}"
                            units="{{ $units->toJson() }}"
                            data-ignore-morph>
                <div data-map-canvas data-ignore aria-label="Mapa interativo. Use as setas para navegar e mais ou menos para ajustar o zoom."></div>

                <div class="pointer-events-none absolute inset-x-3 top-3 z-[1000] flex items-start justify-between gap-3 sm:inset-x-5 sm:top-5">
                    <div class="rounded-xl border border-slate-700 bg-slate-900/95 px-3 py-2 shadow-lg">
                        <p class="text-sm font-semibold text-amber-200">São Luís</p>
                        <p class="text-xs text-slate-300">Maranhão</p>
                    </div>
                    <button type="button" data-map-recenter disabled
                            class="pointer-events-auto rounded-xl border border-slate-700 bg-slate-900/95 px-3 py-2 text-sm font-medium text-slate-100 shadow-lg transition hover:border-amber-300 hover:text-amber-200 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-300 disabled:opacity-60">
                        Recentralizar
                    </button>
                </div>

                <p data-map-status role="status"
                   class="absolute bottom-12 left-3 z-[1000] max-w-[calc(100%_-_5rem)] rounded-lg border border-slate-700 bg-slate-900/95 px-3 py-2 text-sm text-slate-100 shadow-lg">
                    Carregando o mapa…
                </p>
                <noscript>
                    <p class="absolute inset-x-3 top-24 z-[1000] rounded-lg bg-slate-900 p-3 text-sm text-slate-100">
                        Ative o JavaScript no navegador para visualizar o mapa.
                    </p>
                </noscript>
            </mapa-orion-map>
        </main>
    </div>
</x-layouts.app>

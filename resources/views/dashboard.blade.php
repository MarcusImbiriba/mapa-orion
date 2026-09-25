<x-layouts.app title="Mapa">
    <div class="flex h-svh flex-col overflow-hidden">
        <x-map-header />

        <main class="map-workspace relative isolate min-h-0 flex-1" aria-label="Mapa de São Luís">
            <mapa-orion-map id="orion-map"
                            latitude="{{ config('map.center.latitude') }}"
                            longitude="{{ config('map.center.longitude') }}"
                            zoom="{{ config('map.zoom') }}"
                            max-zoom="{{ config('map.max_zoom') }}"
                            tile-url="{{ config('map.tile_url') }}"
                            attribution="{{ config('map.attribution') }}"
                            satellite-tile-url="{{ config('map.satellite.tile_url') }}"
                            satellite-attribution="{{ config('map.satellite.attribution') }}"
                            satellite-max-zoom="{{ config('map.satellite.max_zoom') }}"
                            units="{{ $units->toJson() }}"
                            data-ignore-morph>
                <div data-map-canvas data-ignore aria-label="Mapa interativo. Use as setas para navegar e mais ou menos para ajustar o zoom."></div>

                <div data-map-toolbar class="pointer-events-none absolute top-3 right-3 z-[1000] flex flex-col items-end gap-2 sm:top-5 sm:right-5">
                    <div data-map-basemap-control role="group" aria-label="Tipo de mapa"
                         class="pointer-events-auto flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900/90 p-1.5 shadow-2xl backdrop-blur-md">
                        <button type="button" data-map-basemap value="satellite" aria-pressed="false" disabled
                                class="cursor-pointer rounded-lg px-2.5 py-1.5 text-xs font-semibold whitespace-nowrap text-slate-300 transition-all aria-pressed:bg-amber-500 aria-pressed:font-bold aria-pressed:text-slate-950 aria-pressed:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400 disabled:cursor-not-allowed">
                            Satélite Esri
                        </button>
                        <button type="button" data-map-basemap value="streets" aria-pressed="true" disabled
                                class="cursor-pointer rounded-lg px-2.5 py-1.5 text-xs font-semibold whitespace-nowrap text-slate-300 transition-all aria-pressed:bg-amber-500 aria-pressed:font-bold aria-pressed:text-slate-950 aria-pressed:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400 disabled:cursor-not-allowed">
                            OpenStreetMap
                        </button>
                    </div>
                    <button type="button" data-map-recenter disabled title="Recentralizar"
                            class="pointer-events-auto flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-900/95 px-3 py-2 text-sm font-medium text-slate-100 shadow-lg transition hover:border-amber-300 hover:text-amber-200 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-300 disabled:cursor-not-allowed disabled:opacity-60">
                        <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/></svg>
                        <span data-map-recenter-label>Recentralizar</span>
                    </button>
                </div>

                <p data-map-status role="status"
                   class="absolute bottom-12 left-3 z-[1000] max-w-[calc(100%_-_5rem)] rounded-lg border border-slate-700 bg-slate-900/95 px-3 py-2 text-sm text-slate-100 shadow-lg">
                    Carregando o mapa…
                </p>
                <x-unit-details />

                <noscript>
                    <p class="absolute inset-x-3 top-24 z-[1000] rounded-lg bg-slate-900 p-3 text-sm text-slate-100">
                        Ative o JavaScript no navegador para visualizar o mapa.
                    </p>
                </noscript>
            </mapa-orion-map>
            <x-operations-sidebar map-target="orion-map">
                <x-slot:search>
                    <label for="unit-search" class="block text-xs font-medium text-slate-300">Buscar unidades</label>
                    <div class="mt-1 flex gap-2">
                        <input id="unit-search" data-unit-search type="search" disabled autocomplete="off"
                               aria-controls="unit-list" placeholder="Nome, sigla ou localidade"
                               class="min-w-0 flex-1 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus-visible:outline-2 focus-visible:outline-amber-400 disabled:opacity-60">
                        <button type="button" data-clear-search disabled
                                class="rounded-lg border border-slate-700 px-2 text-xs text-slate-300 hover:bg-slate-800 focus-visible:outline-2 focus-visible:outline-amber-400 disabled:opacity-50">Limpar</button>
                    </div>
                </x-slot:search>
                <x-slot:layers>
                    <fieldset class="flex flex-col gap-2">
                        <legend class="pb-2 text-xs font-semibold text-slate-400">Camadas no mapa</legend>
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-200">
                            <input type="checkbox" data-layer-points checked disabled class="size-4 accent-amber-400">
                            Sedes <span data-point-count class="ml-auto text-xs text-slate-400">—</span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-200">
                            <input type="checkbox" data-layer-areas checked disabled class="size-4 accent-amber-400">
                            Áreas operacionais <span data-area-count class="ml-auto text-xs text-slate-400">—</span>
                        </label>
                    </fieldset>
                </x-slot:layers>
                <x-slot:units>
                    <section aria-labelledby="unit-list-title" class="flex flex-col gap-3">
                        <div class="flex flex-col gap-1">
                            <h3 id="unit-list-title" class="text-sm font-semibold text-amber-200">Unidades</h3>
                            <p data-unit-count role="status" aria-live="polite" class="text-xs text-slate-400">{{ $units->count() }} de {{ $units->count() }} unidades</p>
                            <p data-unlocated-count class="text-xs text-slate-400">{{ $units->whereNull('location')->count() }} sem ponto de sede</p>
                        </div>
                        <ul id="unit-list" class="flex flex-col gap-2">
                            @foreach ($units as $unit)
                                <li data-unit-row-code="{{ $unit->code }}">
                                    <button type="button" data-unit-details-code="{{ $unit->code }}" disabled aria-haspopup="dialog"
                                            class="flex w-full flex-col gap-1 rounded-lg border border-slate-700 bg-slate-800 p-3 text-left transition-colors hover:border-amber-400 hover:bg-slate-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400 disabled:opacity-60">
                                        <span class="text-sm font-semibold text-slate-100 wrap-anywhere">{{ $unit->acronym }}</span>
                                        <span class="text-xs text-slate-300 wrap-anywhere">{{ $unit->name }}</span>
                                        <span data-unit-unlocated @if ($unit->location !== null) hidden @endif class="text-xs text-amber-300">Sem ponto de sede</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        <p data-unit-empty @if ($units->isNotEmpty()) hidden @endif class="text-sm text-slate-400">Nenhuma unidade cadastrada.</p>
                        <noscript><p class="text-xs text-amber-200">Ative o JavaScript para buscar, controlar camadas e abrir os detalhes das unidades.</p></noscript>
                    </section>
                </x-slot:units>
            </x-operations-sidebar>
        </main>
    </div>
</x-layouts.app>

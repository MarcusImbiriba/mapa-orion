<dialog data-unit-dialog aria-labelledby="unit-details-title"
        class="m-auto max-h-[calc(100svh-2rem)] w-[calc(100%-2rem)] max-w-xl overflow-hidden rounded-2xl border border-slate-800 bg-slate-900 p-0 text-slate-100 shadow-2xl ring-1 ring-slate-700/50">
    <div class="flex max-h-[calc(100svh-2rem)] flex-col">
        <header class="flex shrink-0 items-start justify-between gap-3 border-b border-slate-800 bg-slate-950 p-5">
            <div class="flex min-w-0 items-center gap-3">
                <div data-unit-field="acronym" class="shrink-0 rounded-xl border border-amber-500/30 bg-amber-500/20 p-3 font-mono text-sm font-bold text-amber-400 shadow-lg"></div>
                <div class="min-w-0">
                    <h2 id="unit-details-title" data-unit-field="name" class="text-lg font-extrabold wrap-anywhere"></h2>
                    <p data-unit-row hidden class="mt-0.5 flex items-center gap-1 text-xs text-slate-400">
                        <svg class="size-3.5 shrink-0 text-cyan-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>
                        <span data-unit-field="unit_type"></span>
                    </p>
                </div>
            </div>
            <button type="button" data-unit-close autofocus aria-label="Fechar detalhes da unidade"
                    class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-800 hover:text-slate-100 focus-visible:outline-2 focus-visible:outline-amber-400">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </header>

        <div class="unit-details-scroll min-h-0 space-y-5 overflow-y-auto p-6">
            <p id="unit-location-warning" data-unit-location-warning role="status" hidden
               class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-sm text-amber-200">
                Esta unidade não possui uma localização válida cadastrada.
            </p>

            <section aria-label="Indicadores da unidade" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach (['Efetivo Total' => ['total_personnel', 'bg-blue-500', 'text-blue-400'], 'Oficiais' => ['officers_count', 'bg-cyan-500', 'text-cyan-400'], 'Praças' => ['enlisted_count', 'bg-indigo-500', 'text-indigo-400'], 'População' => ['served_population', 'bg-amber-500', 'text-amber-400']] as $label => [$field, $barColor, $iconColor])
                    <div data-unit-metric="{{ $field }}" class="relative overflow-hidden rounded-xl border border-slate-800 bg-slate-950 p-3 text-center">
                        <div class="absolute inset-x-0 top-0 h-1 {{ $barColor }}"></div>
                        <svg class="mx-auto mb-1 size-4 {{ $iconColor }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            @if ($label === 'População')
                                <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>
                            @else
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>
                                <circle cx="12" cy="9.5" r="2.5"/>
                                <path d="M8 16.5a4 4 0 0 1 8 0"/>
                            @endif
                        </svg>
                        <h3 class="text-[10px] font-semibold text-slate-400 uppercase">{{ $label }}</h3>
                        <p class="mt-1 font-mono text-lg font-black text-slate-100" title="Não informado">
                            <span data-unit-metric-value aria-hidden="true">—</span><span data-unit-metric-accessible class="sr-only">Não informado</span>
                            @if ($label === 'População')
                                <span aria-hidden="true" class="text-xs font-normal text-slate-400">hab</span>
                            @endif
                        </p>
                    </div>
                @endforeach
            </section>

            <p data-unit-metrics-demo hidden class="text-xs text-amber-400">Indicadores com valores fictícios para demonstração.</p>

            <section data-unit-command hidden class="space-y-2.5 rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                <h3 class="flex items-center gap-1.5 font-mono text-xs font-bold tracking-wider text-amber-400 uppercase">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
                    Comando & Gestão Operacional
                </h3>
                <dl class="grid grid-cols-1 gap-3 text-xs text-slate-300 sm:grid-cols-2">
                    @foreach (['commander' => 'Comandante Responsável:', 'deputy_commander' => 'Subcomandante:', 'phone' => 'Contato Tático:', 'address' => 'Endereço da Sede:', 'email' => 'E-mail:'] as $field => $label)
                        <div data-unit-row hidden>
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd data-unit-field="{{ $field }}" @class(['whitespace-pre-wrap wrap-anywhere', 'font-semibold text-slate-100' => in_array($field, ['commander', 'deputy_commander']), 'font-mono font-semibold text-cyan-400' => in_array($field, ['phone', 'email'])])></dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section data-unit-localities hidden class="space-y-2">
                <h3 class="font-mono text-xs font-bold text-slate-400 uppercase">Bairros e setores abrangidos (<span data-unit-localities-count></span>):</h3>
                <div data-unit-localities-list class="flex flex-wrap gap-1.5"></div>
            </section>

        </div>

        <footer class="flex shrink-0 justify-end border-t border-slate-800 bg-slate-950/80 p-4">
            <button type="button" data-unit-close class="rounded-xl bg-slate-800 px-4 py-2 text-xs font-bold text-slate-300 transition hover:bg-slate-700 focus-visible:outline-2 focus-visible:outline-amber-400">Fechar</button>
        </footer>
    </div>
</dialog>

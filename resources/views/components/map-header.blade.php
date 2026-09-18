<header {{ $attributes->merge(['class' => 'map-header relative z-20 shrink-0 border-b border-slate-800 bg-slate-900/95 text-white shadow-xl backdrop-blur-md']) }}>
    <mapa-orion-header class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 px-4 py-2.5">
        <div class="flex min-w-0 items-center gap-3">
            <div class="flex shrink-0 items-center justify-center rounded-xl bg-linear-to-br from-amber-500 to-amber-700 p-2.5 shadow-lg shadow-amber-900/30 ring-1 ring-amber-400/30">
                <svg class="size-6 text-slate-950" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20 13c0 5-8 9-8 9s-8-4-8-9V5l8-3 8 3z" />
                </svg>
            </div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <h1 class="font-mono text-lg font-extrabold tracking-wider text-slate-100 uppercase">
                        <span class="sr-only">Mapa Orion — </span>PMMA <span class="font-sans text-amber-400">|</span> DGTI
                    </h1>
                    <span class="inline-flex items-center gap-1 rounded-full border border-emerald-500/30 bg-emerald-500/20 px-2 py-0.5 text-[10px] font-semibold text-emerald-400">
                        <span class="size-1.5 rounded-full bg-emerald-400 motion-safe:animate-pulse" aria-hidden="true"></span>
                        SISTEMA OPERACIONAL
                    </span>
                </div>
                <p class="hidden text-xs font-medium text-slate-400 sm:block">Mapeamento de Jurisdições, Interseções Táticas e Contingente</p>
            </div>
        </div>

        <div class="flex min-w-0 max-w-full flex-wrap items-center gap-2">
            <p class="min-w-0 max-w-full text-xs text-slate-300 sm:max-w-48">
                Bem-vindo, <span class="wrap-anywhere">{{ auth()->user()->name }}</span>.
            </p>
            <time data-header-clock hidden aria-label="Horário local" aria-live="off"
                  class="rounded-lg border border-slate-800 bg-slate-950/80 px-3 py-1.5 font-mono text-xs font-semibold text-amber-400 tabular-nums shadow-inner"></time>
            <button type="button" data-header-fullscreen hidden aria-label="Entrar em tela cheia" aria-pressed="false" title="Entrar em tela cheia"
                    class="rounded-lg border border-slate-700 bg-slate-800 p-2 text-slate-300 transition hover:bg-slate-700 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-300 active:scale-95 disabled:opacity-60">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path data-header-fullscreen-icon d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" />
                </svg>
            </button>
            <form method="POST" action="{{ route('logout') }}"
                  data-signals:signingout="false"
                  data-indicator:signingout
                  data-on:submit="{{ datastar()->post(route('logout'), ['contentType' => 'form', 'retry' => 'never']) }}">
                @csrf
                <button type="submit" data-attr:disabled="$signingout"
                        class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-2 text-xs font-medium text-slate-200 transition hover:bg-slate-700 hover:text-amber-200 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-300 disabled:opacity-60">
                    <span data-text="$signingout ? 'Saindo…' : 'Sair'">Sair</span>
                </button>
            </form>
        </div>
        <p data-header-status role="status" hidden class="w-full text-xs text-amber-200"></p>
    </mapa-orion-header>
</header>

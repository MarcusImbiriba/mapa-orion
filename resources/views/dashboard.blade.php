<x-layouts.app title="Início">
    <header class="border-b border-slate-800 bg-slate-900">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-5">
            <span class="text-xl font-semibold tracking-tight">Mapa Orion</span>
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
    <main class="mx-auto max-w-5xl px-5 py-12">
        <h1 class="text-2xl font-semibold tracking-tight">Bem-vindo, {{ auth()->user()->name }}.</h1>
        <p class="mt-3 text-slate-400">Você está conectado ao Mapa Orion.</p>
    </main>
</x-layouts.app>

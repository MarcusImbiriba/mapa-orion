<x-layouts.app title="Entrar">
    <main class="relative isolate grid min-h-svh place-items-center bg-slate-950 px-4 py-6 sm:px-6 sm:py-10">
        <img src="{{ asset('login-background.png') }}" alt="" fetchpriority="high"
             class="pointer-events-none absolute inset-0 -z-20 h-full w-full object-cover" aria-hidden="true">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-slate-950/30" aria-hidden="true"></div>

        <section class="relative w-full max-w-[32.5rem] overflow-hidden rounded-[1.875rem] border border-white/70 bg-linear-to-br from-slate-50/95 to-slate-200/95 px-6 py-8 text-slate-900 shadow-2xl shadow-black/30 backdrop-blur-md sm:px-10 sm:py-9"
                 aria-labelledby="login-title">
            <div class="absolute inset-x-0 top-0 h-1 bg-linear-to-r from-cyan-300 via-sky-100 to-amber-200" aria-hidden="true"></div>

            <header class="flex flex-col items-center gap-6 text-center">
                <img src="{{ asset('login-emblem.svg') }}" alt="" width="256" height="256"
                     class="size-40 shrink-0 drop-shadow-2xl sm:size-56" aria-hidden="true">

                <h1 id="login-title" class="relative rounded-full border border-white/80 bg-linear-to-r from-slate-100 via-sky-100 to-rose-100 px-5 py-2 text-2xl font-extrabold tracking-[0.12em] text-slate-900 uppercase shadow-lg shadow-slate-900/10 sm:px-6 sm:text-3xl">
                    Mapa Orion
                    <span class="absolute inset-x-5 bottom-1 h-0.5 rounded-full bg-linear-to-r from-slate-900 via-sky-500 to-red-500" aria-hidden="true"></span>
                </h1>

                <p class="text-sm font-medium text-slate-600">Acesse sua conta</p>
            </header>

            <form method="POST" action="{{ route('login.store') }}" class="mt-6"
                  data-signals:submitting="false"
                  data-indicator:submitting
                  data-on:submit="{{ datastar()->post(route('login.store'), ['contentType' => 'form', 'retry' => 'never']) }}">
                @csrf

                @include('auth.partials.login-errors', ['messages' => $messages ?? $errors->all()])

                <div class="grid gap-5">
                    <div class="grid gap-2">
                        <label for="username" class="text-sm font-semibold text-slate-600">Usuário</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex w-13 items-center justify-center border-r border-slate-200 text-amber-600" aria-hidden="true">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="5" y="3" width="14" height="18" rx="2" />
                                    <circle cx="12" cy="9" r="2" />
                                    <path d="M8 17v-1a4 4 0 0 1 8 0v1" />
                                </svg>
                            </span>
                            <input id="username" name="username" type="text" value="{{ $username ?? old('username') }}"
                                   autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="64"
                                   aria-describedby="login-errors" required autofocus
                                   class="min-h-13 w-full rounded-2xl border border-slate-200 bg-white/95 py-3 pr-4 pl-17 text-base text-slate-900 caret-blue-700 shadow-sm outline-none focus:border-sky-400 focus:ring-3 focus:ring-sky-400/25">
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <label for="password" class="text-sm font-semibold text-slate-600">Senha</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex w-13 items-center justify-center border-r border-slate-200 text-amber-600" aria-hidden="true">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="5" y="10" width="14" height="11" rx="2" />
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3" />
                                </svg>
                            </span>
                            <input id="password" name="password" type="password" autocomplete="current-password"
                                   aria-describedby="login-errors" maxlength="1024" required
                                   class="min-h-13 w-full rounded-2xl border border-slate-200 bg-white/95 py-3 pr-4 pl-17 text-base text-slate-900 caret-blue-700 shadow-sm outline-none focus:border-sky-400 focus:ring-3 focus:ring-sky-400/25">
                        </div>
                    </div>

                    <button type="submit" data-attr:disabled="$submitting"
                            class="flex min-h-14 w-full items-center justify-center gap-2 rounded-2xl bg-linear-to-r from-blue-800 to-blue-600 px-4 py-3 text-lg font-bold tracking-wide text-white shadow-lg shadow-blue-800/20 hover:from-blue-900 hover:to-blue-700 focus-visible:outline-3 focus-visible:outline-offset-4 focus-visible:outline-blue-700 disabled:cursor-wait disabled:opacity-60">
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M10 17l5-5-5-5M15 12H3M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7" />
                        </svg>
                        <span data-text="$submitting ? 'Entrando…' : 'Entrar'">Entrar</span>
                    </button>
                </div>
            </form>

            <p class="mt-6 text-center text-xs leading-5 text-slate-600">Acesso restrito a usuários autorizados.</p>
        </section>
    </main>
</x-layouts.app>

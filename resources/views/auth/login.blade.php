<x-layouts.app title="Entrar">
    <main class="flex min-h-svh items-center justify-center px-5 py-12">
        <section class="w-full max-w-md" aria-labelledby="login-title">
            <div class="mb-8 flex items-center gap-3">
                <span class="flex size-11 items-center justify-center rounded-xl border border-amber-300/30 bg-amber-300/10 text-lg font-semibold text-amber-200" aria-hidden="true">O</span>
                <span class="text-xl font-semibold tracking-tight">Mapa Orion</span>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6 shadow-2xl sm:p-8">
                <div class="mb-7">
                    <h1 id="login-title" class="text-2xl font-semibold tracking-tight">Acesse sua conta</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-400">Informe seu usuário e sua senha para continuar.</p>
                </div>

                <form method="POST" action="{{ route('login.store') }}" class="grid gap-5"
                      data-signals:submitting="false"
                      data-indicator:submitting
                      data-on:submit="{{ datastar()->post(route('login.store'), ['contentType' => 'form', 'retry' => 'never']) }}">
                    @csrf

                    @include('auth.partials.login-errors', ['messages' => $messages ?? $errors->all()])

                    <div class="grid gap-2">
                        <label for="username" class="text-sm font-medium">Usuário</label>
                        <input id="username" name="username" type="text" value="{{ $username ?? old('username') }}"
                               autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="64"
                               aria-describedby="login-errors" required autofocus
                               class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-3 text-base outline-none transition focus:border-amber-300 focus:ring-2 focus:ring-amber-300/20">
                    </div>

                    <div class="grid gap-2">
                        <label for="password" class="text-sm font-medium">Senha</label>
                        <input id="password" name="password" type="password" autocomplete="current-password"
                               aria-describedby="login-errors" maxlength="1024" required
                               class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-3 text-base outline-none transition focus:border-amber-300 focus:ring-2 focus:ring-amber-300/20">
                    </div>

                    <button type="submit" data-attr:disabled="$submitting"
                            class="mt-1 flex min-h-12 items-center justify-center rounded-lg bg-amber-300 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-amber-200 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-amber-300 disabled:cursor-wait disabled:opacity-60">
                        <span data-text="$submitting ? 'Entrando…' : 'Entrar'">Entrar</span>
                    </button>
                </form>
            </div>
            <p class="mt-6 text-center text-xs leading-5 text-slate-500">Acesso restrito a usuários autorizados.</p>
        </section>
    </main>
</x-layouts.app>

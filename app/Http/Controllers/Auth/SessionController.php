<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Putyourlightson\Datastar\Services\Sse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse|Response|StreamedResponse
    {
        if ($request->user()) {
            return $this->navigate($request, route('dashboard', absolute: false));
        }

        $validator = Validator::make($request->only('username', 'password'), [
            'username' => ['required', 'string', 'max:64', 'regex:/\A[a-zA-Z0-9._-]+\z/'],
            'password' => ['required', 'string', 'max:1024'],
        ], [
            'username.required' => 'Informe seu nome de usuário.',
            'username.string' => 'Informe um nome de usuário válido.',
            'username.max' => 'O nome de usuário deve ter no máximo 64 caracteres.',
            'username.regex' => 'Use apenas letras, números, ponto, hífen ou sublinhado no usuário.',
            'password.required' => 'Informe sua senha.',
            'password.string' => 'Informe uma senha válida.',
            'password.max' => 'A senha deve ter no máximo 1024 caracteres.',
        ]);

        if ($validator->fails()) {
            return $this->reject($request, $validator->errors()->all());
        }

        $credentials = $validator->validated();
        $credentials['username'] = Str::lower($credentials['username']);
        $key = 'login:'.hash('sha256', $credentials['username'].'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return $this->reject(
                $request,
                ["Muitas tentativas. Aguarde {$seconds} segundos e tente novamente."],
                $seconds,
            );
        }

        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($key, 60);

            return $this->reject($request, ['Usuário ou senha inválidos.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return $this->navigate($request, route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse|StreamedResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->navigate($request, route('login', absolute: false));
    }

    private function navigate(Request $request, string $uri): RedirectResponse|StreamedResponse
    {
        if ($request->header('Datastar-Request') === 'true') {
            return (new Sse)->location($uri)->getEventStream();
        }

        return redirect($uri);
    }

    /**
     * @param  list<string>  $messages
     */
    private function reject(Request $request, array $messages, ?int $retryAfter = null): RedirectResponse|Response|StreamedResponse
    {
        if ($request->header('Datastar-Request') === 'true') {
            $response = (new Sse)
                ->patchElements(view('auth.partials.login-errors', compact('messages'))->render())
                ->getEventStream();

            if ($retryAfter !== null) {
                $response->headers->set('Retry-After', (string) $retryAfter);
            }

            return $response;
        }

        $username = is_string($request->input('username')) ? $request->input('username') : '';

        if ($retryAfter !== null) {
            return response()
                ->view('auth.login', compact('messages', 'username'), 429)
                ->header('Retry-After', (string) $retryAfter);
        }

        return redirect()->route('login')
            ->withErrors(['username' => $messages])
            ->withInput(['username' => $username]);
    }
}

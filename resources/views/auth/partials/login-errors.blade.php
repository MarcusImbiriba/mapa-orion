<div id="login-errors" role="alert" aria-live="polite" aria-atomic="true">
    @if ($messages !== [])
        <ul class="grid gap-1 rounded-xl border border-red-400/30 bg-red-400/10 px-4 py-3 text-sm text-red-200">
            @foreach ($messages as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @endif
</div>

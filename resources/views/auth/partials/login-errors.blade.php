<div id="login-errors" role="alert" aria-live="polite" aria-atomic="true">
    @if ($messages !== [])
        <ul class="mb-5 grid gap-1 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            @foreach ($messages as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @endif
</div>

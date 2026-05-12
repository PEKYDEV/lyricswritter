<x-layouts.auth>
    <div class="min-h-screen flex items-center justify-center py-10"
        style="background: var(--c-bg);">
        <div class="w-full max-w-sm mx-4">

            <div class="text-center mb-8">
                <span class="text-xs tracking-widest uppercase" style="color: var(--c-faint);">lyricwriter</span>
            </div>

            <div class="p-7" style="background: var(--c-panel); border: 1px solid var(--c-border);">

                @if ($errors->any())
                    <div class="mb-5 text-sm" style="color: #b84040;">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <div class="mb-1">
                        <label class="block text-xs mb-1.5" style="color: var(--c-muted);">Felhasználónév</label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            autofocus
                            autocomplete="username"
                            maxlength="30"
                            class="w-full px-3 py-2 text-sm outline-none transition-fast"
                            style="background: var(--c-bg); border: 1px solid var(--c-border); color: var(--c-text);"
                        >
                    </div>
                    <div class="mb-4 text-[10px]" style="color: var(--c-faint);">
                        3–30 karakter, csak betű, szám, _ és -
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs mb-1.5" style="color: var(--c-muted);">Jelszó</label>
                        <input
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            class="w-full px-3 py-2 text-sm outline-none transition-fast"
                            style="background: var(--c-bg); border: 1px solid var(--c-border); color: var(--c-text);"
                        >
                    </div>

                    <div class="mb-6">
                        <label class="block text-xs mb-1.5" style="color: var(--c-muted);">Jelszó megerősítése</label>
                        <input
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            class="w-full px-3 py-2 text-sm outline-none transition-fast"
                            style="background: var(--c-bg); border: 1px solid var(--c-border); color: var(--c-text);"
                        >
                    </div>

                    <button
                        type="submit"
                        class="w-full py-2 text-sm font-medium transition-fast hover:opacity-80"
                        style="background: var(--c-active-bg); color: var(--c-active-text);"
                    >
                        Regisztráció
                    </button>
                </form>
            </div>

            <div class="mt-4 text-center text-xs" style="color: var(--c-muted);">
                Van már fiókod?
                <a href="{{ route('login') }}" class="underline" style="color: var(--c-text);">Bejelentkezés</a>
            </div>

        </div>
    </div>
</x-layouts.auth>

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

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-xs mb-1.5" style="color: var(--c-muted);">Felhasználónév</label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            autofocus
                            autocomplete="username"
                            class="w-full px-3 py-2 text-sm outline-none transition-fast"
                            style="background: var(--c-bg); border: 1px solid var(--c-border); color: var(--c-text);"
                        >
                    </div>

                    <div class="mb-5">
                        <label class="block text-xs mb-1.5" style="color: var(--c-muted);">Jelszó</label>
                        <input
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            class="w-full px-3 py-2 text-sm outline-none transition-fast"
                            style="background: var(--c-bg); border: 1px solid var(--c-border); color: var(--c-text);"
                        >
                    </div>

                    <div class="mb-6 flex items-center gap-2">
                        <input type="checkbox" name="remember" id="remember" value="1"
                            class="cursor-pointer">
                        <label for="remember" class="text-xs cursor-pointer" style="color: var(--c-muted);">
                            Emlékezz rám
                        </label>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-2 text-sm font-medium transition-fast hover:opacity-80"
                        style="background: var(--c-active-bg); color: var(--c-active-text);"
                    >
                        Bejelentkezés
                    </button>
                </form>
            </div>

            <div class="mt-4 text-center text-xs" style="color: var(--c-muted);">
                Nincs fiókod?
                <a href="{{ route('register') }}" class="underline" style="color: var(--c-text);">Regisztráció</a>
            </div>

        </div>
    </div>
</x-layouts.auth>

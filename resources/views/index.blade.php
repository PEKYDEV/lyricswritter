<x-layouts.app>
    <div
        class="flex w-screen overflow-hidden"
        style="height: 100dvh;"
        x-data="editorApp"
        x-init="init()"
        style="background: var(--c-bg); color: var(--c-text);"
    >
        {{-- Sötét overlay (csak mobilon, ha az oldalsáv nyitva van) --}}
        <div
            x-show="sidebarOpen"
            x-transition:enter="transition-opacity duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-40 md:hidden"
            style="background: rgba(0,0,0,0.4);"
            @click="sidebarOpen = false"
        ></div>

        @include('partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            @include('partials.toolbar')

            {{-- Üres állapot --}}
            <div
                x-show="!activeSongId"
                class="flex-1 flex items-center justify-center text-sm"
                style="color: var(--c-faint);"
            >
                <div class="text-center">
                    <div class="text-3xl mb-3" style="opacity: 0.2;">&#9835;</div>
                    <div>Válassz egy dalt a listából, vagy hozz létre újat.</div>
                </div>
            </div>

            {{-- Szerkesztő --}}
            <div x-show="activeSongId" class="flex-1 flex flex-col min-h-0 overflow-hidden">
                @include('partials.editor')
            </div>

        </div>

        @include('partials.version-panel')
    </div>
</x-layouts.app>

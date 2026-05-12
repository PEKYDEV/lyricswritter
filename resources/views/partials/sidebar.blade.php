{{-- Bal oldalsáv --}}
{{-- Mobilon: fixed overlay, hamburgerrel nyitható/zárható --}}
{{-- Desktopon: mindig látható, statikus --}}
<aside
    :class="{ '-translate-x-full': !sidebarOpen }"
    class="fixed inset-y-0 left-0 z-50 w-[250px] flex flex-col h-full
           transition-transform duration-200 ease-in-out
           md:static md:translate-x-0 md:z-auto md:flex-shrink-0"
    style="background: var(--c-sidebar); border-right: 1px solid var(--c-border);">

    {{-- Új dal gomb --}}
    <div class="p-3" style="border-bottom: 1px solid var(--c-border);">
        <button
            @click="createNewSong()"
            class="w-full px-3 py-2 text-sm font-medium hover:opacity-75 transition-fast"
            style="background: var(--c-active-bg); color: var(--c-active-text);"
        >
            + Új dal
        </button>
    </div>

    {{-- Dalok listája --}}
    <div class="flex-1 overflow-y-auto">
        <template x-if="songs.length === 0">
            <div class="p-4 text-xs text-center" style="color: var(--c-faint);">
                Még nincsenek dalok.<br>Hozz létre egyet!
            </div>
        </template>

        <template x-for="song in songs" :key="song.id">
            <div
                class="group relative px-3 py-2.5 cursor-pointer transition-fast"
                :style="activeSongId === song.id
                    ? 'background: var(--c-active-bg); color: var(--c-active-text); border-bottom: 1px solid transparent;'
                    : 'border-bottom: 1px solid var(--c-border);'"
                @mouseenter="$event.currentTarget.style.background = activeSongId === song.id ? 'var(--c-active-bg)' : 'var(--c-hover)'"
                @mouseleave="$event.currentTarget.style.background = activeSongId === song.id ? 'var(--c-active-bg)' : 'var(--c-sidebar)'"
                @click="selectSong(song.id)"
            >
                <div class="text-sm truncate pr-6" x-text="song.title"></div>
                <div
                    class="text-[10px] mt-0.5 truncate"
                    :style="activeSongId === song.id ? 'opacity: 0.55;' : 'color: var(--c-muted);'"
                    x-text="formatDate(song.updated_at)"
                ></div>

                {{-- "..." kontextus menü gomb --}}
                <button
                    class="absolute right-2 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-fast"
                    :style="activeSongId === song.id ? 'color: var(--c-active-text); opacity: 0.6;' : 'color: var(--c-muted);'"
                    @click.stop="$dispatch('song-context', { id: song.id, title: song.title })"
                    title="Műveletek"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="5" r="1.2" fill="currentColor" stroke="none"/>
                        <circle cx="12" cy="12" r="1.2" fill="currentColor" stroke="none"/>
                        <circle cx="12" cy="19" r="1.2" fill="currentColor" stroke="none"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

    {{-- Lábléc: felhasználó + sötét mód toggle --}}
    <div class="px-3 py-2.5 flex items-center gap-2" style="border-top: 1px solid var(--c-border);">
        {{-- Felhasználónév + kijelentkezés --}}
        <div class="flex-1 min-w-0">
            <form method="POST" action="{{ route('logout') }}" class="flex items-center gap-1.5 group">
                @csrf
                <span class="text-xs truncate" style="color: var(--c-muted);">{{ auth()->user()->name }}</span>
                <button
                    type="submit"
                    class="text-[10px] opacity-0 group-hover:opacity-60 hover:!opacity-100 transition-fast shrink-0"
                    style="color: var(--c-muted);"
                    title="Kijelentkezés"
                >
                    ki
                </button>
            </form>
        </div>

        {{-- Sötét mód toggle --}}
        <button
            @click="toggleDarkMode()"
            class="w-7 h-7 flex items-center justify-center shrink-0 transition-fast"
            style="color: var(--c-muted);"
            :title="darkMode ? 'Fehér mód' : 'Sötét mód'"
        >
            <template x-if="!darkMode">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                </svg>
            </template>
            <template x-if="darkMode">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square">
                    <circle cx="12" cy="12" r="5"/>
                    <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
                    <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                </svg>
            </template>
        </button>
    </div>
</aside>

{{-- Kontextus modal (átnevezés/törlés) --}}
<div
    x-data="{ open: false, songId: null, songTitle: '' }"
    @song-context.window="open = true; songId = $event.detail.id; songTitle = $event.detail.title"
>
    <template x-if="open">
        <div
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0,0,0,0.25);"
            @click.self="open = false"
        >
            <div class="p-4 w-64" style="background: var(--c-sidebar); border: 1px solid var(--c-border);">
                <div class="text-xs font-semibold mb-3 truncate" style="color: var(--c-text);" x-text="songTitle"></div>
                <div class="flex flex-col gap-0.5">
                    <button
                        class="text-left text-sm px-3 py-2 transition-fast"
                        style="color: var(--c-text);"
                        @mouseenter="$el.style.background = 'var(--c-hover)'"
                        @mouseleave="$el.style.background = 'transparent'"
                        @click="
                            const newTitle = prompt('Új cím:', songTitle);
                            if (newTitle && newTitle.trim()) {
                                fetch('/api/songs/' + songId, {
                                    method: 'PUT',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                                    body: JSON.stringify({ title: newTitle.trim() })
                                }).then(r => r.json()).then(updated => {
                                    const s = songs.find(x => x.id === songId);
                                    if (s) s.title = updated.title;
                                    if (activeSongId === songId) title = updated.title;
                                });
                            }
                            open = false;
                        "
                    >Átnevezés</button>
                    <button
                        class="text-left text-sm px-3 py-2 transition-fast text-red-700"
                        @mouseenter="$el.style.background = 'var(--c-hover)'"
                        @mouseleave="$el.style.background = 'transparent'"
                        @click="deleteSong(songId); open = false"
                    >Törlés</button>
                    <button
                        class="text-left text-sm px-3 py-2 transition-fast"
                        style="color: var(--c-muted);"
                        @mouseenter="$el.style.background = 'var(--c-hover)'"
                        @mouseleave="$el.style.background = 'transparent'"
                        @click="open = false"
                    >Mégsem</button>
                </div>
            </div>
        </div>
    </template>
</div>

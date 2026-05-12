{{-- Verzió-történet panel --}}
<template x-if="showVersionPanel">
    <div class="fixed inset-0 z-40 flex justify-end">
        <div class="absolute inset-0" style="background: rgba(0,0,0,0.2);" @click="showVersionPanel = false"></div>

        <div class="relative z-50 w-80 h-full flex flex-col overflow-hidden"
            style="background: var(--c-sidebar); border-left: 1px solid var(--c-border);">

            {{-- Fejléc --}}
            <div class="flex items-center justify-between px-4 py-3"
                style="border-bottom: 1px solid var(--c-border);">
                <span class="text-sm font-semibold" style="color: var(--c-text);">Verzió-történet</span>
                <button
                    @click="showVersionPanel = false"
                    class="w-6 h-6 flex items-center justify-center transition-fast"
                    style="color: var(--c-muted);"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            {{-- Előnézet --}}
            <template x-if="versionPreview">
                <div class="p-4" style="border-bottom: 1px solid var(--c-border); background: var(--c-panel);">
                    <div class="text-[10px] mb-1" style="color: var(--c-faint);">Előnézet</div>
                    <div
                        class="text-xs font-mono leading-relaxed max-h-32 overflow-y-auto"
                        style="color: var(--c-text);"
                        x-html="versionPreview.content ? versionPreview.content.replace(/<[^>]+>/g, ' ').substring(0, 300) + '...' : '(üres)'"
                    ></div>
                    <button
                        @click="restoreVersion(versionPreview.id)"
                        class="mt-2 w-full py-1.5 text-xs hover:opacity-75 transition-fast"
                        style="background: var(--c-active-bg); color: var(--c-active-text);"
                    >Visszaállítás</button>
                    <button
                        @click="versionPreview = null"
                        class="mt-1 w-full py-1 text-xs transition-fast"
                        style="color: var(--c-muted);"
                    >Bezár</button>
                </div>
            </template>

            {{-- Lista --}}
            <div class="flex-1 overflow-y-auto">
                <template x-if="versions.length === 0">
                    <div class="p-4 text-xs text-center" style="color: var(--c-faint);">
                        Még nincs mentett verzió.
                    </div>
                </template>

                <template x-for="version in versions" :key="version.id">
                    <div
                        class="px-4 py-3 cursor-pointer transition-fast"
                        style="border-bottom: 1px solid var(--c-border);"
                        @mouseenter="$el.style.background = 'var(--c-hover)'"
                        @mouseleave="$el.style.background = 'transparent'"
                        @click="versionPreview = version"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-medium truncate" style="color: var(--c-text);"
                                    x-text="version.label || version.title || 'Cím nélküli dal'"></div>
                                <div class="text-[10px] mt-0.5" style="color: var(--c-muted);"
                                    x-text="formatDate(version.created_at)"></div>
                            </div>
                            <span
                                x-show="version.label && version.label !== 'auto'"
                                class="text-[9px] px-1.5 py-0.5 flex-shrink-0"
                                style="background: var(--c-active-bg); color: var(--c-active-text);"
                                x-text="version.label"
                            ></span>
                        </div>
                        <div
                            class="text-[10px] mt-1 line-clamp-2 leading-relaxed"
                            style="color: var(--c-faint);"
                            x-text="version.snippet ? version.snippet.replace(/<[^>]+>/g, ' ').trim().substring(0, 80) : '(üres)'"
                        ></div>
                    </div>
                </template>
            </div>

            {{-- Új verzió --}}
            <div class="p-3" style="border-top: 1px solid var(--c-border);">
                <button
                    @click="saveVersion()"
                    class="w-full py-2 text-xs hover:opacity-75 transition-fast"
                    style="background: var(--c-active-bg); color: var(--c-active-text);"
                >+ Új verzió mentése</button>
            </div>
        </div>
    </div>
</template>

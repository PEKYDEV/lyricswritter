{{-- Felső toolbar --}}
{{-- Mobilon görgethető, a cím fix széles, a vezérlők jobbra húzódnak --}}
<header class="flex items-center h-11 gap-1.5 flex-shrink-0 overflow-x-auto"
    style="background: var(--c-sidebar); border-bottom: 1px solid var(--c-border);
           scrollbar-width: none; -webkit-overflow-scrolling: touch; padding: 0 8px;">

    {{-- Hamburger (csak mobilon) --}}
    <button
        @click="toggleSidebar()"
        class="md:hidden w-7 h-7 flex items-center justify-center flex-shrink-0 transition-fast"
        style="color: var(--c-muted);"
        title="Dal lista"
        @mouseenter="$el.style.color = 'var(--c-text)'"
        @mouseleave="$el.style.color = 'var(--c-muted)'"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square">
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    {{-- Cím szerkesztő: mobilon fix széles, desktopon flex-1 --}}
    <div class="w-[140px] md:flex-1 md:w-auto md:min-w-0 flex-shrink-0">
        <input
            type="text"
            x-model="title"
            @change="onTitleChange()"
            @keydown.enter.prevent="$el.blur()"
            class="w-full text-sm font-medium outline-none transition-fast px-0 py-0.5 truncate"
            style="background: transparent; color: var(--c-text); border-bottom: 1px solid transparent;"
            @focus="$el.style.borderBottomColor = 'var(--c-muted)'"
            @blur="$el.style.borderBottomColor = 'transparent'"
            placeholder="Cím nélküli dal"
        >
    </div>

    <span class="toolbar-sep"></span>

    {{-- Dőlt --}}
    <button
        @click="toggleItalic()"
        class="w-7 h-7 flex items-center justify-center flex-shrink-0 transition-fast font-serif text-sm italic"
        style="color: var(--c-muted);"
        @mouseenter="$el.style.background = 'var(--c-hover)'; $el.style.color = 'var(--c-text)'"
        @mouseleave="$el.style.background = 'transparent'; $el.style.color = 'var(--c-muted)'"
        title="Dőlt (Ctrl+I)"
    >I</button>

    {{-- ( ) zárójel --}}
    <button
        @click="insertParentheses()"
        class="w-7 h-7 flex items-center justify-center flex-shrink-0 transition-fast text-xs"
        style="color: var(--c-muted);"
        @mouseenter="$el.style.background = 'var(--c-hover)'; $el.style.color = 'var(--c-text)'"
        @mouseleave="$el.style.background = 'transparent'; $el.style.color = 'var(--c-muted)'"
        title="Zárójel — kihagyott rész"
    >( )</button>

    {{-- Belső rím toggle --}}
    <button
        @click="toggleInternalRhymes()"
        class="px-2 h-7 flex items-center justify-center flex-shrink-0 text-xs transition-fast"
        :style="internalRhymesActive
            ? 'background: var(--c-active-bg); color: var(--c-active-text);'
            : 'color: var(--c-muted);'"
        @mouseenter="if (!internalRhymesActive) { $el.style.background = 'var(--c-hover)'; $el.style.color = 'var(--c-text)'; }"
        @mouseleave="if (!internalRhymesActive) { $el.style.background = 'transparent'; $el.style.color = 'var(--c-muted)'; }"
        title="Belső rímek kiemelése"
    >rím</button>

    {{-- Rímséma toggle --}}
    <button
        @click="toggleRhymeScheme()"
        class="px-2 h-7 flex items-center justify-center flex-shrink-0 text-xs transition-fast"
        :style="showRhymeScheme
            ? 'background: var(--c-active-bg); color: var(--c-active-text);'
            : 'color: var(--c-muted);'"
        @mouseenter="if (!showRhymeScheme) { $el.style.background = 'var(--c-hover)'; $el.style.color = 'var(--c-text)'; }"
        @mouseleave="if (!showRhymeScheme) { $el.style.background = 'transparent'; $el.style.color = 'var(--c-muted)'; }"
        title="Rímséma megjelenítése"
    >ABAB</button>

    <span class="toolbar-sep"></span>

    {{-- BPM --}}
    <div class="flex items-center gap-1 text-xs flex-shrink-0">
        <span class="text-[10px]" style="color: var(--c-muted);">BPM</span>
        <input
            type="number"
            x-model.number="bpm"
            @change="runAnalysis()"
            min="20" max="300"
            class="w-12 text-center text-xs py-0.5 outline-none transition-fast"
            style="background: var(--c-input-bg); color: var(--c-text); border: 1px solid var(--c-border);"
            @focus="$el.style.borderColor = 'var(--c-muted)'"
            @blur="$el.style.borderColor = 'var(--c-border)'"
        >
    </div>

    {{-- Osztó --}}
    <select
        x-model="bpmDivisor"
        @change="runAnalysis()"
        class="text-xs py-0.5 px-1 outline-none transition-fast cursor-pointer flex-shrink-0"
        style="background: var(--c-input-bg); color: var(--c-text); border: 1px solid var(--c-border);"
        title="Szótag/ütem"
    >
        <option value="1/4">1/4</option>
        <option value="1/8">1/8</option>
        <option value="1/16">1/16</option>
    </select>

    {{-- Ütem/sor --}}
    <div class="flex items-center gap-1 text-xs flex-shrink-0">
        <span class="text-[10px]" style="color: var(--c-muted);">ü/sor</span>
        <select
            x-model.number="beatsPerLine"
            @change="runAnalysis()"
            class="text-xs py-0.5 px-1 outline-none transition-fast cursor-pointer"
            style="background: var(--c-input-bg); color: var(--c-text); border: 1px solid var(--c-border);"
            title="Ütem/sor"
        >
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="4">4</option>
            <option value="8">8</option>
        </select>
    </div>

    <span class="toolbar-sep"></span>

    {{-- Verziók --}}
    <button
        @click="openVersionPanel()"
        class="px-2 h-7 text-xs flex items-center gap-1 flex-shrink-0 transition-fast"
        style="color: var(--c-muted);"
        @mouseenter="$el.style.background = 'var(--c-hover)'; $el.style.color = 'var(--c-text)'"
        @mouseleave="$el.style.background = 'transparent'; $el.style.color = 'var(--c-muted)'"
        title="Verzió-történet"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square">
            <polyline points="1 4 1 10 7 10"/>
            <path d="M3.51 15a9 9 0 1 0 .49-3.51"/>
        </svg>
        Verziók
    </button>

    {{-- Verzió mentés --}}
    <button
        @click="saveVersion()"
        class="px-2 h-7 text-xs flex items-center gap-1 flex-shrink-0 transition-fast"
        style="color: var(--c-muted);"
        @mouseenter="$el.style.background = 'var(--c-hover)'; $el.style.color = 'var(--c-text)'"
        @mouseleave="$el.style.background = 'transparent'; $el.style.color = 'var(--c-muted)'"
        title="Verzió mentése"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square">
            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
            <polyline points="17 21 17 13 7 13 7 21"/>
            <polyline points="7 3 7 8 15 8"/>
        </svg>
        Mentés
    </button>

    {{-- Export dropdown --}}
    <div class="relative flex-shrink-0" x-data="{ exportOpen: false }">
        <button
            @click="exportOpen = !exportOpen"
            @click.outside="exportOpen = false"
            class="px-2 h-7 text-xs flex items-center gap-1 transition-fast"
            style="color: var(--c-muted);"
            @mouseenter="$el.style.background = 'var(--c-hover)'; $el.style.color = 'var(--c-text)'"
            @mouseleave="$el.style.background = 'transparent'; $el.style.color = 'var(--c-muted)'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="7 10 12 15 17 10"/>
                <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            Export
        </button>

        <template x-if="exportOpen">
            <div class="dropdown-menu">
                <button
                    @click="exportTxt(); exportOpen = false"
                    class="w-full text-left px-3 py-2 text-sm transition-fast"
                    style="color: var(--c-text);"
                    @mouseenter="$el.style.background = 'var(--c-hover)'"
                    @mouseleave="$el.style.background = 'transparent'"
                >TXT export</button>
                <button
                    @click="exportPdf(); exportOpen = false"
                    class="w-full text-left px-3 py-2 text-sm transition-fast"
                    style="color: var(--c-text);"
                    @mouseenter="$el.style.background = 'var(--c-hover)'"
                    @mouseleave="$el.style.background = 'transparent'"
                >PDF export</button>
            </div>
        </template>
    </div>

    {{-- Mentés státusz --}}
    <div class="text-[10px] w-14 text-right flex-shrink-0">
        <span x-show="saveStatus === 'saving'" style="color: var(--c-muted);">Mentés...</span>
        <span x-show="saveStatus === 'saved'" style="color: #6a9a5a;">Mentve</span>
        <span x-show="saveStatus === 'error'" style="color: #b84040;">Hiba</span>
    </div>

</header>

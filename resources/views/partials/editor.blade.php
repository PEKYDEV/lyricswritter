{{-- Szerkesztő --}}
<div class="flex-1 overflow-y-auto relative" id="editor-scroll" style="background: var(--c-bg);">
    <div class="flex min-h-full">

        {{-- Szerkeszthető tartalom (a sorszám + szótagszám CSS ::before / ::after via data-attr) --}}
        <div
            id="lyric-editor"
            contenteditable="true"
            spellcheck="false"
            class="flex-1 p-6 outline-none"
            @input="onEditorInput()"
            @keydown="onKeyDown($event)"
            @paste.prevent="onPaste($event)"
        ></div>

        {{-- Rímséma jobb oldali oszlop (csak ha aktív) --}}
        <div
            x-show="showRhymeScheme"
            class="flex-shrink-0 pt-6 pb-6 pr-4 flex flex-col"
            style="width: 28px;"
            aria-hidden="true"
        >
            <template x-for="(stat, i) in lineStats" :key="i">
                <div
                    class="flex items-baseline justify-center"
                    style="line-height: 1.7; font-size: 15px; min-height: 1.7em;"
                >
                    <span class="rhyme-badge" x-text="stat.rhyme"></span>
                </div>
            </template>
        </div>

    </div>
</div>

{{-- Alsó státuszsor --}}
<footer class="flex items-center gap-4 px-6 py-1.5 flex-shrink-0"
    style="border-top: 1px solid var(--c-border); color: var(--c-muted); font-size: 10px; background: var(--c-sidebar);">
    <span><span x-text="totalSyllables"></span> szótag</span>
    <span><span x-text="totalLines"></span> sor</span>
    <span><span x-text="totalWords"></span> szó</span>
    <span x-show="bpm > 0 && totalSyllables > 0">
        ~<span x-text="estimatedDuration"></span> a megadott tempón
    </span>
    <span class="ml-auto" x-show="bpm > 0">
        Ajánlott: <span x-text="recommendedSyllablesLabel()"></span> szótag/sor
    </span>
</footer>

import { countLineSyllables } from './syllable-counter.js';
import { analyzeRhymeScheme, findInternalRhymes, applyInternalRhymeHighlights } from './rhyme-analyzer.js';
import { recommendedSyllables, rateSyllableCount, estimateDuration } from './bpm-helper.js';

const AUTOSAVE_DELAY = 3000;
const ANALYSIS_DELAY = 80;

// Debounce segédfüggvény
function debounce(fn, delay) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

export function createEditorApp() {
    return {
        // ---- Dal adatok ----
        songs: [],
        activeSongId: null,
        title: 'Cím nélküli dal',
        bpm: 90,
        bpmDivisor: '1/8',
        beatsPerLine: 4,
        saveStatus: '', // '', 'saving', 'saved', 'error'

        // ---- UI állapot ----
        sidebarOpen: false,
        showVersionPanel: false,
        showRhymeScheme: false,
        internalRhymesActive: false,
        darkMode: false,
        versions: [],
        versionPreview: null,

        // ---- Analízis eredmény ----
        lineStats: [], // [{syllables, rhyme, rating}]
        totalSyllables: 0,
        totalWords: 0,
        totalLines: 0,
        estimatedDuration: '0:00',

        // ---- Belső állapot ----
        _autosaveTimer: null,
        _analysisTimer: null,
        _lastVersionTime: null,
        _hasChanges: false,

        // ====== Inicializálás ======
        init() {
            this.darkMode = localStorage.getItem('lyricwriter-dark') === 'true';
            this.applyDarkMode();
            this.loadSongs();
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        applyDarkMode() {
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        },

        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            localStorage.setItem('lyricwriter-dark', this.darkMode);
            this.applyDarkMode();
        },

        // ====== Dal kezelés ======
        async loadSongs() {
            try {
                const res = await fetch('/api/songs', { headers: this.getHeaders() });
                if (this.handleFetchError(res)) return;
                this.songs = await res.json();

                if (this.songs.length > 0) {
                    await this.selectSong(this.songs[0].id);
                }
            } catch (e) {
                console.error('Betöltési hiba:', e);
            }
        },

        async selectSong(id) {
            try {
                const res = await fetch(`/api/songs/${id}`, { headers: this.getHeaders() });
                if (this.handleFetchError(res)) return;
                const song = await res.json();
                this.activeSongId = song.id;
                this.title = song.title;
                this.bpm = song.bpm ?? 90;
                this._hasChanges = false;
                if (window.innerWidth < 768) {
                    this.sidebarOpen = false;
                }
                // nextTick: megvárjuk, hogy az x-show megjelenítse a szerkesztőt,
                // csak utána írjuk be a tartalmat és futtatjuk az elemzést
                this.$nextTick(() => {
                    this.setEditorContent(song.content ?? '');
                    this.$nextTick(() => this.runAnalysis());
                });
            } catch (e) {
                console.error('Dal betöltési hiba:', e);
            }
        },

        async createNewSong() {
            try {
                const res = await fetch('/api/songs', {
                    method: 'POST',
                    headers: this.getHeaders(),
                    body: JSON.stringify({ title: 'Cím nélküli dal', content: '', bpm: null }),
                });
                const song = await res.json();
                this.songs.unshift(song);
                await this.selectSong(song.id);
            } catch (e) {
                console.error('Létrehozási hiba:', e);
            }
        },

        async deleteSong(id) {
            if (!confirm('Biztosan törlöd ezt a dalt?')) {
                return;
            }
            try {
                await fetch(`/api/songs/${id}`, {
                    method: 'DELETE',
                    headers: this.getHeaders(),
                });
                this.songs = this.songs.filter((s) => s.id !== id);
                if (this.activeSongId === id) {
                    this.activeSongId = null;
                    this.title = '';
                    this.clearEditor();
                    if (this.songs.length > 0) {
                        await this.selectSong(this.songs[0].id);
                    }
                }
            } catch (e) {
                console.error('Törlési hiba:', e);
            }
        },

        // ====== Mentés ======
        getHeaders() {
            return {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            };
        },

        handleFetchError(res) {
            if (res.status === 401) {
                window.location.href = '/login';
                return true;
            }
            return false;
        },

        scheduleAutosave() {
            this._hasChanges = true;
            clearTimeout(this._autosaveTimer);
            this._autosaveTimer = setTimeout(() => this.save(), AUTOSAVE_DELAY);
        },

        async save() {
            if (!this.activeSongId) {
                return;
            }
            this.saveStatus = 'saving';
            try {
                const content = this.getEditorContent();
                const res = await fetch(`/api/songs/${this.activeSongId}`, {
                    method: 'PUT',
                    headers: this.getHeaders(),
                    body: JSON.stringify({
                        title: this.title,
                        content,
                        bpm: this.bpm || null,
                    }),
                });
                if (this.handleFetchError(res)) return;
                if (!res.ok) {
                    throw new Error('Szerverhiba');
                }
                const updated = await res.json();
                this.saveStatus = 'saved';
                this._hasChanges = false;

                // Szükség esetén verzió auto-mentés (1 óra elteltével)
                this.maybeAutoSaveVersion(content, updated.title);

                // Dal lista frissítése
                const idx = this.songs.findIndex((s) => s.id === this.activeSongId);
                if (idx !== -1) {
                    this.songs[idx] = { ...this.songs[idx], title: updated.title, updated_at: updated.updated_at };
                }

                setTimeout(() => {
                    if (this.saveStatus === 'saved') {
                        this.saveStatus = '';
                    }
                }, 2000);
            } catch (e) {
                this.saveStatus = 'error';
                console.error('Mentési hiba:', e);
            }
        },

        async maybeAutoSaveVersion(content, title) {
            const oneHour = 60 * 60 * 1000;
            const now = Date.now();

            if (!this._lastVersionTime || now - this._lastVersionTime > oneHour) {
                try {
                    await fetch(`/api/songs/${this.activeSongId}/versions`, {
                        method: 'POST',
                        headers: this.getHeaders(),
                        body: JSON.stringify({ label: 'auto' }),
                    });
                    this._lastVersionTime = now;
                } catch (e) {
                    console.error('Auto-verzió hiba:', e);
                }
            }
        },

        // ====== Cím szerkesztés ======
        onTitleChange() {
            this.scheduleAutosave();
        },

        // ====== Verziók ======
        async openVersionPanel() {
            this.showVersionPanel = true;
            await this.loadVersions();
        },

        async loadVersions() {
            if (!this.activeSongId) {
                return;
            }
            try {
                const res = await fetch(`/api/songs/${this.activeSongId}/versions`);
                this.versions = await res.json();
            } catch (e) {
                console.error('Verziólista hiba:', e);
            }
        },

        async saveVersion() {
            const label = prompt('Verzió neve (pl. v1, demo, final):') ?? '';
            if (!this.activeSongId) {
                return;
            }
            // Autosave előtt
            await this.save();
            try {
                await fetch(`/api/songs/${this.activeSongId}/versions`, {
                    method: 'POST',
                    headers: this.getHeaders(),
                    body: JSON.stringify({ label: label.trim() || null }),
                });
                this._lastVersionTime = Date.now();
                if (this.showVersionPanel) {
                    await this.loadVersions();
                }
                alert('Verzió mentve!');
            } catch (e) {
                console.error('Verzió mentési hiba:', e);
            }
        },

        async restoreVersion(versionId) {
            if (!confirm('Biztosan visszaállítod ezt a verziót? Az aktuális állapot új verzióként mentésre kerül.')) {
                return;
            }
            try {
                const res = await fetch(`/api/songs/${this.activeSongId}/versions/${versionId}/restore`, {
                    method: 'POST',
                    headers: this.getHeaders(),
                });
                const song = await res.json();
                this.title = song.title;
                this.setEditorContent(song.content ?? '');
                this.runAnalysis();
                this.showVersionPanel = false;
                this._lastVersionTime = Date.now();
            } catch (e) {
                console.error('Visszaállítási hiba:', e);
            }
        },

        // ====== Editor DOM kezelés ======
        getEditorEl() {
            return document.getElementById('lyric-editor');
        },

        getEditorContent() {
            const el = this.getEditorEl();
            if (!el) {
                return '';
            }
            // Klónból menti, data-* attribútumok nélkül
            const clone = el.cloneNode(true);
            clone.querySelectorAll('[data-line]').forEach((div) => {
                delete div.dataset.line;
                delete div.dataset.syl;
                delete div.dataset.rating;
            });
            return clone.innerHTML;
        },

        setEditorContent(html) {
            const el = this.getEditorEl();
            if (!el) {
                return;
            }
            // Üres tartalom kezelés: egy üres sor legyen
            if (!html || html.trim() === '') {
                el.innerHTML = '<div><br></div>';
            } else {
                el.innerHTML = html;
            }
            this.ensureLineStructure();
            // Kurzort az első sor elejére helyezzük, hogy paste azonnal működjön
            el.focus();
            const firstDiv = el.querySelector(':scope > div');
            if (firstDiv) {
                const range = document.createRange();
                range.setStart(firstDiv, 0);
                range.collapse(true);
                const sel = window.getSelection();
                if (sel) {
                    sel.removeAllRanges();
                    sel.addRange(range);
                }
            }
        },

        clearEditor() {
            const el = this.getEditorEl();
            if (el) {
                el.innerHTML = '<div><br></div>';
            }
        },

        /**
         * Biztosítja, hogy az editor tartalmában minden sor
         * egy <div> elembe legyen csomagolva.
         */
        ensureLineStructure() {
            const el = this.getEditorEl();
            if (!el) {
                return;
            }

            // Gyors ellenőrzés: ha minden gyerek div, nem kell módosítani
            const children = Array.from(el.childNodes);
            const needsFix = children.some(
                (n) => n.nodeType === Node.TEXT_NODE || (n.nodeType === Node.ELEMENT_NODE && n.tagName !== 'DIV'),
            );

            if (!needsFix) {
                return;
            }

            // Szöveg node-okat és nem-div elemeket div-be csomagolja
            const lines = [];
            let currentLine = document.createElement('div');

            children.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE && node.tagName === 'DIV') {
                    if (currentLine.childNodes.length > 0) {
                        lines.push(currentLine);
                        currentLine = document.createElement('div');
                    }
                    lines.push(node.cloneNode(true));
                } else {
                    currentLine.appendChild(node.cloneNode(true));
                }
            });

            if (currentLine.childNodes.length > 0) {
                lines.push(currentLine);
            }

            el.innerHTML = '';
            lines.forEach((l) => el.appendChild(l));
        },

        getLineElements() {
            const el = this.getEditorEl();
            if (!el) {
                return [];
            }
            const divs = Array.from(el.querySelectorAll(':scope > div'));
            // Ha még nincs div (pl. Chrome első gépelés előtt), maga az editor az egyetlen "sor"
            if (divs.length === 0 && el.textContent.trim()) {
                return [el];
            }
            return divs;
        },

        // ====== Paste kezelés ======
        onPaste(event) {
            event.preventDefault();
            const text = event.clipboardData.getData('text/plain');
            if (!text) {
                return;
            }

            const lines = text.split(/\r?\n/);
            const sel = window.getSelection();
            if (!sel?.rangeCount) {
                return;
            }

            if (!sel.isCollapsed) {
                sel.deleteFromDocument();
            }

            // Egysoros beillesztés: insertText elegendő
            if (lines.length === 1) {
                if (lines[0]) {
                    document.execCommand('insertText', false, lines[0]);
                }
                this.onEditorInput();
                return;
            }

            // Többsoros beillesztés: direkt DOM-manipuláció, hogy mindig <div> keletkezzék
            // (execCommand('insertParagraph') böngészőnként <p> vagy <br> is lehet)
            const editorEl = this.getEditorEl();
            const range = sel.getRangeAt(0);

            let lineDiv = range.startContainer;
            while (lineDiv && lineDiv.parentElement !== editorEl) {
                lineDiv = lineDiv.parentElement;
            }

            if (!lineDiv || lineDiv === editorEl) {
                // Kurzor az editor root-ján van — az utolsó (vagy egyetlen) div-be illesztünk
                const divs = Array.from(editorEl.querySelectorAll(':scope > div'));
                const offset = range.startContainer === editorEl ? range.startOffset : 0;
                const targetDiv = divs[Math.min(offset, divs.length - 1)] ?? divs[divs.length - 1];

                if (targetDiv) {
                    if (lines[0]) {
                        targetDiv.appendChild(document.createTextNode(lines[0]));
                    }
                    if (!targetDiv.hasChildNodes()) {
                        targetDiv.appendChild(document.createElement('br'));
                    }
                    let prevDiv = targetDiv;
                    for (let i = 1; i < lines.length; i++) {
                        const newDiv = document.createElement('div');
                        newDiv.appendChild(lines[i] ? document.createTextNode(lines[i]) : document.createElement('br'));
                        prevDiv.insertAdjacentElement('afterend', newDiv);
                        prevDiv = newDiv;
                    }
                    const finalRange = document.createRange();
                    const lc = prevDiv.lastChild;
                    if (lc?.nodeType === Node.TEXT_NODE) {
                        finalRange.setStart(lc, lc.length);
                    } else if (lc) {
                        finalRange.setStartAfter(lc);
                    } else {
                        finalRange.setStart(prevDiv, 0);
                    }
                    finalRange.collapse(true);
                    sel.removeAllRanges();
                    sel.addRange(finalRange);
                } else {
                    lines.forEach((line) => {
                        const div = document.createElement('div');
                        div.appendChild(line ? document.createTextNode(line) : document.createElement('br'));
                        editorEl.appendChild(div);
                    });
                }
                this.onEditorInput();
                return;
            }

            // Kurzor utáni tartalmat kivágjuk az aktuális sorból
            const tailRange = document.createRange();
            tailRange.setStart(range.startContainer, range.startOffset);
            tailRange.setEnd(lineDiv, lineDiv.childNodes.length);
            const tailFragment = tailRange.extractContents();
            const tailHasText = tailFragment.textContent.length > 0;

            // Első sor szövegét az aktuális sor végéhez fűzzük (kurzor pozíciójára)
            if (lines[0]) {
                lineDiv.appendChild(document.createTextNode(lines[0]));
            }
            if (!lineDiv.hasChildNodes()) {
                lineDiv.appendChild(document.createElement('br'));
            }

            // Többi sor: új <div> elemként szúrjuk be
            let prevDiv = lineDiv;
            for (let i = 1; i < lines.length; i++) {
                const newDiv = document.createElement('div');

                if (lines[i]) {
                    newDiv.appendChild(document.createTextNode(lines[i]));
                }

                if (i === lines.length - 1 && tailHasText) {
                    newDiv.appendChild(tailFragment);
                }

                if (!newDiv.hasChildNodes()) {
                    newDiv.appendChild(document.createElement('br'));
                }

                prevDiv.insertAdjacentElement('afterend', newDiv);
                prevDiv = newDiv;
            }

            // Kurzort az utolsó beillesztett sor végére helyezzük
            const finalRange = document.createRange();
            const lastChild = prevDiv.lastChild;
            if (lastChild?.nodeType === Node.TEXT_NODE) {
                finalRange.setStart(lastChild, lastChild.length);
            } else if (lastChild) {
                finalRange.setStartAfter(lastChild);
            } else {
                finalRange.setStart(prevDiv, 0);
            }
            finalRange.collapse(true);
            sel.removeAllRanges();
            sel.addRange(finalRange);

            this.onEditorInput();
        },

        // ====== Billentyűzet ======
        onKeyDown(event) {
            if (event.key === 'Tab') {
                event.preventDefault();
                document.execCommand('insertText', false, '  ');
            }

            // Enter: natív helyett direkt DOM-manipuláció, hogy mobilon is
            // mindig <div> keletkezzék (iOS Safari <br>-t szúrhat be natívan)
            if (event.key === 'Enter') {
                event.preventDefault();
                const sel = window.getSelection();
                if (!sel?.rangeCount) {
                    return;
                }
                if (!sel.isCollapsed) {
                    sel.deleteFromDocument();
                }

                const editorEl = this.getEditorEl();
                const range = sel.getRangeAt(0);

                let lineDiv = range.startContainer;
                while (lineDiv && lineDiv.parentElement !== editorEl) {
                    lineDiv = lineDiv.parentElement;
                }
                if (!lineDiv || lineDiv === editorEl) {
                    return;
                }

                // Kurzor utáni tartalmat kivágjuk és új <div>-be visszük
                const tailRange = document.createRange();
                tailRange.setStart(range.startContainer, range.startOffset);
                tailRange.setEnd(lineDiv, lineDiv.childNodes.length);
                const tailFragment = tailRange.extractContents();

                const tailHasContent =
                    tailFragment.textContent.length > 0 ||
                    Array.from(tailFragment.childNodes).some((n) => n.tagName !== 'BR');

                const newDiv = document.createElement('div');
                if (tailHasContent) {
                    newDiv.appendChild(tailFragment);
                } else {
                    newDiv.appendChild(document.createElement('br'));
                }

                if (!lineDiv.hasChildNodes()) {
                    lineDiv.appendChild(document.createElement('br'));
                }

                lineDiv.insertAdjacentElement('afterend', newDiv);

                // Kurzort az új sor elejére helyezzük
                const newRange = document.createRange();
                const firstChild = newDiv.firstChild;
                if (firstChild?.nodeType === Node.TEXT_NODE) {
                    newRange.setStart(firstChild, 0);
                } else {
                    newRange.setStart(newDiv, 0);
                }
                newRange.collapse(true);
                sel.removeAllRanges();
                sel.addRange(newRange);

                this.onEditorInput();
            }
        },

        // ====== Input esemény ======
        onEditorInput() {
            this.scheduleAutosave();
            clearTimeout(this._analysisTimer);
            this._analysisTimer = setTimeout(() => this.runAnalysis(), ANALYSIS_DELAY);
        },

        // ====== Formázás ======
        insertParentheses() {
            const selection = window.getSelection();
            if (!selection.rangeCount) {
                return;
            }

            const range = selection.getRangeAt(0);
            const selectedText = range.toString();

            if (selectedText) {
                document.execCommand('insertText', false, `(${selectedText})`);
            } else {
                document.execCommand('insertText', false, '()');
                // Kurzort a zárójelek közé helyezzük
                const sel = window.getSelection();
                if (sel.rangeCount > 0) {
                    const r = sel.getRangeAt(0);
                    r.setStart(r.startContainer, r.startOffset - 1);
                    r.collapse(true);
                    sel.removeAllRanges();
                    sel.addRange(r);
                }
            }
        },

        toggleItalic() {
            document.execCommand('italic', false);
            this.getEditorEl()?.focus();
        },

        // ====== Analízis ======
        runAnalysis() {
            const lines = this.getLineElements();
            const rhymeScheme = this.showRhymeScheme ? analyzeRhymeScheme(lines) : [];
            const recommended = recommendedSyllables(this.bpm, this.bpmDivisor, this.beatsPerLine);

            let totalSyl = 0;
            let totalWords = 0;

            this.lineStats = lines.map((el, i) => {
                const syllables = countLineSyllables(el);
                const text = el.textContent.trim();
                const words = text ? text.split(/\s+/).filter(Boolean).length : 0;
                const rating = rateSyllableCount(syllables, recommended);

                // data-attribútumok közvetlen a sorelem-en — így nincs csúszás soha
                el.dataset.line = i + 1;
                el.dataset.syl = syllables > 0 ? syllables : '';
                el.dataset.rating = syllables > 0 ? rating : '';

                totalSyl += syllables;
                totalWords += words;

                return {
                    syllables,
                    rhyme: rhymeScheme[i] ?? '',
                    rating,
                };
            });

            this.totalSyllables = totalSyl;
            this.totalWords = totalWords;
            this.totalLines = lines.filter((el) => el.textContent.trim() !== '').length;
            this.estimatedDuration = estimateDuration(totalSyl, this.bpm, this.bpmDivisor);

            if (this.internalRhymesActive) {
                this.highlightInternalRhymes(lines);
            }
        },

        highlightInternalRhymes(lines) {
            // Eltávolítjuk a korábbi kiemeléseket
            document.querySelectorAll('.internal-rhyme').forEach((el) => {
                el.replaceWith(document.createTextNode(el.textContent));
            });

            const rhymeWords = findInternalRhymes(lines);

            lines.forEach((el) => {
                applyInternalRhymeHighlights(el, rhymeWords);
            });
        },

        toggleInternalRhymes() {
            this.internalRhymesActive = !this.internalRhymesActive;
            if (!this.internalRhymesActive) {
                // Kiemelések eltávolítása
                document.querySelectorAll('.internal-rhyme').forEach((el) => {
                    el.replaceWith(document.createTextNode(el.textContent));
                });
            } else {
                this.runAnalysis();
            }
        },

        toggleRhymeScheme() {
            this.showRhymeScheme = !this.showRhymeScheme;
            this.runAnalysis();
        },

        // ====== Export ======
        exportTxt() {
            const el = this.getEditorEl();
            if (!el) {
                return;
            }
            const lines = Array.from(el.querySelectorAll(':scope > div')).map((div) => div.textContent);
            const text = lines.join('\n');
            const blob = new Blob([text], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${this.title}_${this.todayDate()}.txt`;
            a.click();
            URL.revokeObjectURL(url);
        },

        async exportPdf() {
            const { jsPDF } = await import('jspdf');
            const doc = new jsPDF();
            const el = this.getEditorEl();
            if (!el) {
                return;
            }

            const lines = this.getLineElements();
            let y = 20;

            doc.setFontSize(16);
            doc.text(this.title, 14, y);
            y += 10;

            doc.setFontSize(10);
            doc.setTextColor(100);
            doc.text(`BPM: ${this.bpm}  |  Szótagok: ${this.totalSyllables}  |  Sorok: ${this.totalLines}`, 14, y);
            y += 8;

            doc.setDrawColor(200);
            doc.line(14, y, 196, y);
            y += 6;

            doc.setFontSize(11);
            doc.setTextColor(0);

            lines.forEach((lineEl, i) => {
                if (y > 270) {
                    doc.addPage();
                    y = 20;
                }
                const text = lineEl.textContent;
                const syl = this.lineStats[i]?.syllables ?? 0;
                const rhyme = this.lineStats[i]?.rhyme ?? '';

                doc.text(text, 14, y);

                // Szótagszám a jobb margón
                doc.setFontSize(8);
                doc.setTextColor(150);
                const margin = syl > 0 ? `${syl}` : '';
                doc.text(margin, 185, y, { align: 'right' });
                if (rhyme) {
                    doc.text(rhyme, 196, y, { align: 'right' });
                }
                doc.setFontSize(11);
                doc.setTextColor(0);

                y += 6;
            });

            doc.save(`${this.title}_${this.todayDate()}.pdf`);
        },

        todayDate() {
            return new Date().toISOString().slice(0, 10);
        },

        // ====== BPM szín ======
        syllableRatingColor(rating) {
            switch (rating) {
                case 'ok':
                    return 'text-green-600 dark:text-green-400';
                case 'warning':
                    return 'text-yellow-500 dark:text-yellow-400';
                case 'danger':
                    return 'text-red-500 dark:text-red-400';
                default:
                    return 'text-[#999]';
            }
        },

        // ====== Dátum formázás ======
        formatDate(dateStr) {
            if (!dateStr) {
                return '';
            }
            const d = new Date(dateStr);
            return d.toLocaleDateString('hu-HU', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        },

        // ====== Ajánlott szótagszám felirat ======
        recommendedSyllablesLabel() {
            const { recommendedSyllables } = window.__bpmHelper ?? {};
            if (typeof recommendedSyllables === 'function') {
                return recommendedSyllables(this.bpm, this.bpmDivisor, this.beatsPerLine);
            }
            // Inline számítás ha a modul még nem töltődött be
            const divisorMap = { '1/4': 1, '1/8': 2, '1/16': 4 };
            const syl = divisorMap[this.bpmDivisor] ?? 2;
            return Math.round((this.bpm / 60) * syl * (60 / this.bpm) * this.beatsPerLine);
        },
    };
}

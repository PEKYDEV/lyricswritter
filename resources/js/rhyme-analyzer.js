import { getCountableText } from './syllable-counter.js';

// Rímcsoportok betűi
const RHYME_LABELS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

// Belső rímkiemelés színei (Tailwind alapú inline stílusok)
const INTERNAL_RHYME_COLORS = [
    { bg: '#fef3c7', text: '#92400e' }, // sárga
    { bg: '#dbeafe', text: '#1e40af' }, // kék
    { bg: '#d1fae5', text: '#065f46' }, // zöld
    { bg: '#fce7f3', text: '#9d174d' }, // rózsaszín
];

/**
 * Levenshtein-távolság két string között
 * @param {string} a
 * @param {string} b
 * @returns {number}
 */
function levenshtein(a, b) {
    const m = a.length;
    const n = b.length;
    const dp = Array.from({ length: m + 1 }, (_, i) => [i, ...Array(n).fill(0)]);

    for (let j = 0; j <= n; j++) {
        dp[0][j] = j;
    }
    for (let i = 1; i <= m; i++) {
        for (let j = 1; j <= n; j++) {
            dp[i][j] =
                a[i - 1] === b[j - 1]
                    ? dp[i - 1][j - 1]
                    : 1 + Math.min(dp[i - 1][j], dp[i][j - 1], dp[i - 1][j - 1]);
        }
    }

    return dp[m][n];
}

/**
 * Egy sor utolsó szavának végződése (utolsó 4 karakter)
 * @param {string} text - kihagyások nélküli sor szövege
 * @returns {string}
 */
function getRhymeEnding(text) {
    const words = text.trim().split(/\s+/).filter(Boolean);
    if (words.length === 0) {
        return '';
    }
    const lastWord = words[words.length - 1].toLowerCase();
    // Írásjeleket levágjuk a végéről
    const cleaned = lastWord.replace(/[.,!?;:…]+$/, '');
    return cleaned.length <= 4 ? cleaned : cleaned.slice(-4);
}

/**
 * Sorok rímsémáját számítja ki
 * @param {HTMLElement[]} lineElements
 * @returns {string[]} - pl. ['A', 'B', 'A', 'B', '']
 */
export function analyzeRhymeScheme(lineElements) {
    const endings = lineElements.map((el) => {
        const text = getCountableText(el);
        return getRhymeEnding(text);
    });

    const knownRhymes = []; // [{ending, label}]
    let nextLabelIndex = 0;

    return endings.map((ending) => {
        if (!ending) {
            return '';
        }

        // Keresünk egyező vagy nagyon közeli végződést
        const match = knownRhymes.find(
            (r) => r.ending === ending || levenshtein(r.ending, ending) <= 1,
        );

        if (match) {
            return match.label;
        }

        // Új rímcsoport
        const label = RHYME_LABELS[nextLabelIndex % RHYME_LABELS.length];
        knownRhymes.push({ ending, label });
        nextLabelIndex++;
        return label;
    });
}

/**
 * Szavak végződésének kinyerése belső rímkiemeléshez (utolsó 2-3 karakter)
 * @param {string} word
 * @returns {string}
 */
function getWordEnding(word) {
    const cleaned = word.toLowerCase().replace(/[.,!?;:…()]+/g, '');
    if (cleaned.length < 2) {
        return '';
    }
    return cleaned.length <= 3 ? cleaned : cleaned.slice(-3);
}

/**
 * Belső rímek keresése és kiemelése HTML-ben.
 * Visszaad egy Map-et: word -> colorIndex
 * @param {HTMLElement[]} lineElements
 * @returns {Map<string, number>}
 */
export function findInternalRhymes(lineElements) {
    // Összegyűjtjük az összes sor szavait + szomszédos sorok szavait
    const allWords = [];

    lineElements.forEach((el, lineIdx) => {
        const text = getCountableText(el);
        const words = text.trim().split(/\s+/).filter((w) => w.length >= 3);
        words.forEach((word) => {
            allWords.push({ word: word.toLowerCase().replace(/[.,!?;:…()]+/g, ''), lineIdx });
        });
    });

    // Végződések csoportosítása
    const endingGroups = new Map(); // ending -> [{word, lineIdx}]

    allWords.forEach(({ word, lineIdx }) => {
        const ending = getWordEnding(word);
        if (!ending || ending.length < 2) {
            return;
        }
        if (!endingGroups.has(ending)) {
            endingGroups.set(ending, []);
        }
        endingGroups.get(ending).push({ word, lineIdx });
    });

    // Csak azokat tartjuk meg, ahol ugyanazon vagy szomszédos soron belül van legalább 2 egyező végződés
    const rhymeWords = new Map(); // word -> colorIndex
    let colorIndex = 0;

    endingGroups.forEach((occurrences) => {
        if (occurrences.length < 2) {
            return;
        }

        // Ellenőrzés: vannak-e szomszédos vagy azonos soron belül?
        const hasAdjacentOrSame = occurrences.some((a) =>
            occurrences.some((b) => a !== b && Math.abs(a.lineIdx - b.lineIdx) <= 1),
        );

        if (!hasAdjacentOrSame) {
            return;
        }

        occurrences.forEach(({ word }) => {
            if (!rhymeWords.has(word)) {
                rhymeWords.set(word, colorIndex % INTERNAL_RHYME_COLORS.length);
            }
        });
        colorIndex++;
    });

    return rhymeWords;
}

/**
 * Belső rím kiemelés HTML egy sorban.
 * @param {HTMLElement} lineElement
 * @param {Map<string, number>} rhymeWords
 */
export function applyInternalRhymeHighlights(lineElement, rhymeWords) {
    if (rhymeWords.size === 0) {
        return;
    }

    // Csak text node-okat vizsgálunk
    const walker = document.createTreeWalker(lineElement, NodeFilter.SHOW_TEXT);
    const textNodes = [];
    let node;

    while ((node = walker.nextNode())) {
        textNodes.push(node);
    }

    textNodes.forEach((textNode) => {
        const words = textNode.textContent.split(/(\s+)/);
        const hasMatch = words.some((w) => {
            const cleaned = w.toLowerCase().replace(/[.,!?;:…()]+/g, '');
            return rhymeWords.has(cleaned);
        });

        if (!hasMatch) {
            return;
        }

        const fragment = document.createDocumentFragment();
        words.forEach((part) => {
            const cleaned = part.toLowerCase().replace(/[.,!?;:…()]+/g, '');
            const colorIdx = rhymeWords.get(cleaned);

            if (colorIdx !== undefined) {
                const span = document.createElement('span');
                span.className = 'internal-rhyme';
                span.dataset.rhymeColor = colorIdx;
                span.style.backgroundColor = INTERNAL_RHYME_COLORS[colorIdx].bg;
                span.style.color = INTERNAL_RHYME_COLORS[colorIdx].text;
                span.style.borderRadius = '2px';
                span.style.padding = '0 2px';
                span.textContent = part;
                fragment.appendChild(span);
            } else {
                fragment.appendChild(document.createTextNode(part));
            }
        });

        textNode.parentNode.replaceChild(fragment, textNode);
    });
}

export { INTERNAL_RHYME_COLORS };

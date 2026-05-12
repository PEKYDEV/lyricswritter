// Magyar magánhangzók — minden magánhangzó = egy szótag (nincs diftongus magyarban)
const VOWEL_RE = /[aáeéiíoóöőuúüű]/gi;

// Kihagyandó zárójelek: (...) tartalom
const PAREN_RE = /\([^)]*\)/g;

/**
 * Zárójeles részeket eltávolítja a szövegből
 */
function stripParens(text) {
    return text.replace(PAREN_RE, '');
}

/**
 * HTML elemből szöveget nyer ki, kihagyva a dőlt (<em>, <i>) tageket
 * és a zárójelek tartalmát — ezek NEM számítanak bele a szótagszámba.
 */
function extractCountableText(node) {
    if (node.nodeType === Node.TEXT_NODE) {
        return node.textContent;
    }
    if (node.nodeType !== Node.ELEMENT_NODE) {
        return '';
    }
    const tag = node.tagName.toUpperCase();
    // Dőlt szöveg kihagyva
    if (tag === 'EM' || tag === 'I') {
        return '';
    }
    let text = '';
    for (const child of node.childNodes) {
        text += extractCountableText(child);
    }
    return text;
}

/**
 * Szótagszám egy szövegből: magánhangzók darabszáma.
 * Zárójelek tartalma kizárva.
 * @param {string} text
 * @returns {number}
 */
export function countSyllables(text) {
    if (!text) {
        return 0;
    }
    const cleaned = stripParens(text).trim();
    if (!cleaned) {
        return 0;
    }
    const m = cleaned.match(VOWEL_RE);
    return m ? m.length : 0;
}

/**
 * Egy sorelem szótagszáma — kihagyja a dőltet és a zárójeles részeket.
 * @param {HTMLElement} lineElement
 * @returns {number}
 */
export function countLineSyllables(lineElement) {
    if (!lineElement) {
        return 0;
    }
    const text = extractCountableText(lineElement);
    return countSyllables(text);
}

/**
 * Egy sorelem "számítandó" szövege (rímanalízishez, kihagyások nélkül).
 * @param {HTMLElement} lineElement
 * @returns {string}
 */
export function getCountableText(lineElement) {
    if (!lineElement) {
        return '';
    }
    return stripParens(extractCountableText(lineElement));
}

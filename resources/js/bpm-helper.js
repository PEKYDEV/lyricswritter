/**
 * BPM-szinkron segédfüggvények
 *
 * Osztók: '1/4' = 1 szótag/ütem, '1/8' = 2 szótag/ütem, '1/16' = 4 szótag/ütem
 */

const DIVISOR_MAP = {
    '1/4': 1,
    '1/8': 2,
    '1/16': 4,
};

/**
 * Szótag/másodperc kiszámítása
 * @param {number} bpm
 * @param {string} divisor - '1/4' | '1/8' | '1/16'
 * @returns {number}
 */
export function syllablesPerSecond(bpm, divisor) {
    const syllablesPerBeat = DIVISOR_MAP[divisor] ?? 2;
    return (bpm / 60) * syllablesPerBeat;
}

/**
 * Ajánlott szótagszám egy sorhoz
 * @param {number} bpm
 * @param {string} divisor
 * @param {number} beatsPerLine - hány ütem/sor (1, 2, 4, 8)
 * @returns {number}
 */
export function recommendedSyllables(bpm, divisor, beatsPerLine) {
    const sps = syllablesPerSecond(bpm, divisor);
    const secondsPerLine = (60 / bpm) * beatsPerLine;
    return Math.round(sps * secondsPerLine);
}

/**
 * Sor szótagszámának értékelése BPM alapján
 * @param {number} syllableCount
 * @param {number} recommended
 * @returns {'ok' | 'warning' | 'danger' | 'empty'}
 */
export function rateSyllableCount(syllableCount, recommended) {
    if (syllableCount === 0) {
        return 'empty';
    }
    if (recommended === 0) {
        return 'ok';
    }
    const ratio = syllableCount / recommended;
    if (ratio >= 0.8 && ratio <= 1.2) {
        return 'ok';
    }
    if (ratio >= 0.6 && ratio <= 1.4) {
        return 'warning';
    }
    return 'danger';
}

/**
 * Össz-szótagszámból időtartam-becslés
 * @param {number} totalSyllables
 * @param {number} bpm
 * @param {string} divisor
 * @returns {string} - pl. "1:23"
 */
export function estimateDuration(totalSyllables, bpm, divisor) {
    if (totalSyllables === 0 || bpm === 0) {
        return '0:00';
    }
    const sps = syllablesPerSecond(bpm, divisor);
    const totalSeconds = Math.round(totalSyllables / sps);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${minutes}:${String(seconds).padStart(2, '0')}`;
}

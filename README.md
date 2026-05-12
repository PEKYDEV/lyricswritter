# lyricwriter

Magyar dalszövegíró és elemző alkalmazás. Valós idejű szótagszámlálás, rímséma-elemzés, BPM-szinkron és verziókezelés.

---

## Telepítés

### 1. Függőségek telepítése

```bash
composer install
npm install
```

### 2. Környezeti konfiguráció

```bash
cp .env.example .env
php artisan key:generate
```

A `.env` fájlban az adatbázis alapértelmezetten SQLite, így nincs szükség külső adatbázisra.

### 3. Adatbázis létrehozása

```bash
php artisan migrate
```

Ez létrehozza a `database/database.sqlite` fájlt (ha még nem létezik) és lefuttatja a migrációkat.

### 4. Frontend build

**Fejlesztési módhoz** (hot reload, Herd-del együtt futtatva):
```bash
npm run dev
```

**Produkciós buildhez:**
```bash
npm run build
```

---

## Futtatás Herd alatt

Az alkalmazás Laravel Herd alá van konfigurálva. A Herd automatikusan felismeri a `C:\Users\<felhasználó>\Herd\lyricwriter` mappát, és az alkalmazás a következő URL-en érhető el:

```
http://lyricwriter.test
```

Fejlesztéshez indítsd el a Vite dev servert is (`npm run dev`), különben a frontend nem töltődik be.

Egyszerre indítható a PHP szerver és a Vite:
```bash
composer run dev
```

---

## Nothing / Ndot 55 font beállítása

Az alkalmazás a Nothing telefonok rendszerfontját, az **Ndot 55** (Nothing Dot Matrix) betűtípust használja UI elemekhez.

**Honnan szerezhető be:**
- A fontot a Nothing Technology, Ltd. fejlesztette. Nyilvánosan elérhető forrásai:
  - GitHub: keress rá `Ndot-55` névre — rajongói repók terjesztik
  - `Nothing Phone` témájú font gyűjtemények

**Telepítés:**

1. Keresd meg az `Ndot-55.otf` vagy `NothingDotMatrix.ttf` fájlt
2. Másold be a projekt `public/fonts/` mappájába:
   ```
   public/fonts/Ndot-55.otf
   public/fonts/Ndot-55.ttf
   public/fonts/NothingDotMatrix.ttf
   ```
3. Töltsd újra a böngészőt

Ha a fontfájl nem elérhető, az alkalmazás automatikusan a következő fallback fontokat használja:
- `JetBrains Mono`
- `IBM Plex Mono`
- `Courier New`

---

## Funkciók

### Szövegszerkesztő
- Gazdag szöveg szerkesztő (`contenteditable`) dőlt formázással
- Zárójeles részek `(...)` nem számítanak bele a szótagszámba
- Dőlt (`<em>`) szöveg kizárható a szótagszámolásból
- Tiszta szöveg beillesztés (HTML formázás lestripelve)

### Magyar szótagszámlálás
- Magánhangzók megszámlálása soronként: `a á e é i í o ó ö ő u ú ü ű`
- Valós idejű frissítés gépelés közben (max 80ms késleltetéssel)
- Jobb oldali oszlopban halvány számokkal jelezve

### BPM-szinkron
- BPM mező (20–300), osztó (1/4 / 1/8 / 1/16), ütem/sor beállítás
- Soronkénti színkód: zöld = ajánlott tartományban, sárga = ±20%, piros = ±40%
- Időtartam-becslés az össz-szótagszámból

### Rímséma (ABAB)
- Sorok utolsó szavának összehasonlítása Levenshtein-távolsággal
- Rímcsoport-jelölők (`A`, `B`, `C`, ...) a jobb oldali oszlopban
- Toggle gomb: ki/bekapcsolható

### Belső rímek kiemelése
- Azonos vagy szomszédos sorokon belüli ismétlődő végzodések kiemelése
- 4 szín rotáció: sárga / kék / zöld / rózsaszín
- Toggle gombbal ki/bekapcsolható

### Verziókezelés
- Autosave: 3 másodperc inaktivitás után automatikus mentés
- Verzió létrehozás: manuális ("Mentés" gomb), vagy 1 óránként automatikusan
- Verzió panel: lista dátummal, snippet-tel, előnézet és visszaállítás

### Export
- **TXT**: plain szöveg, HTML tag-ek nélkül
- **PDF**: jsPDF-fel generálva, szótagszámokkal a margón

### Sötét mód
- Jobb alsó sarokban toggle
- `localStorage`-ban tárolt preferencia

---

## Adatbázis séma

| Tábla | Leírás |
|-------|--------|
| `songs` | Dalok: cím, tartalom (HTML), BPM |
| `song_versions` | Verzió-történet: tartalom, cím, opcionális label |

A `songs` táblába könnyedén hozzáadható `user_id` foreign key (a modell és a controller erre előkészített).

---

## API végpontok

| Method | Végpont | Leírás |
|--------|---------|--------|
| GET | `/api/songs` | Összes dal |
| POST | `/api/songs` | Új dal |
| GET | `/api/songs/{id}` | Dal lekérése |
| PUT | `/api/songs/{id}` | Dal frissítése (autosave) |
| DELETE | `/api/songs/{id}` | Dal törlése |
| GET | `/api/songs/{id}/versions` | Verziólista |
| POST | `/api/songs/{id}/versions` | Verzió mentése |
| POST | `/api/songs/{id}/versions/{vid}/restore` | Visszaállítás |

---

## Tech stack

- **Backend**: Laravel 13, PHP 8.3, SQLite
- **Frontend**: Blade + Alpine.js 3, Tailwind CSS 4
- **Bundler**: Vite 8
- **JS modulok**: `syllable-counter.js`, `rhyme-analyzer.js`, `bpm-helper.js`, `editor.js`
- **PDF**: jsPDF (kliensoldali)

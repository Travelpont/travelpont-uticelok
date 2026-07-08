# Travelpont Úticélok plugin – dokumentáció

> Verzió: 1.0.0 · A Travelpont Ajánlatok plugin architektúráját követi
> (`D:\travelpont.hu\_Saját_pluginek\travelpont-ajanlatok\`)
> SZABÁLY: minden módosításkor verziót emelünk a fő fájl fejlécében
> (cache-buster + követhetőség).

## Mit tud?

- **"Úticélok" menüpont** a WP adminban → hierarchikus úticél-oldalak felvitele
  űrlapon (nem kell kódolni): ország → tájegység (opcionális) → város.
  A szülő kiválasztása a WordPress natív "Oldal-attribútumok" dobozával
  történik (ugyanaz, mint az Oldalaknál).
- **Mezők**: cím (post title), rövid leíró szöveg (`tpu_leiras`), kiemelt kép
  (natív featured image – ezt kell feltölteni, ha elkészülnek a képek).
- **Automatikus, hierarchikus URL-ek**: `/uticelok/horvatorszag/`,
  `/uticelok/horvatorszag/isztria/`, `/uticelok/horvatorszag/isztria/pula/` –
  a szülő-gyerek kapcsolat alapján, kódolás nélkül. (A WordPress ezt csak a
  natív Oldalaknál csinálja automatikusan, egyedi CPT-nél a plugin építi fel
  – lásd `includes/cpt.php`.)
- **Úticél aloldal** → minden úticél-oldal automatikusan megjeleníti:
  - morzsamenüt (ország > tájegység > város),
  - a leíró szöveget,
  - a gyerek úticélokat kártyarácsban (pl. egy ország oldalán a városai),
  - a hozzá (VAGY BÁRMELY leszármazottjához) kapcsolt **Ajánlatokat** – a
    Travelpont Ajánlatok plugin `tpa_uticel` mezője alapján,
  - a hozzá kapcsolt **Blog cikkeket** – a `tpu_kapcsolt_uticel` mező alapján.
- **`[travelpont_uticelok]` shortcode** → tetszőleges szülő gyerekeinek
  kártyarácsa bárhová (pl. egy "Úticélok" áttekintő oldalra).

## Kapcsolat a másik két tartalomtípussal

Mivel az Úticél a kapcsolat "célpontja", a kapcsoló mezőt a FORRÁS oldalon
(Ajánlat, Blog cikk) kell kitölteni:

- **Ajánlat** → a szerkesztőben az "Utazás adatai" dobozban megjelenik egy
  "Úticél" legördülő (a Travelpont Ajánlatok plugin `fields.php`-jában
  `tpa_uticel` néven, `post_select` típussal – ez ÚJ mező, a `tpa_uticel`
  a felelős érte).
- **Blog cikk** (natív bejegyzés) → a szerkesztő oldalsávjában megjelenik egy
  "🌍 Úticél" doboz (ezt EZ a plugin adja hozzá, `includes/blog-kapcsolo.php`,
  mert a Blognak még nincs saját pluginja – ha lesz, ez a fájl oda is
  átköltöztethető).

Az Úticél oldal ezután MAGÁTÓL összegyűjti a hozzá kapcsolt ajánlatokat/
cikkeket – nincs szükség kézi linkelésre.

## Shortcode használat

```
[travelpont_uticelok]
[travelpont_uticelok szulo="horvatorszag"]
[travelpont_uticelok szulo="42" oszlopok="4" rendezes="nev"]
```

| Paraméter | Alapérték | Lehetőségek |
|---|---|---|
| `szulo` | (üres = országok) | Úticél ID vagy slug – ekkor az ő gyerekeit listázza |
| `limit` | -1 (összes) | szám |
| `rendezes` | `sorrend` | `sorrend` (admin "Sorrend" mező) \| `nev` (ABC) |
| `oszlopok` | 3 | 2, 3, 4 (széles képernyőn) |

## Fájlszerkezet

```
travelpont-uticelok/
├── travelpont-uticelok.php    ← fő fájl: konstansok, modul-betöltés, enqueue
├── includes/
│   ├── fields.php             ← ⭐ KÖZPONTI MEZŐ-DEFINÍCIÓK (bővítés itt!)
│   ├── cpt.php                ← "uticel" CPT + hierarchikus URL-kezelés
│   ├── meta-boxes.php         ← admin űrlap (a fields.php-ból épül, generikus)
│   ├── blog-kapcsolo.php      ← "Úticél" mező a natív Blog bejegyzéseken
│   ├── shortcodes.php         ← [travelpont_uticelok]
│   ├── single-display.php     ← úticél-doboz az aloldalon (the_content elé)
│   └── rest-api.php           ← CSONTVÁZ: csak /status ping (portál helye)
├── templates/
│   ├── lista-template.php     ← kártyarács
│   ├── card-template.php      ← egy kártya
│   └── single-content.php     ← aloldali doboz (morzsamenü, gyerekek, kapcsolódó tartalom)
└── assets/css/
    ├── frontend.css           ← kártya + aloldal (branding CSS-változókban!)
    └── admin.css               ← admin űrlap
```

## Miért nincs automatikus nested URL a WordPress-ben alapból?

A WordPress csak a natív "oldal" (page) post type-nál épít automatikusan
szülő/gyerek URL-t. Egyedi hierarchikus CPT-nél ezt a `cpt.php` négy
lépésben oldja meg:

1. `'rewrite' => false, 'query_var' => false` a regisztrációnál – a WP ne
   generáljon saját, egy-szintű szabályt.
2. Egyetlen rewrite szabály minden mélységre: `^uticelok/(.+?)/?$`.
3. `post_type_link` szűrő, ami az ős-lánc (`get_post_ancestors()`) slugjaiból
   összerakja a teljes URL-t.
4. `pre_get_posts`, ami a WordPress saját `get_page_by_path()` függvényével
   (ez BÁRMELY hierarchikus post type-ra működik, nem csak "page"-re)
   visszakeresi a bejegyzést a beérkező útvonalból.

## Hogyan bővítsd? (SEMMI SINCS BEÉGETVE)

### Új mező hozzáadása
`includes/fields.php` → `tpu_get_fields()` tömbjébe új bejegyzés, a
Travelpont Ajánlatok pluginnal azonos módon (`text | number | url | date |
select | textarea` típusok).

### Hookok (kódból, akár másik pluginból)
- `tpu_fields`, `tpu_sections` – mezők/szekciók módosítása
- `tpu_lista_query_args` – a lista-lekérdezés módosítása
- `tpu_shortcode_defaults` – shortcode alapértékek
- `tpu_after_save_meta` – fut minden úticél-mentés után
- `tpu_single_doboz_vege` – extra tartalom az aloldali doboz aljára
- `tpu_rest_api_init` – későbbi REST endpointok regisztrálása
- `tpu_ures_lista_szoveg` – üres lista szövegének átírása

### Branding / színek
`assets/css/frontend.css` tetején CSS-változók (`--tpu-primary`…) –
ugyanazokkal az alapértékekkel, mint az Ajánlatok pluginban.

## Telepítés

1. A `travelpont-uticelok` mappát felmásolni ide: `wp-content/plugins/`
2. WP admin → Bővítmények → "Travelpont Úticélok" → Bekapcsolás
   (a permalink szabályok automatikusan frissülnek).
3. Úticélok → Új úticél → először az országokat vidd fel (szülő nélkül),
   utána a tájegységeket/városokat, "Oldal-attribútumok" → Szülő beállítva.
4. Ajánlat vagy Blog cikk szerkesztésekor válaszd ki a hozzá tartozó Úticélt
   – automatikusan megjelenik az Úticél oldalán.

# Travelpont Úticélok plugin – dokumentáció

> Verzió: 1.20.2 · A Travelpont Ajánlatok plugin architektúráját követi
> (1.20.2: mobil-szélesítés – a téma tartalom-kártyájának paddingje
> mérsékelve `.single-uticel`-en [az Ajánlatok v1.13.1 mintája]: a ~390px-es
> kijelzőn a tartalom ~290px-re szűkült, a régió-csempék feliratai szó
> közben törtek. Kisebb H1 is. Playwright-screenshot alapján azonosítva.)
> (1.20.1: az „Ajánlataink ehhez az úticélhoz" tartalmi blokk KIVEZETVE a
> `single-content.php`-ból [Gabesz döntése, 2026-07-19] – az ajánlatok az
> OLDALSÁVBÓL jönnek: `[travelpont_ajanlatok limit="4" uticel="aktualis"
> oszlopok="1" nezet="kompakt"]` [Travelpont Ajánlatok v1.17.0 shortcode,
> azonos kör: úticél + leszármazottak, a `tpu_get_leszarmazott_idk()`
> helperre építve]. A blokk-ág üresen megmaradt, hogy a Portálban mentett
> blokk-sorrendek ne törjenek. FIGYELEM: az oldalsáv widget + Kadence
> elrendezés kézi beállítás – addig az úticél-oldalon nincs ajánlat-szekció.
> [Megjegyzés: a docs-fejléc verziója sokáig 1.8.0-n ragadt, most a plugin
> tényleges verziójához igazítva.])
> (`D:\travelpont.hu\_Saját_pluginek\travelpont-ajanlatok\`)
> SZABÁLY: minden módosításkor verziót emelünk a fő fájl fejlécében
> (cache-buster + követhetőség).

## Mit tud?

- **"Úticélok" menüpont** a WP adminban → hierarchikus úticél-oldalak felvitele
  űrlapon (nem kell kódolni): ország → tájegység (opcionális) → város.
  A szülő kiválasztása a WordPress natív "Oldal-attribútumok" dobozával
  történik (ugyanaz, mint az Oldalaknál).
- **Szint (`tpu_szint`)**: minden úticél megjelöli, mi ő a hierarchiában –
  **Ország / Régió / Város / Egyéb**. Ettől függ az oldal felépítése ÉS az,
  mely mezők látszanak a szerkesztőben (a nem releváns mezők automatikusan
  elrejtődnek – `assets/js/admin-szint.js`). A szint **nem kötelező láncot**:
  szint kihagyható (kis ország régió nélkül, szigetcsoport önálló „régióként").
- **Mezők**: cím (post title), rövid leíró szöveg (`tpu_leiras`), kiemelt kép
  (natív featured image). Szint-függő extra mezők:
  - *Ország*: pénznem, nyelv, időzóna, be-/kiutazási tudnivaló
  - *Régió, Város*: legjobb utazási időszak
  - *Város*: legközelebbi repülőtér, repülési idő Budapestről (repjegy-affiliate)
  - *Minden szint*: Google Maps beágyazási URL (`tpu_terkep`) – csak a
    `https://www.google.com/maps/embed…` kezdetű URL-t fogadja el (biztonság).
- **Automatikus, hierarchikus URL-ek**: `/uticelok/horvatorszag/`,
  `/uticelok/horvatorszag/isztria/`, `/uticelok/horvatorszag/isztria/pula/` –
  a szülő-gyerek kapcsolat alapján, kódolás nélkül. (A WordPress ezt csak a
  natív Oldalaknál csinálja automatikusan, egyedi CPT-nél a plugin építi fel
  – lásd `includes/cpt.php`.)
- **Úticél aloldal (szint-függő elrendezés)** → minden úticél-oldal
  automatikusan megjeleníti (a `tpu_szint`-től függő sorrendben/hangsúllyal):
  - morzsamenüt (ország > tájegység > város),
  - a leíró szöveget,
  - **szint-függő info-dobozt**: Ország → „Jó tudni" (pénznem/nyelv/…),
    Város → „Gyakorlati infó" (repülőtér, repülési idő),
  - térképet (ha van beágyazási URL),
  - a gyerek úticélokat kártyarácsban, szint-függő címmel (Ország → „Régiók",
    Régió → „Városok"),
  - a hozzá (VAGY BÁRMELY leszármazottjához) kapcsolt **Ajánlatokat** – a
    Travelpont Ajánlatok plugin `tpa_uticel` mezője alapján (Város szinten
    hangsúlyosan, a gyerek-rács elé kerül),
  - a hozzá kapcsolt **Blog cikkeket** – a `tpu_kapcsolt_uticel` mező alapján.
  - *Ha nincs szint kitöltve*: az általános elrendezés (visszafelé kompatibilis).
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
│   └── rest-api.php           ← teljes CRUD API a Travelpont Portalnak (tpu/v1)
├── templates/
│   ├── lista-template.php     ← kártyarács
│   ├── card-template.php      ← egy kártya
│   └── single-content.php     ← aloldali doboz (morzsamenü, gyerekek, kapcsolódó tartalom)
├── assets/css/
│   ├── frontend.css           ← kártya + aloldal (branding CSS-változókban!)
│   └── admin.css              ← admin űrlap + szint-választó kiemelés
└── assets/js/
    ├── galeria-lightbox.js    ← galéria lightbox
    └── admin-szint.js         ← szint szerint mutatja/rejti a mezőket a szerkesztőben
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

## Portál-kommunikáció (v1.2.0-tól KÉSZ)

`includes/rest-api.php` – teljes CRUD API a Travelpont Portal (Firebase, külön
repó) Cloud Functions proxyja számára, az aktivbalaton `bsza/v1` mintáját
követve. Auth: Application Password (Basic Auth) + `publish_posts`.

- `GET /tpu/v1/uticelok` – lista (szűrés: `search`, `status`, `parent`)
- `GET/PUT /tpu/v1/uticel/{id}`, `POST /tpu/v1/uticel` – egy úticél / létrehozás / frissítés, `parent` paraméterrel (szülő beállítás/módosítás)
- `POST /tpu/v1/uticel/{id}/kep` – kiemelt kép sideload URL-ből
- `GET /tpu/v1/meta` – TELJES flat lista (`id`/`title`/`parent`) minden úticélról – ebből épít fastruktúrát a Portál a hierarchikus szülő-választóhoz
- `GET /tpu/v1/status` – publikus ping

A permalinkeket (nested URL, pl. `/uticelok/orszag/varos/`) a meglévő
`post_type_link` szűrő adja automatikusan – a REST réteg csak `get_permalink()`-et hív.

## Telepítés

1. A `travelpont-uticelok` mappát felmásolni ide: `wp-content/plugins/`
2. WP admin → Bővítmények → "Travelpont Úticélok" → Bekapcsolás
   (a permalink szabályok automatikusan frissülnek).
3. Úticélok → Új úticél → először az országokat vidd fel (szülő nélkül,
   **Szint = Ország**), utána a régiókat (**Szint = Régió**, Szülő = az ország)
   és a városokat (**Szint = Város**, Szülő = a régió). A Szülő az
   "Oldal-attribútumok" dobozban, a Szint az „🌍 Alapadatok" dobozban.
4. Ajánlat vagy Blog cikk szerkesztésekor válaszd ki a hozzá tartozó Úticélt
   – automatikusan megjelenik az Úticél oldalán.

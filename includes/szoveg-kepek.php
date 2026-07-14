<?php
/**
 * Travelpont Úticélok – szövegközi képek díszítése
 *
 * A KÉP HELYÉT A SZERKESZTŐ DÖNTI EL a Portál "Kép beszúrása" gombjával —
 * ez a fájl nem találgat, csak a szerkesztő által a szövegbe helyezett
 * jelölőt (<img class="tpu-inline-kep" data-id="...">) alakítja át a
 * végleges, keretezett <figure> markuppá. (Korábbi verziók automatikusan
 * próbálták kitalálni a képek helyét szakasz-elemzéssel és felirat-
 * párosítással — ez élő, valódi szövegen ismételten megbízhatatlannak
 * bizonyult, ezért a projekt a kézi elhelyezés mellett döntött.)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Egy szövegközi kép-blokk <figure> markupja.
 *
 * @param int    $kep_id Attachment ID.
 * @param string $meret  '' (normál) | 'teljes' | 'kicsi' — a Portál
 *                       kép-blokkján választott megjelenítési méret.
 */
function tpu_kep_blokk_figure( $kep_id, $meret = '' ) {
    $felirat = wp_get_attachment_caption( $kep_id );
    $alt     = $felirat ? $felirat : get_the_title( $kep_id );

    $class = 'tpu-kep-blokk';
    if ( in_array( $meret, array( 'teljes', 'kicsi' ), true ) ) {
        $class .= ' tpu-kep-blokk--' . $meret;
    }

    $html  = '<figure class="' . esc_attr( $class ) . '">';
    $html .= '<a href="' . esc_url( wp_get_attachment_url( $kep_id ) ) . '" class="tpu-galeria-elem" data-caption="' . esc_attr( $felirat ) . '">';
    $html .= wp_get_attachment_image( $kep_id, 'large', false, array( 'alt' => $alt ) );
    $html .= '</a>';
    if ( $felirat ) {
        $html .= '<figcaption>' . esc_html( $felirat ) . '</figcaption>';
    }
    $html .= '</figure>';

    return $html;
}

/**
 * A szerkesztő által kézzel elhelyezett kép-jelölők (<img class="tpu-inline-kep"
 * data-id="X">) átalakítása a végleges, keretezett <figure> markuppá — pontosan
 * ott, ahova a szerkesztő tette. Nincs keresés, nincs találgatás.
 *
 * @param string $content       A bejegyzés tartalma (HTML).
 * @param array  $hasznalt_idk  (kimenő) A szövegben ténylegesen szereplő attachment ID-k.
 * @return string A díszített tartalom.
 */
function tpu_inline_kepek_diszitese( $content, &$hasznalt_idk ) {
    $hasznalt_idk = array();

    if ( trim( (string) $content ) === '' ) {
        return $content;
    }

    // A jelölőt a class alapján fogjuk meg, az attribútumokat a tagen BELÜL
    // keressük — a böngésző/Quill tetszőleges attribútum-sorrendben
    // szerializálhat (élesben pl. <img decoding src data-id alt class> jön).
    $content = preg_replace_callback(
        '/<img[^>]*\btpu-inline-kep\b[^>]*>/i',
        function( $m ) use ( &$hasznalt_idk ) {
            if ( ! preg_match( '/data-id="(\d+)"/i', $m[0], $id_m ) ) {
                return ''; // jelölő azonosító nélkül — nem tudjuk kirajzolni
            }
            $kep_id = (int) $id_m[1];
            if ( ! wp_attachment_is_image( $kep_id ) ) {
                return ''; // törölt/érvénytelen kép — csendben eltűnik
            }
            $hasznalt_idk[] = $kep_id;

            // Opcionális megjelenítési méret a Portál kép-blokkjáról.
            $meret = preg_match( '/data-meret="([^"]*)"/i', $m[0], $meret_m ) ? $meret_m[1] : '';

            return tpu_kep_blokk_figure( $kep_id, $meret );
        },
        $content
    );

    // A wpautop a magányos img-jelölőt <p>-be csomagolta; a figure blokk-elem,
    // ezért a köréje ragadt <p>…</p> héjat leszedjük (kósza üres bekezdések
    // és érvénytelen beágyazás ellen). A class-lista bővülhet (méret-változat),
    // ezért csak a prefixre illesztünk.
    $content = preg_replace( '#<p>\s*(<figure class="tpu-kep-blokk)#', '$1', $content );
    $content = preg_replace( '#(</figure>)\s*</p>#', '$1', $content );

    // A wpautop a csoportosító divek (tpu-kep-szoveg / tpu-galeria-sor) körül
    // hagyhat teljesen üres bekezdéseket — ezeket leszedjük. (A szándékos üres
    // sor a Quill-ből <p><br></p>-ként jön, azt ez nem érinti.)
    $content = preg_replace( '#<p>\s*</p>#', '', $content );

    return $content;
}

/**
 * A fotó-mozaik rácsának HTML-je (cím nélkül — címsort a szerkesztő ad elé,
 * ha szeretne). A képek TELJES egészében látszanak (contain a CSS-ben),
 * vágás sosem történik; a cellák a lightboxba is bekötődnek (tpu-galeria-elem).
 *
 * @param int[] $kep_idk Attachment ID-k.
 * @return string Üres string, ha nincs megjeleníthető kép.
 */
function tpu_fotomozaik_html( $kep_idk ) {
    $cellak = '';
    foreach ( $kep_idk as $kep_id ) {
        if ( ! wp_attachment_is_image( $kep_id ) ) continue;
        $felirat = wp_get_attachment_caption( $kep_id );
        $alt     = $felirat ? $felirat : get_the_title( $kep_id );

        $cellak .= '<a class="tpu-mozaik-csempe tpu-mozaik-csempe--galeria tpu-galeria-elem" href="' . esc_url( wp_get_attachment_url( $kep_id ) ) . '" data-caption="' . esc_attr( $felirat ) . '">'
            . wp_get_attachment_image( $kep_id, 'medium_large', false, array( 'loading' => 'lazy', 'alt' => $alt ) )
            . ( $felirat ? '<span class="tpu-mozaik-nev">' . esc_html( $felirat ) . '</span>' : '' )
            . '</a>';
    }
    if ( '' === $cellak ) return '';
    return '<div class="tpu-grid tpu-grid--mozaik" style="--tpu-card-min: 190px;">' . $cellak . '</div>';
}

/**
 * A szerkesztő által elhelyezett fotó-mozaik helyjelző
 * (<div class="tpu-fotomozaik"></div>) kicserélése a szövegben fel nem
 * használt galéria-képek rácsára — PONTOSAN ott, ahova a szerkesztő tette.
 * Helyjelző nélkül a fel nem használt képek NEM jelennek meg sehol
 * (a korábbi automatikus cikk végi hozzáfűzés kivezetve — a szerkesztő dönt).
 * Csak az első helyjelző renderel; az esetleges továbbiak eltűnnek.
 *
 * @param string $content      A (már jelölő-díszített) tartalom.
 * @param int[]  $galeria_idk  A teljes galéria attachment ID-i.
 * @param int[]  $hasznalt_idk A szövegben már felhasznált ID-k.
 */
function tpu_fotomozaik_beillesztes( $content, $galeria_idk, $hasznalt_idk ) {
    $re = '/<div[^>]*\btpu-fotomozaik\b[^>]*>\s*<\/div>/i';
    if ( ! preg_match( $re, $content ) ) {
        return $content;
    }

    $maradek = array_values( array_diff( array_map( 'intval', $galeria_idk ), array_map( 'intval', $hasznalt_idk ) ) );
    $mozaik  = tpu_fotomozaik_html( $maradek );

    // Callback-kel cserélünk, hogy a kép-URL-ek $-jelei ne sérüljenek,
    // és csak az első helyjelző kapja meg a rácsot.
    $volt = false;
    return preg_replace_callback( $re, function() use ( &$volt, $mozaik ) {
        if ( $volt ) return '';
        $volt = true;
        return $mozaik;
    }, $content );
}

/* ═══════════════════ Tartalmi widgetek (Portál vászon-szerkesztő) ═══════════════════
   A kiemelés-doboz (.tpu-kiemeles) és a GYIK (.tpu-gyik, natív details/summary)
   nem igényel PHP-t — tiszta CSS. Az alábbiak a szerver-adatos/biztonsági
   feldolgozást végzik. */

/**
 * CTA-gombok díszítése: külső (pl. affiliate) linknél új fül + sponsored
 * jelölés; belső linknél a tag változatlan.
 *
 * @param string $content    A tartalom.
 * @param string $sajat_host A saját domain (tesztelhetőség; üresen a home_url()-ból).
 */
function tpu_cta_diszitese( $content, $sajat_host = '' ) {
    if ( '' === $sajat_host ) {
        $sajat_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );
    }
    return preg_replace_callback( '/<a[^>]*\btpu-cta\b[^>]*>/i', function( $m ) use ( $sajat_host ) {
        $tag = $m[0];
        if ( ! preg_match( '/href="([^"]*)"/i', $tag, $h ) ) {
            return $tag;
        }
        $host = wp_parse_url( html_entity_decode( $h[1], ENT_QUOTES ), PHP_URL_HOST );
        if ( ! $host || $host === $sajat_host ) {
            return $tag; // belső vagy relatív link — marad, ahogy van
        }
        return substr( $tag, 0, -1 ) . ' target="_blank" rel="sponsored nofollow noopener">';
    }, $content );
}

/**
 * YouTube-videó helyjelzők → kattintásra töltő beágyazás (adatkímélő,
 * gyors oldal; az iframe-et a tpu-video.js cseréli be youtube-nocookie-val).
 */
function tpu_video_render( $content ) {
    return preg_replace_callback( '/<div[^>]*class="tpu-video"[^>]*>\s*<\/div>/i', function( $m ) {
        if ( ! preg_match( '/data-youtube="([A-Za-z0-9_-]{6,15})"/', $m[0], $id_m ) ) {
            return ''; // érvénytelen azonosító — csendben eltűnik
        }
        $id = $id_m[1];
        return '<div class="tpu-video-doboz" data-youtube="' . esc_attr( $id ) . '">'
            . '<button type="button" class="tpu-video-inditas" aria-label="Videó lejátszása">'
            . '<img src="' . esc_url( 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg' ) . '" loading="lazy" alt="">'
            . '<span class="tpu-video-play">▶</span>'
            . '</button></div>';
    }, $content );
}

/**
 * Térkép-helyjelzők → iframe, KIZÁRÓLAG Google Maps beágyazási URL-lel
 * (ugyanaz a whitelist-elv, mint a tpu_terkep mező sanitizerénél) —
 * tetszőleges iframe nem csempészhető be.
 */
function tpu_terkep_widget_render( $content ) {
    return preg_replace_callback( '/<div[^>]*class="tpu-terkep-widget"[^>]*>\s*<\/div>/i', function( $m ) {
        if ( ! preg_match( '/data-src="([^"]*)"/i', $m[0], $src_m ) ) {
            return '';
        }
        $src = html_entity_decode( $src_m[1], ENT_QUOTES );
        if ( strpos( $src, 'https://www.google.com/maps/embed' ) !== 0 ) {
            return ''; // nem Google-embed — nem rendereljük
        }
        return '<div class="tpu-terkep tpu-terkep--szovegkozi">'
            . '<iframe src="' . esc_url( $src ) . '" height="380" style="border:0;" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade"></iframe>'
            . '</div>';
    }, $content );
}

/**
 * Egy bejegyzés kirajzolása a saját sablonjával, ideiglenes globális
 * $post-cserével (a kártya-sablonok the_title()/the_permalink()-re épülnek).
 */
function tpu_widget_sablon_html( $bejegyzes, $sablon_utvonal ) {
    $eredeti = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
    $GLOBALS['post'] = $bejegyzes;
    setup_postdata( $bejegyzes );
    ob_start();
    include $sablon_utvonal;
    $html = ob_get_clean();
    $GLOBALS['post'] = $eredeti;
    wp_reset_postdata();
    return $html;
}

/**
 * Ajánlat-kártya helyjelzők → a travelpont-ajanlatok kártya-sablonja.
 * Hiányzó/visszavont/lejárt-törölt ajánlatnál a helyjelző nyomtalanul eltűnik;
 * ha az Ajánlatok plugin nincs aktív, egyszerű link-gomb a tartalék.
 */
function tpu_ajanlat_widget_render( $content ) {
    return preg_replace_callback( '/<div[^>]*class="tpu-ajanlat-widget"[^>]*>\s*<\/div>/i', function( $m ) {
        if ( ! preg_match( '/data-id="(\d+)"/', $m[0], $id_m ) ) {
            return '';
        }
        $ajanlat = get_post( (int) $id_m[1] );
        if ( ! $ajanlat || 'ajanlat' !== $ajanlat->post_type || 'publish' !== $ajanlat->post_status ) {
            return '';
        }
        if ( ! function_exists( 'tpa_mezo' ) || ! defined( 'TPA_PATH' ) ) {
            return '<p class="tpu-cta-sor"><a class="tpu-cta" href="' . esc_url( get_permalink( $ajanlat ) ) . '">' . esc_html( get_the_title( $ajanlat ) ) . ' →</a></p>';
        }
        $kartya = tpu_widget_sablon_html( $ajanlat, TPA_PATH . 'templates/card-template.php' );
        return '<div class="tpu-ajanlat-beszurt tpa-grid">' . $kartya . '</div>';
    }, $content );
}

/**
 * Úticél-ajánló helyjelzők → fotó-csempe (mozaik-csempe.php) a saját
 * oldalára linkelve. Nem publikált/törölt úticélnál eltűnik.
 */
function tpu_uticel_widget_render( $content ) {
    return preg_replace_callback( '/<div[^>]*class="tpu-uticel-widget"[^>]*>\s*<\/div>/i', function( $m ) {
        if ( ! preg_match( '/data-id="(\d+)"/', $m[0], $id_m ) ) {
            return '';
        }
        $uticel = get_post( (int) $id_m[1] );
        if ( ! $uticel || 'uticel' !== $uticel->post_type || 'publish' !== $uticel->post_status ) {
            return '';
        }
        $csempe = tpu_widget_sablon_html( $uticel, TPU_PATH . 'templates/mozaik-csempe.php' );
        return '<div class="tpu-uticel-beszurt">' . $csempe . '</div>';
    }, $content );
}

/**
 * FAQPage JSON-LD a tartalom GYIK-elemeiből (Google rich result).
 * Üres string, ha nincs (érvényes) GYIK.
 */
function tpu_gyik_schema_json( $content ) {
    if ( ! preg_match_all( '/<details[^>]*class="[^"]*tpu-gyik[^"]*"[^>]*>\s*<summary[^>]*>([\s\S]*?)<\/summary>\s*<div[^>]*class="[^"]*tpu-gyik-valasz[^"]*"[^>]*>([\s\S]*?)<\/div>\s*<\/details>/i', $content, $mk, PREG_SET_ORDER ) ) {
        return '';
    }
    $kerdesek = array();
    foreach ( $mk as $par ) {
        $kerdes = trim( wp_strip_all_tags( $par[1] ) );
        $valasz = trim( wp_strip_all_tags( $par[2] ) );
        if ( '' === $kerdes || '' === $valasz ) continue;
        $kerdesek[] = array(
            '@type'          => 'Question',
            'name'           => $kerdes,
            'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $valasz ),
        );
    }
    if ( ! $kerdesek ) return '';
    return wp_json_encode(
        array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $kerdesek ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
}

/**
 * Publikus renderelő tetszőleges tpu-formátumú HTML-hez, bejegyzéstől
 * függetlenül – a travelpont-kezdolap "Szabad szekció" modulja hívja
 * function_exists() guarddal. A single-display.php the_content láncának
 * bejegyzés-független része, egyben.
 *
 * Eltérés a single-oldali lánctól: nincs bejegyzés-galéria, ezért a
 * fotó-mozaik helyjelző (ha a szerkesztő betette) csendben eltűnik.
 * A GYIK-schema a meglévő wp_footer hookon íródik ki; ha egy oldalon több
 * tartalom is állít GYIK-et, az utolsó nyer (elfogadott egyszerűsítés).
 */
function tpu_render_tartalom( $html ) {
    $hasznalt = array();
    $content  = tpu_inline_kepek_diszitese( (string) $html, $hasznalt );
    $content  = tpu_fotomozaik_beillesztes( $content, array(), $hasznalt );
    $content  = tpu_cta_diszitese( $content );
    $content  = tpu_video_render( $content );
    $content  = tpu_terkep_widget_render( $content );
    $content  = tpu_ajanlat_widget_render( $content );
    $content  = tpu_uticel_widget_render( $content );

    $gyik_schema = tpu_gyik_schema_json( $content );
    if ( $gyik_schema ) {
        $GLOBALS['tpu_gyik_schema'] = $gyik_schema;
    }

    return $content;
}

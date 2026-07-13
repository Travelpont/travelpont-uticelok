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

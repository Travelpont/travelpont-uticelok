<?php
/**
 * Travelpont Úticélok – Galéria-képek beszövése a leírás szövegébe
 *
 * A leírás szakaszait (h3-mal tagolt egységeit) a galéria képeivel párba
 * állítva "zig-zag" sorokká alakítja:
 *   1. sor: kép BALRA, szöveg jobbra
 *   2. sor: kép JOBBRA, szöveg balra
 *   3. sor: TELJES szélességű kép, alatta a szöveg
 *   ... és a ritmus ismétlődik. Mobilon minden sor egyoszlopos (kép felül).
 *
 * Kép-hozzárendelés két körben:
 *  1. FELIRAT-PÁROSÍTÁS: a kép felirata (vagy címe/fájlneve) alapján ahhoz a
 *     szakaszhoz kerül, amelyik említi (szó-eleji egyezés, magyar toldalék-
 *     tűréssel: "Toszkána" → "Toszkánában" is talál).
 *  2. SORREND: ami nem párosítható, a maradék szakaszokat tölti fel a
 *     Portálbeli feltöltési sorrend szerint.
 * A be nem szőtt képek a galéria-tömbben maradnak (lásd single-content.php).
 * Minden kép 16:9 keretben jelenik meg — a szerkesztett 16:9-es forrásképeknél
 * ez NULLA vágást jelent (lásd a képarány-szabályt).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Egy szakasz-sor képének <figure> markupja.
 *
 * @param int  $kep_id  Attachment ID.
 * @param bool $teljes  Teljes szélességű sorba kerül-e (nagyobb képméret).
 */
function tpu_szakasz_kep_figure( $kep_id, $teljes ) {
    $felirat = wp_get_attachment_caption( $kep_id );
    $alt     = $felirat ? $felirat : get_the_title( $kep_id );

    $html  = '<figure class="tpu-szakasz-kep">';
    $html .= '<a href="' . esc_url( wp_get_attachment_url( $kep_id ) ) . '" class="tpu-galeria-elem" data-caption="' . esc_attr( $felirat ) . '">';
    $html .= wp_get_attachment_image( $kep_id, $teljes ? 'large' : 'medium_large', false, array( 'alt' => $alt ) );
    $html .= '</a>';
    if ( $felirat ) {
        $html .= '<figcaption>' . esc_html( $felirat ) . '</figcaption>';
    }
    $html .= '</figure>';

    return $html;
}

/**
 * UTF-8 kisbetűsítés / hossz — mbstring-tartalékkal, hogy a plugin olyan
 * környezetben se dőljön el, ahol az mbstring bővítmény hiányzik.
 * (Tartalék-ágban az ékezetes NAGYBETŰ kisbetűsítése kimarad — a gyakorlatban
 * a feliratok és a szöveg ékezetes betűi eleve kisbetűsek.)
 */
function tpu_kisbetus( $s ) {
    return function_exists( 'mb_strtolower' ) ? mb_strtolower( $s, 'UTF-8' ) : strtolower( $s );
}

function tpu_szohossz( $s ) {
    return function_exists( 'mb_strlen' ) ? mb_strlen( $s, 'UTF-8' ) : strlen( $s );
}

/**
 * Kulcsszó-jelöltek egy képhez a felirata és a címe (fájlneve) alapján.
 * Először a teljes felirat, utána az egyes (legalább 4 betűs) szavai.
 *
 * @return string[] Kisbetűs kulcsszavak, erősorrendben.
 */
function tpu_kep_kulcsszavak( $kep_id ) {
    $jeloltek = array();

    foreach ( array( wp_get_attachment_caption( $kep_id ), get_the_title( $kep_id ) ) as $forras ) {
        $forras = tpu_kisbetus( trim( (string) $forras ) );
        if ( $forras === '' ) continue;

        if ( tpu_szohossz( $forras ) >= 4 ) {
            $jeloltek[] = $forras; // teljes kifejezés (pl. "amalfi-part")
        }
        $szavak = preg_split( '/[^\pL\pN]+/u', $forras, -1, PREG_SPLIT_NO_EMPTY );
        foreach ( $szavak as $szo ) {
            if ( tpu_szohossz( $szo ) >= 4 && ! is_numeric( $szo ) ) {
                $jeloltek[] = $szo;
            }
        }
    }

    return array_values( array_unique( $jeloltek ) );
}

/**
 * A tageket szóközzel elválasztva vetkőzteti le a HTML-t, hogy a címek és a
 * szöveg ne ragadjanak össze ("<h3>Táj</h3><p>Toszkánában" ≠ "TájToszkánában").
 */
function tpu_slot_szoveg( $html ) {
    return tpu_kisbetus( trim( wp_strip_all_tags( str_replace( '<', ' <', $html ) ) ) );
}

/**
 * Szerepel-e a kulcsszó a szövegben szó-eleji egyezéssel (toldalék-tűrő).
 * Mindkét oldal kisbetűs. A magyar toldalék előtt a szóvégi a/e/o/ö megnyúlik
 * (Barcelona→Barcelonában, Velence→Velencében), ezért az utolsó magánhangzót
 * rugalmasan illesztjük.
 */
function tpu_szovegben_szerepel( $kulcs, $szoveg ) {
    $minta  = preg_quote( $kulcs, '/' );
    $nyulas = array( 'a' => '[aá]', 'e' => '[eé]', 'o' => '[oó]', 'ö' => '[öő]' );
    if ( preg_match( '/(.)$/u', $kulcs, $m ) && isset( $nyulas[ $m[1] ] ) ) {
        $torzs = preg_replace( '/.$/u', '', $kulcs );
        $minta = preg_quote( $torzs, '/' ) . $nyulas[ $m[1] ];
    }
    return (bool) preg_match( '/(?<![\pL\pN])' . $minta . '/u', $szoveg );
}

/**
 * A galéria képeinek beszövése a tartalomba (zig-zag szakasz-sorok).
 *
 * @param string $content       A bejegyzés tartalma (HTML).
 * @param int    $post_id       Az úticél ID-je.
 * @param array  $hasznalt_idk  (kimenő) A szövegbe beszőtt attachment ID-k.
 * @return string A képekkel átszőtt tartalom.
 */
function tpu_kepek_beszovese( $content, $post_id, &$hasznalt_idk ) {
    $hasznalt_idk = array();

    if ( trim( $content ) === '' ) {
        return $content;
    }

    // A Portál Quill-szerkesztője a címsor előtti üres sorokat üres <h3></h3>
    // elemként menti — ezek hézagot okoznak ÉS hamis szakaszhatárt adnának.
    // Eltávolítjuk őket (galéria nélküli úticélnál is).
    $content = preg_replace( '#<h([23])>(\s|&\#?\w+;)*</h\1>#u', '', $content );

    $galeria_idk = get_post_meta( $post_id, 'tpu_galeria_ids', true );
    $galeria_idk = is_array( $galeria_idk ) ? array_map( 'intval', $galeria_idk ) : array();
    $galeria_idk = array_values( array_filter( $galeria_idk, 'wp_attachment_is_image' ) );

    if ( ! $galeria_idk ) {
        return $content;
    }

    // ── Szakaszok (slotok) összegyűjtése ─────────────────────────────────────
    // Slot: egy szöveg-egység, ami képet kaphat + a kisbetűs szövege a
    // felirat-párosításhoz. 'db'–'db2' a darabok tömbben elfoglalt tartomány.
    $van_h3 = ( stripos( $content, '<h3' ) !== false );
    $slotok = array();

    if ( $van_h3 ) {
        $darabok = preg_split( '/(?=<h3)/i', $content );
        foreach ( $darabok as $d => $darab ) {
            $sima = tpu_slot_szoveg( $darab );
            if ( $sima === '' ) continue; // üres szakasz sosem slot
            if ( $d === 0 || stripos( $darab, '</h3>' ) !== false ) {
                $slotok[] = array( 'db' => $d, 'db2' => $d, 'szoveg' => $sima );
            }
        }
    } else {
        // Tagolatlan szöveg: bekezdés-párok alkotnak egy-egy szakaszt.
        $darabok  = preg_split( '/(?<=<\/p>)/i', $content );
        $par_elso = -1;
        foreach ( $darabok as $d => $darab ) {
            if ( stripos( $darab, '</p>' ) === false ) continue;
            if ( $par_elso < 0 ) {
                $par_elso = $d;
            } else {
                $slotok[] = array(
                    'db'     => $par_elso,
                    'db2'    => $d,
                    'szoveg' => tpu_slot_szoveg( $darabok[ $par_elso ] . ' ' . $darab ),
                );
                $par_elso = -1;
            }
        }
        // Páratlan utolsó bekezdés is lehet szakasz.
        if ( $par_elso >= 0 ) {
            $slotok[] = array( 'db' => $par_elso, 'db2' => $par_elso, 'szoveg' => tpu_slot_szoveg( $darabok[ $par_elso ] ) );
        }
    }

    if ( ! $slotok ) {
        return $content;
    }

    // ── 1. kör: felirat-párosítás (elsőbbséget élvez a sorrendi töltéssel szemben) ──
    $slot_kep     = array_fill( 0, count( $slotok ), 0 ); // slot-index → kép ID (0 = üres)
    $sorrendi_sor = array(); // nem párosított képek, feltöltési sorrendben

    foreach ( $galeria_idk as $kep_id ) {
        $talalt = false;
        foreach ( tpu_kep_kulcsszavak( $kep_id ) as $kulcs ) {
            foreach ( $slotok as $si => $slot ) {
                if ( $slot_kep[ $si ] ) continue; // a slot már foglalt
                if ( tpu_szovegben_szerepel( $kulcs, $slot['szoveg'] ) ) {
                    $slot_kep[ $si ] = $kep_id;
                    $talalt = true;
                    break 2;
                }
            }
        }
        if ( ! $talalt ) {
            $sorrendi_sor[] = $kep_id;
        }
    }

    // ── 2. kör: a maradék képek a szabad szakaszokba, sorrendben ─────────────
    foreach ( $slot_kep as $si => $kep ) {
        if ( ! $kep && $sorrendi_sor ) {
            $slot_kep[ $si ] = array_shift( $sorrendi_sor );
        }
    }

    // ── Szakasz-sorok összeállítása dokumentum-sorrendben ────────────────────
    // Ritmus: kép balra → kép jobbra → teljes szélességű, majd elölről.
    $variansok = array( 'kep-bal', 'kep-jobb', 'teljes' );
    $i = 0;
    foreach ( $slotok as $si => $slot ) {
        if ( ! $slot_kep[ $si ] ) continue; // képtelen szakasz változatlan marad

        $varians = $variansok[ $i % 3 ];
        $figure  = tpu_szakasz_kep_figure( $slot_kep[ $si ], $varians === 'teljes' );

        $darabok[ $slot['db'] ] = '<section class="tpu-szakasz tpu-szakasz--' . $varians . '">'
            . $figure . '<div class="tpu-szakasz-szoveg">' . $darabok[ $slot['db'] ];
        $darabok[ $slot['db2'] ] .= '</div></section>';

        $hasznalt_idk[] = $slot_kep[ $si ];
        $i++;
    }

    if ( ! $hasznalt_idk ) {
        return $content;
    }

    return '<div class="tpu-szoveges-tartalom">' . implode( '', $darabok ) . '</div>';
}

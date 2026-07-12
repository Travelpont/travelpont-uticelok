<?php
/**
 * Travelpont Úticélok – Galéria-képek beszövése a leírás szövegébe
 *
 * A galéria (tpu_galeria_ids) képeit automatikusan elhelyezi a bemutató
 * szöveg szakaszai közé, "mix" stílusban: felváltva jobbra/balra úszó
 * képek (a szöveg körbefolyja), minden harmadik pedig teljes szélességű,
 * filmes "levegővétel"-sáv. A be nem szőtt képek a galéria-tömbben maradnak
 * (lásd single-content.php). A szerkesztői munkamenet nem változik: a képek
 * sorrendjét a Portálbeli feltöltési sorrend adja.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Egy szövegközi kép <figure> markupja.
 *
 * @param int $kep_id  Attachment ID.
 * @param int $i       Hanyadik beszőtt kép (0-tól) – ebből jön a stílus.
 */
function tpu_szoveg_kep_figure( $kep_id, $i ) {
    $stilusok = array( 'tpu-szoveg-kep--jobb', 'tpu-szoveg-kep--bal', 'tpu-szoveg-kep--nagy' );
    $stilus   = $stilusok[ $i % 3 ];
    $meret    = ( $stilus === 'tpu-szoveg-kep--nagy' ) ? 'large' : 'medium_large';

    $felirat = wp_get_attachment_caption( $kep_id );
    $alt     = $felirat ? $felirat : get_the_title( $kep_id );

    $html  = '<figure class="tpu-szoveg-kep ' . esc_attr( $stilus ) . '">';
    $html .= '<a href="' . esc_url( wp_get_attachment_url( $kep_id ) ) . '" class="tpu-galeria-elem" data-caption="' . esc_attr( $felirat ) . '">';
    $html .= wp_get_attachment_image( $kep_id, $meret, false, array( 'loading' => 'lazy', 'alt' => $alt ) );
    $html .= '</a>';
    if ( $felirat ) {
        $html .= '<figcaption>' . esc_html( $felirat ) . '</figcaption>';
    }
    $html .= '</figure>';

    return $html;
}

/**
 * A galéria képeinek beszövése a tartalomba.
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

    $galeria_idk = get_post_meta( $post_id, 'tpu_galeria_ids', true );
    $galeria_idk = is_array( $galeria_idk ) ? array_map( 'intval', $galeria_idk ) : array();
    $galeria_idk = array_values( array_filter( $galeria_idk, 'wp_attachment_is_image' ) );

    if ( ! $galeria_idk ) {
        return $content;
    }

    $i = 0; // hanyadik képnél járunk (a stílus-rotációhoz)

    if ( stripos( $content, '<h3' ) !== false ) {
        // Tagolt szöveg: 1. kép az intro elejére, a többi az alcímek után.
        $szakaszok = preg_split( '/(?=<h3)/i', $content );

        foreach ( $szakaszok as $sz => $szakasz ) {
            if ( ! isset( $galeria_idk[ $i ] ) ) break;

            if ( $sz === 0 ) {
                // Az intro elejére csak akkor, ha van benne érdemi szöveg.
                if ( trim( wp_strip_all_tags( $szakasz ) ) === '' ) continue;
                $szakaszok[ $sz ] = tpu_szoveg_kep_figure( $galeria_idk[ $i ], $i ) . $szakasz;
                $i++;
            } elseif ( stripos( $szakasz, '</h3>' ) !== false ) {
                $szakaszok[ $sz ] = preg_replace(
                    '/<\/h3>/i',
                    '</h3>' . tpu_szoveg_kep_figure( $galeria_idk[ $i ], $i ),
                    $szakasz,
                    1
                );
                $i++;
            }
        }
        $content = implode( '', $szakaszok );
    } else {
        // Tagolatlan (h3 nélküli) szöveg: minden 2. bekezdés után egy kép.
        $darabok = preg_split( '/(?<=<\/p>)/i', $content );
        $p_szam  = 0;
        foreach ( $darabok as $d => $darab ) {
            if ( ! isset( $galeria_idk[ $i ] ) ) break;
            if ( stripos( $darab, '</p>' ) === false ) continue;
            $p_szam++;
            if ( $p_szam % 2 === 0 ) {
                $darabok[ $d ] = $darab . tpu_szoveg_kep_figure( $galeria_idk[ $i ], $i );
                $i++;
            }
        }
        $content = implode( '', $darabok );
    }

    $hasznalt_idk = array_slice( $galeria_idk, 0, $i );

    if ( ! $hasznalt_idk ) {
        return $content;
    }

    // Wrapper a scoped CSS-hez (h3 clear + clearfix a lezáráshoz).
    return '<div class="tpu-szoveges-tartalom">' . $content . '</div>';
}

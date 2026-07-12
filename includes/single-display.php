<?php
/**
 * Travelpont Úticélok – Aloldal (single) megjelenítés
 *
 * A Travelpont Ajánlatok mintáját követve a tartalom ELÉ fűzzük be az
 * úticél-dobozt a the_content szűrővel – ez blokk-témával ÉS Hello
 * Elementorral is ugyanúgy működik, a témaváltás nem töri el.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'the_content', function( $content ) {
    if ( ! is_singular( 'uticel' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    wp_enqueue_style( 'travelpont-uticelok' );

    // ELŐBB a galéria-képek beszövése a szövegbe — a használt ID-kat a
    // single-content.php galéria-tömbje kihagyja (nincs duplikáció).
    $hasznalt = array();
    $content  = tpu_kepek_beszovese( $content, get_the_ID(), $hasznalt );
    $GLOBALS['tpu_szott_kep_idk'] = $hasznalt;

    ob_start();
    include TPU_PATH . 'templates/single-content.php';
    $uticel_doboz = ob_get_clean();

    return $uticel_doboz . $content;
} );

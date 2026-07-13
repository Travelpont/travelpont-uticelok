<?php
/**
 * Travelpont Úticélok – Aloldal (single) megjelenítés
 *
 * A Travelpont Ajánlatok mintáját követve a tartalom ELÉ fűzzük be az
 * úticél-dobozt a the_content szűrővel – ez blokk-témával ÉS Hello
 * Elementorral is ugyanúgy működik, a témaváltás nem töri el.
 *
 * Végleges sorrend: doboz (infók, térkép, gyerek-mozaik, ajánlatok) →
 * törzsszöveg (a szerkesztő által kézzel elhelyezett képekkel és — ha a
 * szerkesztő betette a 📷 helyjelzőt — a fel nem használt galéria-képek
 * mozaikjával, pontosan ott, ahova tette).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'the_content', function( $content ) {
    if ( ! is_singular( 'uticel' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    wp_enqueue_style( 'travelpont-uticelok' );

    $post_id = get_the_ID();

    // A szerkesztő által a szövegbe kézzel beillesztett kép-jelölők díszítése.
    $hasznalt = array();
    $content  = tpu_inline_kepek_diszitese( $content, $hasznalt );

    // Fotó-mozaik a szerkesztő által elhelyezett helyjelzőnél (ha nincs
    // helyjelző, a fel nem használt galéria-képek nem jelennek meg sehol).
    $galeria_idk = get_post_meta( $post_id, 'tpu_galeria_ids', true );
    $galeria_idk = is_array( $galeria_idk ) ? array_map( 'intval', $galeria_idk ) : array();
    $content     = tpu_fotomozaik_beillesztes( $content, $galeria_idk, $hasznalt );

    // Tartalmi widgetek (Portál vászon-szerkesztő): CTA-linkek díszítése,
    // majd a szerver-adatos helyjelzők kitöltése.
    $content = tpu_cta_diszitese( $content );
    $content = tpu_video_render( $content );
    $content = tpu_terkep_widget_render( $content );
    $content = tpu_ajanlat_widget_render( $content );
    $content = tpu_uticel_widget_render( $content );

    // GYIK strukturált adat (FAQPage) a láblécbe — lásd a wp_footer hook-ot lent.
    $gyik_schema = tpu_gyik_schema_json( $content );
    if ( $gyik_schema ) {
        $GLOBALS['tpu_gyik_schema'] = $gyik_schema;
    }

    // A videó-lejátszó apró JS-e csak akkor töltődik, ha van videó az oldalon.
    if ( strpos( $content, 'tpu-video-doboz' ) !== false ) {
        wp_enqueue_script( 'travelpont-uticelok-video' );
    }

    ob_start();
    include TPU_PATH . 'templates/single-content.php';
    $uticel_doboz = ob_get_clean();

    return $uticel_doboz . $content;
} );

// A the_content futásakor összegyűjtött GYIK-schema kiírása a láblécben.
add_action( 'wp_footer', function() {
    if ( empty( $GLOBALS['tpu_gyik_schema'] ) ) return;
    echo '<script type="application/ld+json">' . $GLOBALS['tpu_gyik_schema'] . '</script>' . "\n";
} );

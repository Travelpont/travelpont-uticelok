<?php
/**
 * Travelpont Úticélok – Shortcode-ok
 *
 * [travelpont_uticelok]
 *   szulo=""        üres = legfelső szint (országok); vagy egy Úticél
 *                   bejegyzés ID-je vagy slugja – ekkor az ő gyerekeit listázza
 *   limit="-1"      hány úticél jelenjen meg (-1 = összes)
 *   rendezes="sorrend"  sorrend (admin "Sorrend" mező) | nev (ABC)
 *   oszlopok="3"    2 | 3 | 4 – a kártyák kívánt oszlopszáma széles képernyőn
 *   nezet="kartya"  kartya (hover-overlay kártyák) | mozaik (kép + név csempék)
 *
 * A shortcode Elementorban (Shortcode widget) és blokk-témában
 * (Shortcode blokk) is ugyanúgy használható.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Egy "szulo" attribútum (ID vagy slug) feloldása post ID-re ───────────────
function tpu_resolve_szulo( $szulo ) {
    if ( $szulo === '' ) return 0;
    if ( is_numeric( $szulo ) ) return (int) $szulo;

    $post = get_page_by_path( $szulo, OBJECT, 'uticel' );
    return $post ? $post->ID : -1; // -1 = nem található, üres eredmény legyen
}

function tpu_uticelok_shortcode( $atts ) {
    $atts = shortcode_atts( apply_filters( 'tpu_shortcode_defaults', array(
        'szulo'    => '',
        'limit'    => -1,
        'rendezes' => 'sorrend',
        'oszlopok' => 3,
        'nezet'    => 'kartya',
    ) ), $atts, 'travelpont_uticelok' );

    $args = array(
        'post_type'      => 'uticel',
        'post_status'    => 'publish',
        'post_parent'    => tpu_resolve_szulo( $atts['szulo'] ),
        'posts_per_page' => (int) $atts['limit'],
    );

    if ( $atts['rendezes'] === 'nev' ) {
        $args['orderby'] = 'title';
        $args['order']   = 'ASC';
    } else {
        $args['orderby'] = 'menu_order title';
        $args['order']   = 'ASC';
    }

    $args = apply_filters( 'tpu_lista_query_args', $args, $atts );

    $tpu_query = new WP_Query( $args );
    $tpu_atts  = $atts;

    wp_enqueue_style( 'travelpont-uticelok' );

    ob_start();
    include TPU_PATH . 'templates/lista-template.php';
    return ob_get_clean();
}
add_shortcode( 'travelpont_uticelok', 'tpu_uticelok_shortcode' );

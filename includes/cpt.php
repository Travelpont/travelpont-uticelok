<?php
/**
 * Travelpont Úticélok – Custom Post Type + hierarchikus URL-kezelés
 *
 * FONTOS: a WordPress alapból CSAK a natív "oldal" (page) post type-nál
 * épít automatikusan szülő/gyerek URL-eket (pl. /szulo/gyerek/). Egyedi
 * hierarchikus CPT-nél ezt magunknak kell megoldani:
 *   1) 'rewrite' => false és 'query_var' => false a regisztrációnál,
 *   2) saját, minden mélységet lefedő rewrite szabály,
 *   3) 'post_type_link' szűrő, ami az ős-lánc alapján összerakja az URL-t,
 *   4) 'pre_get_posts', ami a beérkező útvonalat visszakeresi a bejegyzésre
 *      (a WP saját get_page_by_path() függvénye ezt bármely hierarchikus
 *      post type-ra tudja, nem csak a "page"-re).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Custom Post Type: uticel ──────────────────────────────────────────────────
function tpu_register_cpt() {
    $labels = array(
        'name'               => 'Úticélok',
        'singular_name'      => 'Úticél',
        'menu_name'          => 'Úticélok',
        'add_new'            => 'Új úticél',
        'add_new_item'       => 'Új úticél hozzáadása',
        'edit_item'          => 'Úticél szerkesztése',
        'new_item'           => 'Új úticél',
        'view_item'          => 'Úticél megtekintése',
        'search_items'       => 'Úticélok keresése',
        'not_found'          => 'Nem található úticél',
        'not_found_in_trash' => 'Nincs úticél a kukában',
        'parent_item_colon'  => 'Szülő úticél:',
    );

    $args = array(
        'labels'          => $labels,
        'public'          => true,
        'show_ui'         => true,
        'show_in_menu'    => true,
        'query_var'       => false, // saját rewrite/lekérdezés-feloldás kezeli, lásd lentebb
        'rewrite'         => false, // ua. – a WP ne generáljon automatikus, egy-szintű szabályt
        'capability_type' => 'post',
        'has_archive'     => false, // a listázást a [travelpont_uticelok] shortcode adja
        'hierarchical'    => true,  // ország → tájegység (opcionális) → város
        'menu_position'   => 24,
        'menu_icon'       => 'dashicons-location-alt',
        'supports'        => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
        'show_in_rest'    => true,  // blokk-szerkesztő + standard wp/v2 REST (a saját tpu/v1 namespace ettől független)
    );

    register_post_type( 'uticel', apply_filters( 'tpu_cpt_args', $args ) );
}
add_action( 'init', 'tpu_register_cpt', 5 );

// ── Rewrite szabály: /uticelok/bármi-tetszőleges-mélységben/ ─────────────────
function tpu_add_rewrite_rules() {
    add_rewrite_rule( '^uticelok/(.+?)/?$', 'index.php?tpu_path=$matches[1]', 'top' );
}
add_action( 'init', 'tpu_add_rewrite_rules', 20 );

add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'tpu_path';
    return $vars;
} );

// ── Permalink összeállítása: ős-lánc slugjai / saját slug ────────────────────
add_filter( 'post_type_link', function( $link, $post ) {
    if ( $post->post_type !== 'uticel' ) return $link;
    return home_url( '/uticelok/' . tpu_get_path( $post ) . '/' );
}, 10, 2 );

function tpu_get_path( $post ) {
    $post = get_post( $post );
    if ( ! $post ) return '';

    $segments   = array( $post->post_name );
    $ancestors  = get_post_ancestors( $post ); // gyerektől a szülő felé sorban
    foreach ( $ancestors as $ancestor_id ) {
        $segments[] = get_post_field( 'post_name', $ancestor_id );
    }
    return implode( '/', array_reverse( $segments ) );
}

// ── Beérkező /uticelok/... útvonal visszakeresése a megfelelő bejegyzésre ────
add_action( 'pre_get_posts', function( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) return;

    $path = $query->get( 'tpu_path' );
    if ( ! $path ) return;

    $post = get_page_by_path( $path, OBJECT, 'uticel' );

    if ( $post ) {
        $query->set( 'post_type', 'uticel' );
        $query->set( 'p', $post->ID );
        $query->is_single   = true;
        $query->is_singular = true;
        $query->is_page     = false;
        $query->is_home     = false;
        $query->is_404      = false;
    } else {
        $query->set_404();
    }
} );

// ── Összes leszármazott ID-je (rekurzív) – kapcsolódó tartalom gyűjtéséhez ────
function tpu_get_leszarmazott_idk( $post_id ) {
    $ids      = array();
    $gyerekek = get_children( array(
        'post_parent' => $post_id,
        'post_type'   => 'uticel',
        'post_status' => 'publish',
        'fields'      => 'ids',
        'numberposts' => -1,
    ) );
    foreach ( $gyerekek as $gyerek_id ) {
        $ids[] = $gyerek_id;
        $ids   = array_merge( $ids, tpu_get_leszarmazott_idk( $gyerek_id ) );
    }
    return $ids;
}

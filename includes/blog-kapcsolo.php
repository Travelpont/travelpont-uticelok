<?php
/**
 * Travelpont Úticélok – "Úticél" kapcsoló mező a Blog cikkekhez
 *
 * A Blognak még nincs saját pluginja ("leendő" fejlesztés), ezért ezt a
 * kis kapcsoló mezőt egyelőre ide, az Úticél pluginba tesszük – ő a
 * kapcsolat célpontja. Ha később elkészül egy önálló Travelpont Blog
 * plugin, ez a fájl oda is átköltöztethető.
 *
 * Meta kulcs: tpu_kapcsolt_uticel (a natív "post" post type-on).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'tpu_box_kapcsolt_uticel',
        '🌍 Úticél',
        'tpu_render_blog_kapcsolo',
        'post',
        'side',
        'default'
    );
} );

function tpu_render_blog_kapcsolo( $post ) {
    wp_nonce_field( 'tpu_save_blog_kapcsolo', 'tpu_blog_kapcsolo_nonce' );
    $value = get_post_meta( $post->ID, 'tpu_kapcsolt_uticel', true );

    wp_dropdown_pages( array(
        'post_type'         => 'uticel',
        'name'              => 'tpu_kapcsolt_uticel',
        'id'                => 'tpu_kapcsolt_uticel',
        'selected'          => $value,
        'show_option_none'  => '— nincs kiválasztva —',
        'option_none_value' => '',
        'sort_column'       => 'menu_order,post_title',
    ) );
    echo '<p class="description">Melyik Úticélhoz (ország / tájegység / város) kapcsolódik ez a cikk? A cikk automatikusan megjelenik a kiválasztott Úticél oldalán.</p>';
}

add_action( 'save_post_post', function( $post_id ) {
    if ( ! isset( $_POST['tpu_blog_kapcsolo_nonce'] ) || ! wp_verify_nonce( $_POST['tpu_blog_kapcsolo_nonce'], 'tpu_save_blog_kapcsolo' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;
    if ( ! isset( $_POST['tpu_kapcsolt_uticel'] ) ) return;

    $raw   = absint( wp_unslash( $_POST['tpu_kapcsolt_uticel'] ) );
    $value = ( $raw && get_post( $raw ) ) ? (string) $raw : '';

    update_post_meta( $post_id, 'tpu_kapcsolt_uticel', $value );
} );

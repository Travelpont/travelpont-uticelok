<?php
/**
 * Travelpont Úticélok – REST API
 *
 * Prefix: /wp-json/tpu/v1/
 *
 *   GET  /tpu/v1/uticelok          – Lista (szűrés, lapozás, szülő szerint)
 *   GET  /tpu/v1/uticel/{id}       – Egy úticél részletei
 *   POST /tpu/v1/uticel            – Úticél létrehozása (szülő megadható)
 *   PUT  /tpu/v1/uticel/{id}       – Úticél frissítése (szülő is módosítható)
 *   POST /tpu/v1/uticel/{id}/kep   – Kiemelt kép sideload URL-ből
 *   GET  /tpu/v1/meta              – TELJES flat lista (id/title/parent) a
 *                                    Portál hierarchikus szülő-választójához
 *   GET  /tpu/v1/status            – Publikus ping
 *
 * Auth: WordPress Application Password (Basic Auth) + publish_posts capability
 * (a Travelpont Portal Firebase Cloud Function proxyja hívja, sosem a böngésző közvetlenül).
 *
 * A nested URL-eket (pl. /uticelok/orszag/varos/) a meglévő post_type_link
 * szűrő adja (lásd includes/cpt.php, tpu_get_path()) – ez a réteg csak
 * get_permalink()-et hív, a rewrite-logikát nem kell ismernie.
 *
 * Yoast SEO mezők (seo_title/seo_metadesc) a create/update végpontokon keresztül
 * íródnak/olvasódnak (_yoast_wpseo_title / _yoast_wpseo_metadesc postmeta) – NEM a
 * tpu_get_fields() rendszer része, mert ezek Yoast saját mezői, nem a plugin sajátjai.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function() {

    register_rest_route( 'tpu/v1', '/uticelok', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'tpu_api_list',
        'permission_callback' => 'tpu_api_auth',
        'args'                => array(
            'per_page' => array( 'type' => 'integer', 'default' => 50, 'minimum' => 1, 'maximum' => 200 ),
            'page'     => array( 'type' => 'integer', 'default' => 1,  'minimum' => 1 ),
            'search'   => array( 'type' => 'string',  'default' => '' ),
            'status'   => array( 'type' => 'string',  'default' => 'publish', 'enum' => array( 'publish', 'draft', 'any' ) ),
            'parent'   => array( 'type' => 'integer', 'default' => -1 ), // -1 = nincs szűrés, 0 = csak gyökér
        ),
    ) );

    register_rest_route( 'tpu/v1', '/uticel/(?P<id>\d+)', array(
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'tpu_api_get',
            'permission_callback' => 'tpu_api_auth',
        ),
        array(
            'methods'             => WP_REST_Server::EDITABLE,
            'callback'            => 'tpu_api_update',
            'permission_callback' => 'tpu_api_auth',
        ),
    ) );

    register_rest_route( 'tpu/v1', '/uticel', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'tpu_api_create',
        'permission_callback' => 'tpu_api_auth',
        'args'                => tpu_api_args(),
    ) );

    register_rest_route( 'tpu/v1', '/uticel/(?P<id>\d+)/kep', array(
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => 'tpu_api_sideload_image',
        'permission_callback' => 'tpu_api_auth',
        'args'                => array(
            'url' => array( 'type' => 'string', 'required' => true ),
        ),
    ) );

    register_rest_route( 'tpu/v1', '/meta', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'tpu_api_meta',
        'permission_callback' => 'tpu_api_auth',
    ) );

    register_rest_route( 'tpu/v1', '/status', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'tpu_api_status',
        'permission_callback' => '__return_true',
    ) );

    do_action( 'tpu_rest_api_init' );
} );

// ── Auth ───────────────────────────────────────────────────────────────────────
function tpu_api_auth() {
    return current_user_can( 'publish_posts' );
}

// ── Közös arg-definíciók a create/update endpointokhoz ────────────────────────
function tpu_api_args() {
    $args = array(
        'title'   => array( 'type' => 'string',  'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
        'content' => array( 'type' => 'string',  'default'  => '' ),
        'status'  => array( 'type' => 'string',  'default'  => 'publish', 'enum' => array( 'publish', 'draft' ) ),
        'parent'  => array( 'type' => 'integer', 'default'  => 0 ),
        'seo_title'    => array( 'type' => 'string', 'default' => '' ),
        'seo_metadesc' => array( 'type' => 'string', 'default' => '' ),
    );

    foreach ( tpu_get_fields() as $key => $field ) {
        $args[ $key ] = array( 'type' => 'string', 'default' => '' );
    }

    return $args;
}

// ── Úticél → API válasz formátum ───────────────────────────────────────────────
function tpu_api_format( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'uticel' ) return array();

    $parent_id = (int) $post->post_parent;
    $thumb_id  = (int) get_post_thumbnail_id( $post_id );
    $thumb_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium_large' ) : '';

    $data = array(
        'id'            => $post_id,
        'title'         => $post->post_title,
        'slug'          => $post->post_name,
        'status'        => $post->post_status,
        'content'       => $post->post_content,
        'parent'        => $parent_id,
        'parent_title'  => $parent_id ? get_the_title( $parent_id ) : '',
        'depth'         => count( get_post_ancestors( $post_id ) ),
        'thumbnail_id'  => $thumb_id,
        'thumbnail_url' => $thumb_url ?: '',
        'seo_title'     => get_post_meta( $post_id, '_yoast_wpseo_title', true ),
        'seo_metadesc'  => get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ),
        'permalink'     => get_permalink( $post_id ) ?: '',
        'edit_url'      => admin_url( "post.php?post={$post_id}&action=edit" ),
        'created_at'    => get_post_field( 'post_date', $post_id ),
        'modified_at'   => get_post_field( 'post_modified', $post_id ),
    );

    foreach ( tpu_get_fields() as $key => $field ) {
        $data[ $key ] = tpu_mezo( $post_id, $key );
    }

    return $data;
}

// ── Egyedi mezők + szülő mentése (create/update közös) ────────────────────────
function tpu_api_save_fields( $post_id, WP_REST_Request $req ) {
    foreach ( tpu_get_fields() as $key => $field ) {
        if ( $req->get_param( $key ) === null ) continue;

        $raw   = wp_unslash( $req->get_param( $key ) );
        $type  = isset( $field['type'] ) ? $field['type'] : 'text';
        $value = tpu_sanitize_field_value( $type, $raw, $field );

        update_post_meta( $post_id, $key, $value );
    }

    if ( $req->get_param( 'seo_title' ) !== null ) {
        update_post_meta( $post_id, '_yoast_wpseo_title', sanitize_text_field( $req->get_param( 'seo_title' ) ) );
    }
    if ( $req->get_param( 'seo_metadesc' ) !== null ) {
        update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_textarea_field( $req->get_param( 'seo_metadesc' ) ) );
    }
    if ( $req->get_param( 'seo_title' ) !== null || $req->get_param( 'seo_metadesc' ) !== null ) {
        tpu_yoast_indexable_frissit( $post_id );
    }
}

// ── Yoast SEO indexable frissítése REST mentés után (lásd travelpont-ajanlatok
// azonos nevű függvénye, ugyanaz az indoklás: a REST update_post_meta()-ja nem
// futtatja a Yoast admin save_post hookját, a cache-táblát direktben kell frissíteni) ──
function tpu_yoast_indexable_frissit( $post_id ) {
    if ( ! function_exists( 'YoastSEO' ) ) {
        clean_post_cache( $post_id );
        return;
    }
    try {
        $repository = YoastSEO()->classes->get( 'Yoast\WP\SEO\Repositories\Indexable_Repository' );
        $builder    = YoastSEO()->classes->get( 'Yoast\WP\SEO\Builders\Indexable_Builder' );
        $indexable  = $repository->find_by_id_and_type( $post_id, 'post', false );
        $builder->build_for_id_and_type( $post_id, 'post', $indexable );
    } catch ( \Throwable $e ) {
        clean_post_cache( $post_id );
    }
}

// ── Szülő validálása: csak létező, más "uticel" post lehet, önmaga nem ────────
function tpu_api_resolve_parent( $post_id, $parent_param ) {
    $parent_id = (int) $parent_param;
    if ( ! $parent_id || $parent_id === (int) $post_id ) return 0;

    $parent_post = get_post( $parent_id );
    if ( ! $parent_post || $parent_post->post_type !== 'uticel' ) return 0;

    return $parent_id;
}

// ── GET /tpu/v1/uticelok – Lista ───────────────────────────────────────────────
function tpu_api_list( WP_REST_Request $req ) {
    $per_page = (int) $req->get_param( 'per_page' );
    $page     = (int) $req->get_param( 'page' );
    $search   = sanitize_text_field( $req->get_param( 'search' ) );
    $status   = $req->get_param( 'status' );
    $parent   = (int) $req->get_param( 'parent' );

    $args = array(
        'post_type'      => 'uticel',
        'post_status'    => $status === 'any' ? array( 'publish', 'draft' ) : $status,
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
    );

    if ( $search ) $args['s'] = $search;
    if ( $parent >= 0 ) $args['post_parent'] = $parent;

    $query = new WP_Query( $args );
    $items = array();
    foreach ( $query->posts as $post ) {
        $items[] = tpu_api_format( $post->ID );
    }

    return rest_ensure_response( array(
        'items'       => $items,
        'total'       => (int) $query->found_posts,
        'total_pages' => (int) $query->max_num_pages,
        'page'        => $page,
        'per_page'    => $per_page,
    ) );
}

// ── GET /tpu/v1/uticel/{id} ────────────────────────────────────────────────────
function tpu_api_get( WP_REST_Request $req ) {
    $id   = (int) $req->get_param( 'id' );
    $post = get_post( $id );

    if ( ! $post || $post->post_type !== 'uticel' ) {
        return new WP_Error( 'not_found', 'Úticél nem található', array( 'status' => 404 ) );
    }

    return rest_ensure_response( tpu_api_format( $id ) );
}

// ── POST /tpu/v1/uticel – Létrehozás ───────────────────────────────────────────
function tpu_api_create( WP_REST_Request $req ) {
    $post_id = wp_insert_post( array(
        'post_type'    => 'uticel',
        'post_title'   => $req->get_param( 'title' ),
        'post_content' => wp_kses_post( (string) $req->get_param( 'content' ) ),
        'post_status'  => $req->get_param( 'status' ) ?: 'publish',
    ), true );

    if ( is_wp_error( $post_id ) ) {
        return new WP_Error( 'insert_failed', $post_id->get_error_message(), array( 'status' => 500 ) );
    }

    if ( $req->get_param( 'parent' ) !== null ) {
        $parent_id = tpu_api_resolve_parent( $post_id, $req->get_param( 'parent' ) );
        wp_update_post( array( 'ID' => $post_id, 'post_parent' => $parent_id ) );
    }

    tpu_api_save_fields( $post_id, $req );

    return rest_ensure_response( tpu_api_format( $post_id ) );
}

// ── PUT /tpu/v1/uticel/{id} – Frissítés ────────────────────────────────────────
function tpu_api_update( WP_REST_Request $req ) {
    $id   = (int) $req->get_param( 'id' );
    $post = get_post( $id );

    if ( ! $post || $post->post_type !== 'uticel' ) {
        return new WP_Error( 'not_found', 'Úticél nem található', array( 'status' => 404 ) );
    }

    $update = array( 'ID' => $id );
    if ( $req->get_param( 'title' )   !== null ) $update['post_title']   = sanitize_text_field( $req->get_param( 'title' ) );
    if ( $req->get_param( 'content' ) !== null ) $update['post_content'] = wp_kses_post( $req->get_param( 'content' ) );
    if ( $req->get_param( 'status' )  !== null ) {
        $status               = $req->get_param( 'status' );
        $update['post_status'] = in_array( $status, array( 'publish', 'draft' ), true ) ? $status : $post->post_status;
    }
    if ( $req->get_param( 'parent' ) !== null ) {
        $update['post_parent'] = tpu_api_resolve_parent( $id, $req->get_param( 'parent' ) );
    }

    wp_update_post( $update );
    tpu_api_save_fields( $id, $req );

    return rest_ensure_response( tpu_api_format( $id ) );
}

// ── POST /tpu/v1/uticel/{id}/kep – Kiemelt kép sideload URL-ből ───────────────
function tpu_api_sideload_image( WP_REST_Request $req ) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $post_id = (int) $req->get_param( 'id' );
    $url     = esc_url_raw( $req->get_param( 'url' ) );

    $post = get_post( $post_id );
    if ( ! $post || $post->post_type !== 'uticel' ) {
        return new WP_Error( 'not_found', 'Úticél nem található', array( 'status' => 404 ) );
    }
    if ( ! $url ) {
        return new WP_Error( 'no_url', 'URL megadása kötelező', array( 'status' => 400 ) );
    }

    $tmp = download_url( $url, 30 );
    if ( is_wp_error( $tmp ) ) {
        return new WP_Error( 'download_failed', 'Kép letöltése sikertelen: ' . $tmp->get_error_message(), array( 'status' => 500 ) );
    }

    $file_name = basename( parse_url( $url, PHP_URL_PATH ) );
    if ( ! pathinfo( $file_name, PATHINFO_EXTENSION ) ) $file_name .= '.jpg';
    $file_name = sanitize_file_name( $file_name );

    $attachment_id = media_handle_sideload( array( 'name' => $file_name, 'tmp_name' => $tmp ), $post_id );

    if ( file_exists( $tmp ) ) @unlink( $tmp );

    if ( is_wp_error( $attachment_id ) ) {
        return new WP_Error( 'sideload_failed', 'Importálás sikertelen: ' . $attachment_id->get_error_message(), array( 'status' => 500 ) );
    }

    set_post_thumbnail( $post_id, $attachment_id );

    return rest_ensure_response( array(
        'attachment_id' => $attachment_id,
        'url'           => wp_get_attachment_image_url( $attachment_id, 'medium_large' ) ?: wp_get_attachment_url( $attachment_id ),
        'full_url'      => wp_get_attachment_url( $attachment_id ),
    ) );
}

// ── GET /tpu/v1/meta – Teljes flat lista a Portál fastruktúra-építéséhez ──────
function tpu_api_meta() {
    $posts = get_posts( array(
        'post_type'      => 'uticel',
        'post_status'    => array( 'publish', 'draft' ),
        'numberposts'    => -1,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
    ) );

    $items = array();
    foreach ( $posts as $p ) {
        $items[] = array(
            'id'     => $p->ID,
            'title'  => $p->post_title,
            'parent' => (int) $p->post_parent,
            'status' => $p->post_status,
        );
    }

    return rest_ensure_response( array( 'uticelok' => $items ) );
}

// ── GET /tpu/v1/status – Státusz / ping ─────────────────────────────────────────
function tpu_api_status() {
    return rest_ensure_response( array(
        'plugin'     => 'Travelpont Úticélok REST API',
        'version'    => TPU_VERSION,
        'endpoint'   => rest_url( 'tpu/v1/uticelok' ),
        'cpt_exists' => post_type_exists( 'uticel' ),
    ) );
}

<?php
/**
 * Travelpont Úticélok – REST API (CSONTVÁZ)
 *
 * Prefix: /wp-json/tpu/v1/
 *
 * JELENLEG csak a publikus /status ping él, a Travelpont Ajánlatok
 * plugin mintájára (includes/rest-api.php ott). Ez a fájl a HELYE a
 * későbbi portál-kommunikációnak, ha szükség lesz rá.
 *
 * Auth a write endpointokhoz majd: WordPress Application Password
 * (Basic Auth) + current_user_can( 'publish_posts' ).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'rest_api_init', function() {
    register_rest_route( 'tpu/v1', '/status', array(
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => 'tpu_api_status',
        'permission_callback' => '__return_true',
    ) );

    do_action( 'tpu_rest_api_init' );
} );

function tpu_api_status() {
    return rest_ensure_response( array(
        'plugin'     => 'Travelpont Úticélok REST API',
        'version'    => TPU_VERSION,
        'cpt_exists' => post_type_exists( 'uticel' ),
    ) );
}

<?php
/**
 * Plugin Name: Travelpont Úticélok
 * Plugin URI:  https://travelpont.hu
 * Description: Hierarchikus Úticél oldalak (ország → tájegység → város) – ACF-mentes, önálló plugin, a Travelpont Ajánlatok plugin mintájára.
 * Version:     1.0.0
 * Author:      travelpont.hu
 * Text Domain: travelpont-uticelok
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$tpu_plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ) );
define( 'TPU_VERSION', $tpu_plugin_data['Version'] );
define( 'TPU_PATH', plugin_dir_path( __FILE__ ) );
define( 'TPU_URL',  plugin_dir_url( __FILE__ ) );

// ── Gutenberg kikapcsolása az Úticél CPT-re – LEGELSŐ, minden require_once előtt ──
add_filter( 'use_block_editor_for_post_type', function( $use, $post_type ) {
    if ( $post_type === 'uticel' ) return false;
    return $use;
}, 10, 2 );

// ── Egyedi mezők panel elrejtése ──────────────────────────────────────────────
add_action( 'add_meta_boxes', function() {
    remove_meta_box( 'postcustom', 'uticel', 'normal' );
}, 99 );

// ── Modulok betöltése ─────────────────────────────────────────────────────────
require_once TPU_PATH . 'includes/fields.php';
require_once TPU_PATH . 'includes/cpt.php';
require_once TPU_PATH . 'includes/meta-boxes.php';
require_once TPU_PATH . 'includes/blog-kapcsolo.php';
require_once TPU_PATH . 'includes/shortcodes.php';
require_once TPU_PATH . 'includes/single-display.php';
require_once TPU_PATH . 'includes/rest-api.php';

// ── Aktiválás / deaktiválás: CPT regisztrálása + permalink szabályok frissítése ──
register_activation_hook( __FILE__, function() {
    tpu_register_cpt();
    tpu_add_rewrite_rules();
    flush_rewrite_rules();
} );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

// ── Frontend eszközök ─────────────────────────────────────────────────────────
// Csak regisztrálunk – a shortcode és a single nézet tölti be ténylegesen,
// így az oldal többi részét nem lassítjuk fölöslegesen.
add_action( 'wp_enqueue_scripts', function() {
    wp_register_style(
        'travelpont-uticelok',
        TPU_URL . 'assets/css/frontend.css',
        array(), TPU_VERSION
    );
    if ( is_singular( 'uticel' ) ) {
        wp_enqueue_style( 'travelpont-uticelok' );
    }
} );

// Ha a Travelpont Ajánlatok plugin is aktív, az Úticél oldalon megjelenő
// kapcsolódó ajánlat-kártyák az ő stílusát használják (kártyarács, gombok).
// Alacsonyabb prioritás, hogy a 'travelpont-ajanlatok' stílus addigra
// biztosan regisztrálva legyen.
add_action( 'wp_enqueue_scripts', function() {
    if ( is_singular( 'uticel' ) && wp_style_is( 'travelpont-ajanlatok', 'registered' ) ) {
        wp_enqueue_style( 'travelpont-ajanlatok' );
    }
}, 20 );

// ── Admin eszközök ────────────────────────────────────────────────────────────
add_action( 'admin_enqueue_scripts', function() {
    global $post_type;
    if ( $post_type !== 'uticel' && $post_type !== 'post' ) return;
    wp_enqueue_style(
        'travelpont-uticelok-admin',
        TPU_URL . 'assets/css/admin.css',
        array(), TPU_VERSION
    );
} );

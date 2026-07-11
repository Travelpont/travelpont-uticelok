<?php
/**
 * Travelpont Úticélok – Meta boxok (admin adatbeviteli űrlap)
 *
 * FONTOS: itt NINCS beégetett mezőlista – minden a fields.php központi
 * definícióiból épül fel automatikusan. Új mező = új bejegyzés ott.
 * (A "Szülő" mezőt maga a WordPress adja a hierarchikus post type-okhoz,
 * ide nem kell külön megépíteni.)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Meta boxok regisztrálása a szekciók alapján ───────────────────────────────
add_action( 'add_meta_boxes', function() {
    foreach ( tpu_get_sections() as $section_id => $section ) {
        add_meta_box(
            'tpu_box_' . $section_id,
            $section['title'],
            function( $post ) use ( $section_id ) {
                tpu_render_section( $post, $section_id );
            },
            'uticel',
            isset( $section['context'] )  ? $section['context']  : 'normal',
            isset( $section['priority'] ) ? $section['priority'] : 'default'
        );
    }
} );

// ── Egy szekció mezőinek kirajzolása ──────────────────────────────────────────
function tpu_render_section( $post, $section_id ) {
    static $nonce_done = false;
    if ( ! $nonce_done ) {
        wp_nonce_field( 'tpu_save_meta', 'tpu_nonce' );
        $nonce_done = true;
    }

    echo '<div class="tpu-admin-fields">';
    foreach ( tpu_get_fields() as $key => $field ) {
        if ( ( isset( $field['section'] ) ? $field['section'] : '' ) !== $section_id ) continue;
        tpu_render_field( $post->ID, $key, $field );
    }
    echo '</div>';
}

// ── Egy mező kirajzolása típus szerint ────────────────────────────────────────
function tpu_render_field( $post_id, $key, $field ) {
    $value       = get_post_meta( $post_id, $key, true );
    $type        = isset( $field['type'] ) ? $field['type'] : 'text';
    $placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

    if ( $value === '' && isset( $field['default'] ) && get_post_status( $post_id ) === 'auto-draft' ) {
        $value = $field['default'];
    }

    // Szint-függő mező: data-szint attribútum, amit az admin-szint.js használ a
    // megjelenítés szűkítésére (üres = minden szinten látszik).
    $szint_attr = '';
    if ( ! empty( $field['szint'] ) && is_array( $field['szint'] ) ) {
        $szint_attr = ' data-szint="' . esc_attr( implode( ' ', $field['szint'] ) ) . '"';
    }

    echo '<div class="tpu-field tpu-field-' . esc_attr( $type ) . '"' . $szint_attr . '>';
    echo '<label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $field['label'] ) . '</strong></label>';

    switch ( $type ) {
        case 'textarea':
            printf(
                '<textarea id="%1$s" name="%1$s" rows="3" placeholder="%2$s">%3$s</textarea>',
                esc_attr( $key ), esc_attr( $placeholder ), esc_textarea( $value )
            );
            break;

        case 'select':
            echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
            $options = isset( $field['options'] ) ? $field['options'] : array();
            foreach ( $options as $opt_value => $opt_label ) {
                printf(
                    '<option value="%s"%s>%s</option>',
                    esc_attr( $opt_value ),
                    selected( $value, $opt_value, false ),
                    esc_html( $opt_label )
                );
            }
            echo '</select>';
            break;

        case 'number':
            printf(
                '<input type="number" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s" min="0" step="1">',
                esc_attr( $key ), esc_attr( $value ), esc_attr( $placeholder )
            );
            break;

        case 'date':
            printf(
                '<input type="date" id="%1$s" name="%1$s" value="%2$s">',
                esc_attr( $key ), esc_attr( $value )
            );
            break;

        case 'url':
            printf(
                '<input type="url" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s" class="tpu-wide">',
                esc_attr( $key ), esc_attr( $value ), esc_attr( $placeholder )
            );
            break;

        default: // text
            printf(
                '<input type="text" id="%1$s" name="%1$s" value="%2$s" placeholder="%3$s" class="tpu-wide">',
                esc_attr( $key ), esc_attr( $value ), esc_attr( $placeholder )
            );
    }

    if ( ! empty( $field['desc'] ) ) {
        echo '<p class="description">' . esc_html( $field['desc'] ) . '</p>';
    }
    echo '</div>';
}

// ── Mentés – típus szerinti sanitizálással ────────────────────────────────────
add_action( 'save_post_uticel', function( $post_id ) {
    if ( ! isset( $_POST['tpu_nonce'] ) || ! wp_verify_nonce( $_POST['tpu_nonce'], 'tpu_save_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    foreach ( tpu_get_fields() as $key => $field ) {
        if ( ! isset( $_POST[ $key ] ) ) continue;

        $raw   = wp_unslash( $_POST[ $key ] );
        $type  = isset( $field['type'] ) ? $field['type'] : 'text';
        $value = tpu_sanitize_field_value( $type, $raw, $field );

        update_post_meta( $post_id, $key, $value );
    }

    do_action( 'tpu_after_save_meta', $post_id ); // bővítési pont (pl. későbbi portál-szinkron)
} );

<?php
/**
 * Travelpont Úticélok – KÖZPONTI MEZŐ-DEFINÍCIÓK
 *
 * A Travelpont Ajánlatok pluginhoz hasonlóan minden egyedi mező itt van
 * definiálva, EGY helyen. A meta boxok (admin űrlap), a mentés/sanitizálás
 * és a sablonok is ebből a listából dolgoznak.
 *
 * ÚJ MEZŐ HOZZÁADÁSA = egyetlen új bejegyzés ebbe a tömbbe.
 * (A megjelenítéshez a sablonban: tpu_mezo( get_the_ID(), 'tpu_uj_mezo' ) )
 *
 * A "kép" mező szándékosan nincs itt: az Úticél a natív kiemelt képet
 * (featured image / thumbnail) használja, ugyanúgy, mint az Ajánlat CPT.
 *
 * Támogatott típusok: text, number, url, date, select, textarea
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Mező-szekciók (admin meta boxok) ──────────────────────────────────────────
function tpu_get_sections() {
    $sections = array(
        'alapadatok' => array( 'title' => '🌍 Alapadatok', 'context' => 'normal', 'priority' => 'high' ),
    );
    return apply_filters( 'tpu_sections', $sections );
}

// ── Mezők ─────────────────────────────────────────────────────────────────────
function tpu_get_fields() {
    $fields = array(

        // 🌍 Alapadatok
        'tpu_leiras' => array(
            'label'       => 'Rövid leíró szöveg',
            'type'        => 'textarea',
            'section'     => 'alapadatok',
            'placeholder' => 'pl. Horvátország napfényes tengerpartjai és történelmi városai.',
            'desc'        => 'Az Úticél oldal tetején, a kiemelt kép alatt megjelenő rövid szöveg (1-2 mondat).',
        ),
    );
    return apply_filters( 'tpu_fields', $fields );
}

// ── Mezőérték lekérése (default-tal) ──────────────────────────────────────────
function tpu_mezo( $post_id, $key ) {
    $value = get_post_meta( $post_id, $key, true );
    if ( $value !== '' && $value !== null ) return $value;

    $fields = tpu_get_fields();
    return isset( $fields[ $key ]['default'] ) ? $fields[ $key ]['default'] : '';
}

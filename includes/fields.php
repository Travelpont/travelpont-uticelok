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
 *
 * SZINT-FÜGGŐ MEZŐK: minden mezőnek lehet egy 'szint' kulcsa (tömb), ami megadja,
 * hogy az adott mező mely úticél-szinteknél releváns (orszag / regio / varos / egyeb).
 * Ha nincs 'szint' kulcs, a mező minden szintnél megjelenik. Ezt használja
 * a meta-boxes.php (admin – data-szint attribútum + admin-szint.js el/kirejtés)
 * és a single-content.php (frontend – szint szerinti elrendezés).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Választható úticél-szintek (egy helyen, több modul is ebből dolgozik) ──────
function tpu_get_szintek() {
    return apply_filters( 'tpu_szintek', array(
        'orszag' => 'Ország',
        'regio'  => 'Régió / tájegység',
        'varos'  => 'Város / település',
        'egyeb'  => 'Egyéb terület',
    ) );
}

// ── Mező-szekciók (admin meta boxok) ──────────────────────────────────────────
function tpu_get_sections() {
    $sections = array(
        'alapadatok'    => array( 'title' => '🌍 Alapadatok',   'context' => 'normal', 'priority' => 'high' ),
        'orszag_adatok' => array( 'title' => 'ℹ️ Ország-adatok', 'context' => 'normal', 'priority' => 'default' ),
        'gyakorlati'    => array( 'title' => '✈️ Gyakorlati infó', 'context' => 'normal', 'priority' => 'default' ),
        'terkep'        => array( 'title' => '🗺️ Térkép',        'context' => 'normal', 'priority' => 'default' ),
    );
    return apply_filters( 'tpu_sections', $sections );
}

// ── Mezők ─────────────────────────────────────────────────────────────────────
function tpu_get_fields() {
    $fields = array(

        // 🌍 Alapadatok
        'tpu_szint' => array(
            'label'   => 'Szint',
            'type'    => 'select',
            'section' => 'alapadatok',
            'options' => array( '' => '— válassz —' ) + tpu_get_szintek(),
            'desc'    => 'Mi ez az úticél a hierarchiában? Ez határozza meg az oldal felépítését és az itt kitölthető mezőket. (A szülő-gyerek kapcsolatot ettől függetlenül a jobb oldali „Úticél tulajdonságai" doboz Szülő mezője adja.)',
        ),
        'tpu_leiras' => array(
            'label'       => 'Rövid leíró szöveg',
            'type'        => 'textarea',
            'section'     => 'alapadatok',
            'placeholder' => 'pl. Horvátország napfényes tengerpartjai és történelmi városai.',
            'desc'        => 'Az Úticél oldal tetején, a kiemelt kép alatt megjelenő rövid szöveg (1-2 mondat).',
        ),

        // ℹ️ Ország-adatok (csak Ország szinten)
        'tpu_penznem' => array(
            'label'       => 'Pénznem',
            'type'        => 'text',
            'section'     => 'orszag_adatok',
            'szint'       => array( 'orszag' ),
            'placeholder' => 'pl. Euró (EUR)',
        ),
        'tpu_nyelv' => array(
            'label'       => 'Beszélt nyelv',
            'type'        => 'text',
            'section'     => 'orszag_adatok',
            'szint'       => array( 'orszag' ),
            'placeholder' => 'pl. olasz',
        ),
        'tpu_idozona' => array(
            'label'       => 'Időzóna',
            'type'        => 'text',
            'section'     => 'orszag_adatok',
            'szint'       => array( 'orszag' ),
            'placeholder' => 'pl. CET (UTC+1)',
        ),
        'tpu_beutazas' => array(
            'label'       => 'Be- és kiutazási tudnivaló',
            'type'        => 'textarea',
            'section'     => 'orszag_adatok',
            'szint'       => array( 'orszag' ),
            'placeholder' => 'pl. EU-tagország, útlevél nem szükséges, személyi igazolvány elég.',
            'desc'        => 'Vízum / EU / beutazási feltételek röviden.',
        ),

        // ✈️ Gyakorlati infó (Régió és Város szinten)
        'tpu_legjobb_idoszak' => array(
            'label'       => 'Legjobb utazási időszak',
            'type'        => 'text',
            'section'     => 'gyakorlati',
            'szint'       => array( 'regio', 'varos' ),
            'placeholder' => 'pl. május–szeptember',
        ),
        'tpu_repuloter' => array(
            'label'       => 'Legközelebbi repülőtér',
            'type'        => 'text',
            'section'     => 'gyakorlati',
            'szint'       => array( 'varos' ),
            'placeholder' => 'pl. Bolzano (BZO) / Verona (VRN)',
        ),
        'tpu_repules_ido' => array(
            'label'       => 'Repülési idő Budapestről',
            'type'        => 'text',
            'section'     => 'gyakorlati',
            'szint'       => array( 'varos' ),
            'placeholder' => 'pl. kb. 1 óra 30 perc',
        ),

        // 🗺️ Térkép (minden szinten)
        'tpu_terkep' => array(
            'label'       => 'Google Maps beágyazási URL',
            'type'        => 'url',
            'section'     => 'terkep',
            'url_prefix'  => 'https://www.google.com/maps/embed',
            'placeholder' => 'https://www.google.com/maps/embed?pb=...',
            'desc'        => 'A Google Maps → Megosztás → Térkép beágyazása → HTML „src” értéke (a https://www.google.com/maps/embed... kezdetű URL). Csak ilyen URL-t fogad el a rendszer.',
        ),
    );
    return apply_filters( 'tpu_fields', $fields );
}

// ── Mezőérték sanitizálása típus szerint (meta box mentés ÉS REST API közös) ──
function tpu_sanitize_field_value( $type, $raw, $field ) {
    switch ( $type ) {
        case 'number':
            return ( $raw === '' ) ? '' : (string) absint( $raw );
        case 'url':
            $url = esc_url_raw( $raw );
            // Ha a mező előírt URL-prefixet (pl. Google Maps embed), csak azzal
            // kezdődő URL-t fogadunk el – így nem fűzhető be tetszőleges iframe.
            if ( ! empty( $field['url_prefix'] ) && $url !== '' && strpos( $url, $field['url_prefix'] ) !== 0 ) {
                return '';
            }
            return $url;
        case 'date':
            return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ? $raw : '';
        case 'select':
            $options = isset( $field['options'] ) ? $field['options'] : array();
            return array_key_exists( $raw, $options ) ? $raw : '';
        case 'textarea':
            return sanitize_textarea_field( $raw );
        default:
            return sanitize_text_field( $raw );
    }
}

// ── Mezőérték lekérése (default-tal) ──────────────────────────────────────────
function tpu_mezo( $post_id, $key ) {
    $value = get_post_meta( $post_id, $key, true );
    if ( $value !== '' && $value !== null ) return $value;

    $fields = tpu_get_fields();
    return isset( $fields[ $key ]['default'] ) ? $fields[ $key ]['default'] : '';
}

// ── Egy úticél szintje + ember-olvasható címkéje ──────────────────────────────
function tpu_szint_erteke( $post_id ) {
    return tpu_mezo( $post_id, 'tpu_szint' );
}

function tpu_szint_cimke( $szint ) {
    $szintek = tpu_get_szintek();
    return isset( $szintek[ $szint ] ) ? $szintek[ $szint ] : '';
}

// ── Egy info-sor kiírása CSAK ha a mezőnek van értéke (frontend info-dobozok) ─
// Visszaadja a HTML-t (echo helyett), hogy a hívó eldönthesse, üres-e a doboz.
function tpu_info_sor( $post_id, $key, $cimke ) {
    $ertek = tpu_mezo( $post_id, $key );
    if ( $ertek === '' || $ertek === null ) return '';
    return '<div class="tpu-info-sor"><span class="tpu-info-cimke">' . esc_html( $cimke )
         . '</span><span class="tpu-info-ertek">' . esc_html( $ertek ) . '</span></div>';
}

<?php
/**
 * Travelpont Úticélok – Úticél-doboz az aloldalon
 * (a leírás elé fűzve jelenik meg, lásd includes/single-display.php)
 *
 * Az elrendezés a "Szint" (tpu_szint) mezőtől függ:
 *  - ORSZÁG: ország-adatok doboz + "Régiók" rács + ország összes ajánlata
 *  - RÉGIÓ:  legjobb időszak + "Városok" rács + régió ajánlatai
 *  - VÁROS:  gyakorlati infó (repülőtér, repülési idő) + ajánlatok elöl
 *  - egyéb / nincs kitöltve: az általános, visszafelé kompatibilis elrendezés
 *
 * Minden szinten közös: morzsamenü, rövid leírás, térkép, galéria, kapcsolódó
 * blog cikkek. Az ajánlatokat a bejegyzéshez VAGY bármely leszármazottjához
 * kötve gyűjti (rekurzív) – így egy ország oldalán az összes alá tartozó ajánlat.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$post_id        = get_the_ID();
$szint          = tpu_szint_erteke( $post_id );
$leiras         = tpu_mezo( $post_id, 'tpu_leiras' );
$terkep         = tpu_mezo( $post_id, 'tpu_terkep' );
$osok           = array_reverse( get_post_ancestors( $post_id ) );
$leszarmazottak = tpu_get_leszarmazott_idk( $post_id );
$sajat_es_leszarmazottak = array_merge( array( $post_id ), $leszarmazottak );

// A gyerek-rács címe a szinttől függ.
$gyerek_cim = 'Ebben az úticélban';
if ( $szint === 'orszag' ) {
    $gyerek_cim = 'Régiók, tájegységek';
} elseif ( $szint === 'regio' ) {
    $gyerek_cim = 'Városok, települések';
}

// Város szinten az ajánlatok a gyerek-rács ELÉ kerülnek (hangsúlyos), egyébként utána.
$sorrend = ( $szint === 'varos' ) ? array( 'ajanlatok', 'gyerekek' ) : array( 'gyerekek', 'ajanlatok' );
?>
<div class="tpu-single-doboz tpu-szint-<?php echo esc_attr( $szint ?: 'nincs' ); ?>">

    <?php
    // A gyökér "Úticélok" link csak akkor jelenik meg, ha van hozzá beállított
    // áttekintő oldal (lásd tpu_uticelok_szulo_url filter, pl. egy Elementor
    // oldal a [travelpont_uticelok] shortcode-dal).
    $tpu_gyoker_url = apply_filters( 'tpu_uticelok_gyoker_url', '' );
    ?>
    <?php if ( $osok || $tpu_gyoker_url ) : ?>
        <nav class="tpu-morzsamenu" aria-label="Morzsamenü">
            <?php if ( $tpu_gyoker_url ) : ?>
                <a href="<?php echo esc_url( $tpu_gyoker_url ); ?>">Úticélok</a> »
            <?php endif; ?>
            <?php foreach ( $osok as $os_id ) : ?>
                <a href="<?php echo esc_url( get_permalink( $os_id ) ); ?>"><?php echo esc_html( get_the_title( $os_id ) ); ?></a> »
            <?php endforeach; ?>
            <span><?php the_title(); ?></span>
        </nav>
    <?php endif; ?>

    <?php if ( $leiras ) : ?>
        <p class="tpu-single-leiras"><?php echo esc_html( $leiras ); ?></p>
    <?php endif; ?>

    <?php
    // ── Szint-függő info-doboz ────────────────────────────────────────────────
    if ( $szint === 'orszag' ) {
        $sorok  = tpu_info_sor( $post_id, 'tpu_penznem',  'Pénznem' );
        $sorok .= tpu_info_sor( $post_id, 'tpu_nyelv',    'Nyelv' );
        $sorok .= tpu_info_sor( $post_id, 'tpu_idozona',  'Időzóna' );
        $sorok .= tpu_info_sor( $post_id, 'tpu_beutazas', 'Beutazás' );
        if ( $sorok ) {
            echo '<div class="tpu-info-doboz"><h2 class="tpu-info-cim">Jó tudni</h2>' . $sorok . '</div>';
        }
    } elseif ( $szint === 'regio' ) {
        $sorok = tpu_info_sor( $post_id, 'tpu_legjobb_idoszak', 'Legjobb időszak' );
        if ( $sorok ) {
            echo '<div class="tpu-info-doboz">' . $sorok . '</div>';
        }
    } elseif ( $szint === 'varos' ) {
        $sorok  = tpu_info_sor( $post_id, 'tpu_legjobb_idoszak', 'Legjobb időszak' );
        $sorok .= tpu_info_sor( $post_id, 'tpu_repuloter',       'Legközelebbi repülőtér' );
        $sorok .= tpu_info_sor( $post_id, 'tpu_repules_ido',     'Repülési idő Budapestről' );
        if ( $sorok ) {
            echo '<div class="tpu-info-doboz"><h2 class="tpu-info-cim">Gyakorlati infó</h2>' . $sorok . '</div>';
        }
    }
    ?>

    <?php
    // ── Térkép (minden szinten, ha van beágyazási URL) ────────────────────────
    if ( $terkep ) : ?>
        <div class="tpu-terkep">
            <iframe src="<?php echo esc_url( $terkep ); ?>" width="100%" height="360" style="border:0;" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="<?php echo esc_attr( get_the_title() ); ?> térkép"></iframe>
        </div>
    <?php endif; ?>

    <?php
    // A galéria-képek megjelenítése (amit a szerkesztő nem helyezett el kézzel
    // a szövegben) a cikk törzsszövege UTÁN történik — lásd single-display.php.
    ?>

    <?php
    // ── Gyerek úticélok és Ajánlatok szint-függő sorrendben ───────────────────
    foreach ( $sorrend as $blokk ) :

        if ( $blokk === 'gyerekek' ) :
            // A gyerekeket a SAJÁT szintjük szerint külön blokkokba csoportosítjuk
            // (pl. egy ország alatt külön „Régiók" és külön „Városok"), hogy a
            // különböző szintű aloldalak sose keveredjenek egy közös rácsban.
            $gyerekek = get_children( array(
                'post_parent' => $post_id,
                'post_type'   => 'uticel',
                'post_status' => 'publish',
                'orderby'     => 'menu_order title',
                'order'       => 'ASC',
                'numberposts' => -1,
            ) );
            if ( $gyerekek ) :
                $csoportok = array( 'orszag' => array(), 'regio' => array(), 'varos' => array(), 'egyeb' => array() );
                foreach ( $gyerekek as $gy ) {
                    $gy_szint = tpu_mezo( $gy->ID, 'tpu_szint' );
                    if ( ! isset( $csoportok[ $gy_szint ] ) ) $gy_szint = 'egyeb'; // '' vagy ismeretlen
                    $csoportok[ $gy_szint ][] = $gy->ID;
                }
                // Ha egyetlen gyereknek sincs szintje, marad az egy közös blokk a
                // szülő szintje szerinti címmel (visszafelé kompatibilis a régi adatokkal).
                $van_tipizalt = $csoportok['orszag'] || $csoportok['regio'] || $csoportok['varos'];
                $csoport_cimek = array(
                    'orszag' => 'Országok',
                    'regio'  => 'Régiók, tájegységek',
                    'varos'  => 'Városok, települések',
                    'egyeb'  => $van_tipizalt ? 'További úticélok' : $gyerek_cim,
                );
                foreach ( array( 'orszag', 'regio', 'varos', 'egyeb' ) as $cs ) {
                    if ( empty( $csoportok[ $cs ] ) ) continue;
                    $tpu_query = new WP_Query( array(
                        'post_type'      => 'uticel',
                        'post_status'    => 'publish',
                        'post__in'       => $csoportok[ $cs ],
                        'orderby'        => 'menu_order title',
                        'order'          => 'ASC',
                        'posts_per_page' => -1,
                    ) );
                    // Gyerek-úticélok fotó-mozaikként (kép + név), hogy sok gyerek
                    // (pl. Olaszország 20 régiója) se nyújtsa el az oldalt.
                    $tpu_atts = array( 'oszlopok' => 3, 'nezet' => 'mozaik' );
                    echo '<h2 class="tpu-single-alcim">' . esc_html( $csoport_cimek[ $cs ] ) . '</h2>';
                    include TPU_PATH . 'templates/lista-template.php';
                    wp_reset_postdata();
                }
            endif;

        elseif ( $blokk === 'ajanlatok' ) :
            // 2026-07-19: az ajánlat-blokk KIVEZETVE a tartalomból (Gabesz
            // döntése) – az ajánlatok az OLDALSÁVBÓL jönnek, a Travelpont
            // Ajánlatok plugin (v1.17.0+) shortcode-jával:
            //   [travelpont_ajanlatok limit="4" uticel="aktualis" oszlopok="1" nezet="kompakt"]
            // A shortcode ugyanazt a kört fedi le (úticél + leszármazottak).
            // A blokk-ág üresen marad, hogy a Portálban mentett blokk-sorrendek
            // változatlanul érvényesek maradjanak.
            ;
        endif;

    endforeach;
    ?>

    <?php
    // ── Kapcsolódó Blog cikkek ─────────────────────────────────────────────────
    $cikkek_query = new WP_Query( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'tpu_kapcsolt_uticel',
                'value'   => $sajat_es_leszarmazottak,
                'compare' => 'IN',
            ),
        ),
    ) );
    if ( $cikkek_query->have_posts() ) :
        ?>
        <h2 class="tpu-single-alcim">Kapcsolódó cikkeink</h2>
        <ul class="tpu-egyszeru-lista">
            <?php while ( $cikkek_query->have_posts() ) : $cikkek_query->the_post(); ?>
                <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
            <?php endwhile; ?>
        </ul>
        <?php wp_reset_postdata();
    endif;
    ?>

    <?php do_action( 'tpu_single_doboz_vege', $post_id ); // bővítési pont ?>
</div>

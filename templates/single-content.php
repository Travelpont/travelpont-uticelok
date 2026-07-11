<?php
/**
 * Travelpont Úticélok – Úticél-doboz az aloldalon
 * (a leírás elé fűzve jelenik meg, lásd includes/single-display.php)
 *
 * Tartalma:
 *  - morzsamenü (ország > tájegység > város)
 *  - rövid leíró szöveg
 *  - gyerek úticélok rácsa (ha vannak – pl. egy ország oldalán a városai)
 *  - a hozzá (és a leszármazottaihoz) kapcsolt Ajánlatok
 *  - a hozzá kapcsolt Blog cikkek
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$post_id       = get_the_ID();
$leiras        = tpu_mezo( $post_id, 'tpu_leiras' );
$osok          = array_reverse( get_post_ancestors( $post_id ) );
$leszarmazottak = tpu_get_leszarmazott_idk( $post_id );
$sajat_es_leszarmazottak = array_merge( array( $post_id ), $leszarmazottak );
?>
<div class="tpu-single-doboz">

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
    // ── Galéria (a Portálból feltöltött további fotók, tpu_galeria_ids meta) ──
    $galeria_idk = get_post_meta( $post_id, 'tpu_galeria_ids', true );
    $galeria_idk = is_array( $galeria_idk ) ? array_map( 'intval', $galeria_idk ) : array();
    if ( $galeria_idk ) : ?>
        <div class="tpu-galeria">
            <?php foreach ( $galeria_idk as $kep_id ) :
                if ( ! wp_attachment_is_image( $kep_id ) ) continue; ?>
                <a href="<?php echo esc_url( wp_get_attachment_url( $kep_id ) ); ?>" class="tpu-galeria-elem" target="_blank" rel="noopener">
                    <?php echo wp_get_attachment_image( $kep_id, 'medium_large', false, array( 'loading' => 'lazy', 'alt' => get_the_title( $post_id ) ) ); ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php
    // ── Gyerek úticélok (pl. egy ország oldalán a tájegységei/városai) ────────
    $gyerekek_query = new WP_Query( array(
        'post_type'      => 'uticel',
        'post_status'    => 'publish',
        'post_parent'    => $post_id,
        'posts_per_page' => -1,
        'orderby'        => 'menu_order title',
        'order'          => 'ASC',
    ) );
    if ( $gyerekek_query->have_posts() ) :
        $tpu_query = $gyerekek_query;
        $tpu_atts  = array( 'oszlopok' => 3 );
        ?>
        <h2 class="tpu-single-alcim">Ebben az úticélban</h2>
        <?php include TPU_PATH . 'templates/lista-template.php'; ?>
    <?php endif; wp_reset_postdata(); ?>

    <?php
    // ── Kapcsolódó Ajánlatok (a Travelpont Ajánlatok plugin mezője alapján) ───
    if ( post_type_exists( 'ajanlat' ) ) :
        $ajanlatok_query = new WP_Query( array(
            'post_type'      => 'ajanlat',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => 'tpa_uticel',
                    'value'   => $sajat_es_leszarmazottak,
                    'compare' => 'IN',
                ),
            ),
        ) );
        if ( $ajanlatok_query->have_posts() ) :
            ?>
            <h2 class="tpu-single-alcim">Ajánlataink ehhez az úticélhoz</h2>
            <?php if ( function_exists( 'tpa_mezo' ) && defined( 'TPA_PATH' ) ) : ?>
                <div class="tpa-grid">
                    <?php while ( $ajanlatok_query->have_posts() ) : $ajanlatok_query->the_post(); ?>
                        <?php include TPA_PATH . 'templates/card-template.php'; ?>
                    <?php endwhile; ?>
                </div>
            <?php else : ?>
                <ul class="tpu-egyszeru-lista">
                    <?php while ( $ajanlatok_query->have_posts() ) : $ajanlatok_query->the_post(); ?>
                        <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
        <?php endif;
    endif;
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

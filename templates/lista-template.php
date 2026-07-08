<?php
/**
 * Travelpont Úticélok – Lista (kártyarács) sablon
 * Bemenet: $tpu_query (WP_Query), $tpu_atts (shortcode attribútumok)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$oszlopok = max( 2, min( 4, (int) $tpu_atts['oszlopok'] ) );
$min_szelesseg = array( 2 => '340px', 3 => '270px', 4 => '220px' );
?>

<?php if ( $tpu_query->have_posts() ) : ?>
    <div class="tpu-grid" style="--tpu-card-min: <?php echo esc_attr( $min_szelesseg[ $oszlopok ] ); ?>;">
        <?php
        while ( $tpu_query->have_posts() ) :
            $tpu_query->the_post();
            include TPU_PATH . 'templates/card-template.php';
        endwhile;
        wp_reset_postdata();
        ?>
    </div>
<?php else : ?>
    <p class="tpu-empty"><?php echo esc_html( apply_filters( 'tpu_ures_lista_szoveg', 'Jelenleg nincs feltöltött úticél ebben a kategóriában.' ) ); ?></p>
<?php endif; ?>

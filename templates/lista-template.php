<?php
/**
 * Travelpont Úticélok – Lista (kártyarács) sablon
 * Bemenet: $tpu_query (WP_Query), $tpu_atts (shortcode attribútumok)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$oszlopok = max( 2, min( 4, (int) $tpu_atts['oszlopok'] ) );
$min_szelesseg = array( 2 => '340px', 3 => '270px', 4 => '220px' );

// nezet: "kartya" (alap, hover-overlay kártyák) | "mozaik" (kép + név csempék)
$nezet = ( isset( $tpu_atts['nezet'] ) && $tpu_atts['nezet'] === 'mozaik' ) ? 'mozaik' : 'kartya';
$racs_osztaly = 'tpu-grid' . ( $nezet === 'mozaik' ? ' tpu-grid--mozaik' : '' );
$racs_min     = ( $nezet === 'mozaik' ) ? '190px' : $min_szelesseg[ $oszlopok ];
$elem_sablon  = ( $nezet === 'mozaik' ) ? 'templates/mozaik-csempe.php' : 'templates/card-template.php';
?>

<?php if ( $tpu_query->have_posts() ) : ?>
    <div class="<?php echo esc_attr( $racs_osztaly ); ?>" style="--tpu-card-min: <?php echo esc_attr( $racs_min ); ?>;">
        <?php
        while ( $tpu_query->have_posts() ) :
            $tpu_query->the_post();
            include TPU_PATH . $elem_sablon;
        endwhile;
        wp_reset_postdata();
        ?>
    </div>
<?php else : ?>
    <p class="tpu-empty"><?php echo esc_html( apply_filters( 'tpu_ures_lista_szoveg', 'Jelenleg nincs feltöltött úticél ebben a kategóriában.' ) ); ?></p>
<?php endif; ?>

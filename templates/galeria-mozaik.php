<?php
/**
 * Travelpont Úticélok – "További fotóink" mozaik
 * A szövegben fel nem használt galéria-képek rendezett csemperácsa,
 * a cikk törzsszövege UTÁN. Bemenet: $tpu_tovabbi_kep_idk (attachment ID-k).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( empty( $tpu_tovabbi_kep_idk ) ) return;
?>
<h2 class="tpu-single-alcim">További fotóink</h2>
<div class="tpu-grid tpu-grid--mozaik" style="--tpu-card-min: 190px;">
    <?php foreach ( $tpu_tovabbi_kep_idk as $kep_id ) :
        if ( ! wp_attachment_is_image( $kep_id ) ) continue;
        $felirat = wp_get_attachment_caption( $kep_id );
        $alt     = $felirat ? $felirat : get_the_title( $kep_id );
        ?>
        <a class="tpu-mozaik-csempe tpu-mozaik-csempe--galeria tpu-galeria-elem" href="<?php echo esc_url( wp_get_attachment_url( $kep_id ) ); ?>" data-caption="<?php echo esc_attr( $felirat ); ?>">
            <?php echo wp_get_attachment_image( $kep_id, 'medium_large', false, array( 'loading' => 'lazy', 'alt' => $alt ) ); ?>
            <?php if ( $felirat ) : ?>
                <span class="tpu-mozaik-nev"><?php echo esc_html( $felirat ); ?></span>
            <?php endif; ?>
        </a>
    <?php endforeach; ?>
</div>

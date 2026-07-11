<?php
/**
 * Travelpont Úticélok – Egy úticél-kártya sablonja
 * A loop-on belül fut (lista-template.php hívja).
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$post_id     = get_the_ID();
$leiras      = tpu_mezo( $post_id, 'tpu_leiras' );
$szint_cimke = tpu_szint_cimke( tpu_szint_erteke( $post_id ) );
?>
<article class="tpu-card">
    <a class="tpu-card-kep-link" href="<?php the_permalink(); ?>">
        <div class="tpu-card-kep">
            <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'medium_large' ); ?>
            <?php else : ?>
                <div class="tpu-card-kep-ures">🌍</div>
            <?php endif; ?>
            <?php if ( $szint_cimke ) : ?>
                <span class="tpu-card-badge"><?php echo esc_html( $szint_cimke ); ?></span>
            <?php endif; ?>
        </div>
    </a>

    <div class="tpu-card-torzs">
        <h3 class="tpu-card-cim">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>

        <?php if ( $leiras ) : ?>
            <p class="tpu-card-leiras"><?php echo esc_html( $leiras ); ?></p>
        <?php endif; ?>

        <a class="tpu-gomb" href="<?php the_permalink(); ?>">Felfedezem</a>
    </div>
</article>

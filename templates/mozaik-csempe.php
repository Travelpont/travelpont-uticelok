<?php
/**
 * Travelpont Úticélok – Fotó-mozaik csempe
 * Egy gyerek-úticél kompakt, képre épülő megjelenítése (kép + név a képen).
 * A loop-on belül fut (lista-template.php hívja, nezet="mozaik" esetén).
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<a class="tpu-mozaik-csempe" href="<?php the_permalink(); ?>">
    <?php if ( has_post_thumbnail() ) : ?>
        <?php the_post_thumbnail( 'medium_large' ); ?>
    <?php else : ?>
        <div class="tpu-mozaik-ures">🌍</div>
    <?php endif; ?>
    <span class="tpu-mozaik-nev"><?php the_title(); ?></span>
</a>

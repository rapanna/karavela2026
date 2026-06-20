<?php
/**
 * Detail průvodce (CPT pruvodce).
 * Vlevo foto v kartě (ala „Doporučujeme"), vpravo jméno + bio.
 * Drobečková navigace je globálně v header.php.
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>

<main role="main" class="content content--subpage content--trip">
  <div class="container">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
    <div class="row pruvodce-detail">
      <div class="col-sm-8 content--subpage__main">
        <h1 class="title title--text title--h1"><?php the_title(); ?></h1>
        <div class="thecontent"><?php the_content(); ?></div>
      </div>
      <div class="col-sm-4 content--subpage__sidebar">
        <?php if ( has_post_thumbnail() ) : ?>
          <div class="box box--sidebar pruvodce-detail__foto">
            <?php the_post_thumbnail( 'large', array( 'class' => 'box-link__img' ) ); ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endwhile; endif; ?>
  </div>
</main>

<?php get_footer(); ?>

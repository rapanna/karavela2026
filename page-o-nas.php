<?php
/**
 * Stránka Kontakty (slug: o-nas).
 * Obsah vlevo + pravý sidebar „Doporučujeme" (jako ostatní podstránky).
 * Drobečková navigace je globálně v header.php.
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>

<main role="main" class="content content--subpage">
  <div class="container">
    <div class="row">
      <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
      <div class="col-sm-8 content--subpage__main">
        <h1 class="title title--h1"><?php the_title(); ?></h1>
        <div class="thecontent"><?php the_content(); ?></div>
      </div>
      <?php endwhile; endif; ?>

      <?php get_template_part( 'includes/sidebar-doporucujeme' ); ?>
    </div>
  </div>
</main>

<?php get_footer(); ?>

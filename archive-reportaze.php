<?php
/**
 * Archiv reportáží (CPT reportaze).
 * Dlaždice s fotkou na pozadí + názvem, stránkování. Dle originálu.
 * Drobečková navigace je globálně v header.php.
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>

<main role="main" class="content content--subpage">
  <div class="container">
    <div class="row">
      <div class="col-sm-12">
        <h1 class="title title--h1">Reportáže z cest</h1>
      </div>
    </div>

    <?php if ( have_posts() ) : ?>
    <div class="row row-reportaze">
      <?php while ( have_posts() ) : the_post();
          $img = get_the_post_thumbnail_url( get_the_ID(), 'square_thumbnail' );
      ?>
      <div class="col-sm-4 col-md-3">
        <a href="<?php the_permalink(); ?>" class="reports-link" style="background-image: url('<?php echo esc_url( $img ); ?>');">
          <span class="reports-link__name"><?php the_title(); ?></span>
        </a>
      </div>
      <?php endwhile; ?>
    </div>

    <div class="row">
      <div class="col-sm-12">
        <nav class="pagination"><?php k26_pagination( $GLOBALS['wp_query'] ); ?></nav>
      </div>
    </div>
    <?php else : ?>
    <div class="row">
      <div class="col-sm-12"><p>Zatím zde nejsou žádné reportáže.</p></div>
    </div>
    <?php endif; ?>
  </div>
</main>

<?php get_footer(); ?>

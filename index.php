<?php get_header(); ?>

<main role="main" class="content content--hp">
  <div class="container">
    <div class="row">
      <div class="col-sm-12">
        <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
          <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
            <div class="entry-content"><?php the_excerpt(); ?></div>
          </article>
        <?php endwhile; else : ?>
          <p>Žádný obsah nenalezen.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>

<?php get_footer(); ?>

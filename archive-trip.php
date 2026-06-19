<?php get_header(); ?>

<main role="main" class="content content--subpage">
  <div class="container">
    <div class="row">
      <div class="col-sm-12">
        <h1 class="title title--h1">Zájezdy</h1>
      </div>
    </div>
    <div class="row recommended-trips">
      <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
        <div class="col-sm-4 col-md-3">
          <div class="box">
            <a href="<?php the_permalink(); ?>" class="box-link">
              <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'homepage_thumbnail', array( 'class' => 'box-link__img' ) ); ?>
              <?php endif; ?>
              <h2 class="box-link__title"><?php the_title(); ?></h2>
            </a>
          </div>
        </div>
      <?php endwhile; else : ?>
        <div class="col-sm-12"><p>Žádné zájezdy nenalezeny.</p></div>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php get_footer(); ?>

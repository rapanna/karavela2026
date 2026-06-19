<?php get_header(); ?>

<main role="main" class="content content--subpage">
  <div class="container">
    <div class="row">
      <div class="col-md-12 content--subpage__main">
        <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
          <h1 class="title title--h1"><?php the_title(); ?></h1>
          <div class="entry-content"><?php the_content(); ?></div>
        <?php endwhile; endif; ?>
      </div>
    </div>
  </div>
</main>

<?php get_footer(); ?>

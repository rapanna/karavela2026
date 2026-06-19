<?php
/**
 * Archiv zájezdů (CPT trip, URL /zajezd/) – „Seznam zájezdů".
 * Vypisuje VŠECHNY zájezdy jako dlaždice s nejbližším termínem a cenami,
 * seřazené podle data – stejný styl jako homepage „Doporučujeme".
 */
defined( 'ABSPATH' ) || exit;

get_header();

$q = new WP_Query( array(
    'post_type'      => 'trip',
    'posts_per_page' => -1,
    'no_found_rows'  => true,
    'post_status'    => 'publish',
) );

$trips = array();

if ( $q->have_posts() ) {
    while ( $q->have_posts() ) {
        $q->the_post();
        $id        = get_the_ID();
        $alt_title = get_post_meta( $id, '_karavela_title', true );
        $datumy    = karavela_tmps( $id );
        $trip_info = karavela_preffered_date( $datumy, $id );

        if ( empty( $trip_info ) ) {
            asort( $datumy );
            $trip_info = karavela_single_trip_date( $datumy );
        }

        if ( empty( $trip_info['tm_od_p'][0] ) ) continue;

        $cena_lm = is_numeric( $trip_info['tm_cena_lm_p'][0] ?? '' ) ? (int) $trip_info['tm_cena_lm_p'][0] : 0;
        $tm_od   = get_post_meta( $id, 'tm_od_p', true );

        $trips[ $id ] = array(
            'title'     => $alt_title ?: get_the_title(),
            'thumbnail' => get_the_post_thumbnail_url( $id, 'homepage_thumbnail' ),
            'link'      => get_the_permalink( $id ),
            'tm_od'     => date( 'd.m.Y', $trip_info['tm_od_p'][0] ),
            'tm_do'     => date( 'd.m.Y', $trip_info['tm_do_p'][0] ),
            'day_count' => (int) floor( ( $trip_info['tm_do_p'][0] - $trip_info['tm_od_p'][0] ) / DAY_IN_SECONDS ) + 1,
            'cena'      => $trip_info['tm_cena_celk_p'][0],
            'cena_lm'   => $cena_lm ? number_format( $cena_lm, 0, ',', ' ' ) : '',
            'infop'     => $trip_info['tm_info_p'][0] ?? '',
            'moreterm'  => count( (array) $tm_od ),
            'timestamp' => $trip_info['tm_od_p'][0],
        );
    }
    wp_reset_postdata();
}

usort( $trips, fn( $a, $b ) => $a['timestamp'] <=> $b['timestamp'] );
?>

<main role="main" class="content content--subpage">
  <div class="container">
    <div class="row">
      <div class="col-sm-12">
        <h1 class="title title--h1">Seznam zájezdů</h1>
      </div>
    </div>

    <div class="row recommended-trips">
      <?php if ( $trips ) : $i = 0; foreach ( $trips as $trip ) :
          $cena_default  = number_format( (float) $trip['cena'], 0, ',', ' ' );
          $cena_firstmin = firstminute( $trip['tm_od'], $trip['cena'] );
          $moreterm      = $trip['moreterm'];
          $hidden_cls    = $i >= 12 ? ' k26-trip-hidden' : '';
          $i++;
      ?>
        <div class="col-sm-4 col-md-3 k26-trip-card<?php echo $hidden_cls; ?>">
          <div class="box">
            <a href="<?php echo esc_url( $trip['link'] ); ?>" class="box-link">
              <?php if ( $trip['infop'] ) : ?>
                <div class="box-info"><?php echo esc_html( $trip['infop'] ); ?></div>
              <?php endif; ?>
              <?php if ( $trip['thumbnail'] ) : ?>
                <img src="<?php echo esc_url( $trip['thumbnail'] ); ?>" alt="<?php echo esc_attr( $trip['title'] ); ?>" style="max-width:100%;" class="box-link__img">
              <?php endif; ?>
              <h2 class="box-link__title"><?php echo esc_html( $trip['title'] ); ?></h2>
              <div class="box-link-date">
                <span class="box-link-date__term"><?php echo esc_html( $trip['tm_od'] ); ?> – <?php echo esc_html( $trip['tm_do'] ); ?></span>
                <span class="box-link-date__long"><?php echo esc_html( $trip['day_count'] ); ?> dní</span>
              </div>
              <div class="box-link-term">
                <?php if ( $moreterm <= 1 ) : ?>
                  <span class="box-link-term__count"></span>
                <?php elseif ( $moreterm === 2 ) : ?>
                  <span class="box-link-term__count">+ <strong>1</strong> další termín</span>
                <?php elseif ( $moreterm <= 5 ) : ?>
                  <span class="box-link-term__count">+ <strong><?php echo $moreterm - 1; ?></strong> další termíny</span>
                <?php else : ?>
                  <span class="box-link-term__count">+ <strong><?php echo $moreterm - 1; ?></strong> dalších termínů</span>
                <?php endif; ?>
              </div>
              <div class="box-link-cost">
                <span class="box-link-cost__default">Cena: <?php echo esc_html( $cena_default ); ?> Kč</span>
                <?php if ( $cena_firstmin ) : ?>
                  <span class="box-link-cost__fm">Cena FM: <?php echo esc_html( $cena_firstmin ); ?> Kč</span>
                <?php endif; ?>
                <?php if ( $trip['cena_lm'] ) : ?>
                  <span class="box-link-cost__fm">Cena LM: <?php echo esc_html( $trip['cena_lm'] ); ?> Kč</span>
                <?php endif; ?>
              </div>
            </a>
          </div>
        </div>
      <?php endforeach; else : ?>
        <div class="col-sm-12"><p>Žádné zájezdy nenalezeny.</p></div>
      <?php endif; ?>
    </div>

    <?php if ( count( $trips ) > 12 ) : ?>
    <div class="row">
      <div class="col-sm-12" style="text-align:center; margin-top:1em;">
        <button type="button" class="btn btn-info k26-load-more">Zobraz další zájezdy</button>
      </div>
    </div>
    <?php endif; ?>
  </div>
</main>

<?php get_footer(); ?>

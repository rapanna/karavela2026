<?php
/**
 * Template part: pravý sidebar „Doporučujeme".
 * 3 zájezdy z taxonomie categories → „Doporučujeme", řazené dle nejbližšího termínu.
 * Použito v page-reference.php a single-reportaze.php.
 */
defined( 'ABSPATH' ) || exit;

$dop = new WP_Query( array(
    'post_type'      => 'trip',
    'posts_per_page' => 3,
    'no_found_rows'  => true,
    'tax_query'      => array( array(
        'taxonomy' => 'categories',
        'field'    => 'name',
        'terms'    => 'Doporučujeme',
    ) ),
) );

$dop_trips = array();
if ( $dop->have_posts() ) {
    while ( $dop->have_posts() ) {
        $dop->the_post();
        $id        = get_the_ID();
        $alt_title = get_post_meta( $id, '_karavela_title', true );
        $datumy    = karavela_tmps( $id );
        $info      = karavela_preffered_date( $datumy, $id );
        if ( empty( $info ) ) {
            asort( $datumy );
            $info = karavela_single_trip_date( $datumy );
        }
        if ( empty( $info['tm_od_p'][0] ) ) continue;

        $dop_trips[] = array(
            'title'     => $alt_title ?: get_the_title(),
            'thumbnail' => get_the_post_thumbnail_url( $id, 'homepage_thumbnail' ),
            'link'      => get_permalink( $id ),
            'tm_od'     => date( 'd.m.Y', $info['tm_od_p'][0] ),
            'tm_do'     => date( 'd.m.Y', $info['tm_do_p'][0] ),
            'day_count' => (int) floor( ( $info['tm_do_p'][0] - $info['tm_od_p'][0] ) / DAY_IN_SECONDS ) + 1,
            'cena'      => $info['tm_cena_celk_p'][0],
            'timestamp' => $info['tm_od_p'][0],
        );
    }
    wp_reset_postdata();
}
usort( $dop_trips, fn( $a, $b ) => $a['timestamp'] <=> $b['timestamp'] );

if ( ! $dop_trips ) return;
?>
<div class="col-sm-4 content--subpage__sidebar">
  <h2 class="title title--h2">Doporučujeme</h2>
  <?php foreach ( $dop_trips as $t ) :
      $cena_default  = number_format( (float) $t['cena'], 0, ',', ' ' );
      $cena_firstmin = firstminute( $t['tm_od'], $t['cena'] );
  ?>
    <div class="box box--sidebar">
      <a href="<?php echo esc_url( $t['link'] ); ?>" class="box-link">
        <?php if ( $t['thumbnail'] ) : ?>
          <img src="<?php echo esc_url( $t['thumbnail'] ); ?>" alt="<?php echo esc_attr( $t['title'] ); ?>" style="max-width:100%;" class="box-link__img">
        <?php endif; ?>
        <h3 class="box-link__title"><?php echo esc_html( $t['title'] ); ?></h3>
        <div class="box-link-date">
          <span class="box-link-date__term"><?php echo esc_html( $t['tm_od'] ); ?> – <?php echo esc_html( $t['tm_do'] ); ?></span>
          <span class="box-link-date__long"><?php echo esc_html( $t['day_count'] ); ?> dní</span>
        </div>
        <div class="box-link-cost">
          <span class="box-link-cost__default">Cena: <?php echo esc_html( $cena_default ); ?> Kč</span>
          <?php if ( $cena_firstmin ) : ?>
            <span class="box-link-cost__fm">Cena FM: <?php echo esc_html( $cena_firstmin ); ?> Kč</span>
          <?php endif; ?>
        </div>
      </a>
    </div>
  <?php endforeach; ?>
</div>

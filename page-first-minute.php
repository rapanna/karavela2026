<?php
/**
 * Šablona stránky „First Minute" (slug: first-minute).
 * Vypisuje jednotlivé budoucí termíny, které mají First Minute cenu
 * (firstminute() vrací neprázdnou hodnotu – termín dál než 3 měsíce),
 * seřazené podle data odjezdu.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$page_title = get_the_title();

$dnes    = (int) strtotime( 'today UTC' );
$entries = array();

$q = new WP_Query( array(
    'post_type'      => 'trip',
    'posts_per_page' => -1,
    'no_found_rows'  => true,
    'post_status'    => 'publish',
) );

if ( $q->have_posts() ) {
    while ( $q->have_posts() ) {
        $q->the_post();
        $id = get_the_ID();

        $od = (array) ( get_post_meta( $id, 'tm_od_p', true ) ?: [] );
        if ( ! $od ) continue;

        $do   = (array) ( get_post_meta( $id, 'tm_do_p',        true ) ?: [] );
        $celk = (array) ( get_post_meta( $id, 'tm_cena_celk_p', true ) ?: [] );
        $info = (array) ( get_post_meta( $id, 'tm_info_p',      true ) ?: [] );

        $alt_title = get_post_meta( $id, '_karavela_title', true );
        $title     = $alt_title ?: get_the_title();
        $thumbnail = get_the_post_thumbnail_url( $id, 'homepage_thumbnail' );
        $link      = get_permalink( $id );

        foreach ( $od as $i => $od_val ) {
            $od_ts = (int) $od_val;
            if ( ! $od_ts || $od_ts < $dnes ) continue;

            $cena    = $celk[ $i ] ?? '';
            $cena_fm = $cena !== '' ? firstminute( date( 'd.m.Y', $od_ts ), $cena ) : '';
            if ( $cena_fm === '' ) continue; // jen termíny s First Minute cenou

            $do_ts = (int) ( $do[ $i ] ?? 0 );

            $entries[] = array(
                'timestamp' => $od_ts,
                'title'     => $title,
                'thumbnail' => $thumbnail,
                'link'      => $link,
                'tm_od'     => date( 'd.m.Y', $od_ts ),
                'tm_do'     => $do_ts ? date( 'd.m.Y', $do_ts ) : '',
                'day_count' => $do_ts ? (int) floor( ( $do_ts - $od_ts ) / DAY_IN_SECONDS ) + 1 : 0,
                'cena'      => $cena,
                'cena_fm'   => $cena_fm,
                'infop'     => $info[ $i ] ?? '',
            );
        }
    }
    wp_reset_postdata();
}

usort( $entries, fn( $a, $b ) => $a['timestamp'] <=> $b['timestamp'] );
?>

<main role="main" class="content content--subpage">
  <div class="container">
    <div class="row">
      <div class="col-sm-12">
        <h1 class="title title--h1"><?php echo esc_html( $page_title ); ?></h1>
      </div>
    </div>

    <div class="row recommended-trips">
      <?php if ( $entries ) : $idx = 0; foreach ( $entries as $e ) :
          $cena_default = number_format( (float) $e['cena'], 0, ',', ' ' );
          $hidden_cls   = $idx >= 12 ? ' k26-trip-hidden' : '';
          $idx++;
      ?>
        <div class="col-sm-4 col-md-3 k26-trip-card<?php echo $hidden_cls; ?>">
          <div class="box">
            <a href="<?php echo esc_url( $e['link'] ); ?>" class="box-link">
              <?php if ( $e['infop'] ) : ?>
                <div class="box-info"><?php echo esc_html( $e['infop'] ); ?></div>
              <?php endif; ?>
              <?php if ( $e['thumbnail'] ) : ?>
                <img src="<?php echo esc_url( $e['thumbnail'] ); ?>" alt="<?php echo esc_attr( $e['title'] ); ?>" style="max-width:100%;" class="box-link__img">
              <?php endif; ?>
              <h2 class="box-link__title"><?php echo esc_html( $e['title'] ); ?></h2>
              <div class="box-link-date">
                <span class="box-link-date__term"><?php echo esc_html( $e['tm_od'] ); ?><?php echo $e['tm_do'] ? ' – ' . esc_html( $e['tm_do'] ) : ''; ?></span>
                <?php if ( $e['day_count'] ) : ?>
                  <span class="box-link-date__long"><?php echo esc_html( $e['day_count'] ); ?> dní</span>
                <?php endif; ?>
              </div>
              <div class="box-link-cost">
                <span class="box-link-cost__default">Cena: <?php echo esc_html( $cena_default ); ?> Kč</span>
                <span class="box-link-cost__fm">Cena FM: <?php echo esc_html( $e['cena_fm'] ); ?> Kč</span>
              </div>
            </a>
          </div>
        </div>
      <?php endforeach; else : ?>
        <div class="col-sm-12"><p>Momentálně nejsou vypsány žádné First Minute termíny.</p></div>
      <?php endif; ?>
    </div>

    <?php if ( count( $entries ) > 12 ) : ?>
    <div class="row">
      <div class="col-sm-12" style="text-align:center; margin-top:1em;">
        <button type="button" class="btn btn-info k26-load-more">Zobraz další zájezdy</button>
      </div>
    </div>
    <?php endif; ?>
  </div>
</main>

<?php get_footer(); ?>

<?php
/**
 * Šablona stránky „Garance" (slug: garance).
 * Vypisuje jednotlivé garantované termíny napříč všemi zájezdy,
 * seřazené podle data odjezdu. Garance je per-termín: tm_garance_p[i] === '1'.
 */
defined( 'ABSPATH' ) || exit;

get_header();

// Titulek + případný obsah stránky
$page_title   = get_the_title();
$page_content = '';
if ( have_posts() ) {
    the_post();
    $page_content = get_the_content();
}

// ── Posbírej garantované termíny ze všech zájezdů ────────────────────────────
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

        $gar = (array) ( get_post_meta( $id, 'tm_garance_p', true ) ?: [] );
        if ( ! $gar ) continue;

        $kod  = (array) ( get_post_meta( $id, 'tm_kod_p',       true ) ?: [] );
        $od   = (array) ( get_post_meta( $id, 'tm_od_p',        true ) ?: [] );
        $do   = (array) ( get_post_meta( $id, 'tm_do_p',        true ) ?: [] );
        $celk = (array) ( get_post_meta( $id, 'tm_cena_celk_p', true ) ?: [] );
        $lm   = (array) ( get_post_meta( $id, 'tm_cena_lm_p',   true ) ?: [] );
        $info = (array) ( get_post_meta( $id, 'tm_info_p',      true ) ?: [] );

        $alt_title = get_post_meta( $id, '_karavela_title', true );
        $title     = $alt_title ?: get_the_title();
        $thumbnail = get_the_post_thumbnail_url( $id, 'homepage_thumbnail' );
        $link      = get_permalink( $id );

        foreach ( $gar as $i => $g ) {
            if ( ! in_array( $g, array( '1', 'Ano' ), true ) ) continue;

            $od_ts = (int) ( $od[ $i ] ?? 0 );
            $do_ts = (int) ( $do[ $i ] ?? 0 );
            if ( ! $od_ts || $od_ts < $dnes ) continue; // jen budoucí termíny

            $entries[] = array(
                'timestamp' => $od_ts,
                'title'     => $title,
                'thumbnail' => $thumbnail,
                'link'      => $link,
                'tm_od'     => date( 'd.m.Y', $od_ts ),
                'tm_do'     => $do_ts ? date( 'd.m.Y', $do_ts ) : '',
                'day_count' => $do_ts ? (int) floor( ( $do_ts - $od_ts ) / DAY_IN_SECONDS ) + 1 : 0,
                'cena'      => $celk[ $i ] ?? '',
                'cena_lm'   => is_numeric( $lm[ $i ] ?? '' ) ? (int) $lm[ $i ] : 0,
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
        <?php if ( trim( wp_strip_all_tags( $page_content ) ) !== '' ) : ?>
          <div class="entry-content"><?php echo apply_filters( 'the_content', $page_content ); ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="row recommended-trips">
      <?php if ( $entries ) : foreach ( $entries as $e ) :
          $cena_default  = is_numeric( $e['cena'] ) ? number_format( $e['cena'], 0, ',', ' ' ) : $e['cena'];
          $cena_firstmin = $e['cena'] !== '' ? firstminute( $e['tm_od'], $e['cena'] ) : '';
      ?>
        <div class="col-sm-4 col-md-3">
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
                <?php if ( $cena_default !== '' ) : ?>
                  <span class="box-link-cost__default">Cena: <?php echo esc_html( $cena_default ); ?> Kč</span>
                <?php endif; ?>
                <?php if ( $cena_firstmin ) : ?>
                  <span class="box-link-cost__fm">Cena FM: <?php echo esc_html( $cena_firstmin ); ?> Kč</span>
                <?php endif; ?>
                <?php if ( $e['cena_lm'] ) : ?>
                  <span class="box-link-cost__fm">Cena LM: <?php echo esc_html( number_format( $e['cena_lm'], 0, ',', ' ' ) ); ?> Kč</span>
                <?php endif; ?>
              </div>
            </a>
          </div>
        </div>
      <?php endforeach; else : ?>
        <div class="col-sm-12"><p>Momentálně nejsou vypsány žádné garantované termíny.</p></div>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php get_footer(); ?>

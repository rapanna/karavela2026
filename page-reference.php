<?php
/**
 * Šablona stránky „Reference" (slug: reference).
 * Hlavní sloupec = všechny reference napříč zájezdy (přes k26_get_references),
 * jednotlivě oddělené, s odkazem na zájezd; postupné načítání po 12.
 * Pravý sloupec = sidebar „Doporučujeme" (3 zájezdy z taxonomie Doporučujeme).
 */
defined( 'ABSPATH' ) || exit;

get_header();

// ── Posbírej všechny reference napříč zájezdy ────────────────────────────────
$ref_items = array();

$q = new WP_Query( array(
    'post_type'      => 'trip',
    'posts_per_page' => -1,
    'no_found_rows'  => true,
    'post_status'    => 'publish',
) );

if ( $q->have_posts() ) {
    while ( $q->have_posts() ) {
        $q->the_post();
        $id   = get_the_ID();
        $refs = k26_get_references( $id );
        if ( ! $refs ) continue;

        $alt_title = get_post_meta( $id, '_karavela_title', true );
        $trip_title = $alt_title ?: get_the_title();
        $trip_link  = get_permalink( $id );

        foreach ( $refs as $r ) {
            $ref_items[] = array(
                'jmeno'      => $r['jmeno'],
                'text'       => $r['text'],
                'foto_id'    => $r['foto_id'],
                'trip_title' => $trip_title,
                'trip_link'  => $trip_link,
            );
        }
    }
    wp_reset_postdata();
}

// Náhodné pořadí – při každém načtení jiné
shuffle( $ref_items );
?>

<main role="main" class="content content--subpage">
  <div class="container">
    <div class="row">
      <div class="col-sm-12">
        <h1 class="title title--h1"><?php the_title(); ?></h1>
      </div>
    </div>

    <div class="row">
      <!-- Hlavní sloupec: reference -->
      <div class="col-sm-8 content--subpage__main">
        <?php if ( $ref_items ) : $i = 0; foreach ( $ref_items as $it ) :
            $hidden_cls = $i >= 12 ? ' k26-trip-hidden' : '';
            $i++;
            $foto_url = $it['foto_id'] ? wp_get_attachment_image_url( $it['foto_id'], 'square_thumbnail' ) : '';
        ?>
          <div class="reference-item k26-trip-card<?php echo $hidden_cls; ?>">
            <?php if ( $foto_url ) : ?>
              <img class="reference-item__img" src="<?php echo esc_url( $foto_url ); ?>" alt="<?php echo esc_attr( $it['jmeno'] ); ?>">
            <?php endif; ?>
            <div class="reference-item__body">
              <?php if ( $it['jmeno'] !== '' ) : ?>
                <h4 class="reference-item__name"><?php echo esc_html( $it['jmeno'] ); ?></h4>
              <?php endif; ?>
              <div class="reference-item__text"><?php echo wpautop( wp_kses_post( $it['text'] ) ); ?></div>
              <a class="reference-item__trip" href="<?php echo esc_url( $it['trip_link'] ); ?>"><?php echo esc_html( $it['trip_title'] ); ?></a>
            </div>
          </div>
        <?php endforeach; else : ?>
          <p>Zatím nejsou k dispozici žádné reference.</p>
        <?php endif; ?>

        <?php if ( count( $ref_items ) > 12 ) : ?>
        <div style="text-align:center; margin-top:1.5em;">
          <button type="button" class="btn btn-info k26-load-more">Zobraz další reference</button>
        </div>
        <?php endif; ?>
      </div>

      <!-- Pravý sloupec: Doporučujeme -->
      <div class="col-sm-4 content--subpage__sidebar">
        <h2 class="title title--h2">Doporučujeme</h2>
        <?php
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

        foreach ( $dop_trips as $t ) :
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
    </div>
  </div>
</main>

<?php get_footer(); ?>

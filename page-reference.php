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
      <?php get_template_part( 'includes/sidebar-doporucujeme' ); ?>
    </div>
  </div>
</main>

<?php get_footer(); ?>

<?php
/**
 * Výsledky vyhledávání zájezdů.
 * - vyhledávání omezeno na CPT trip (pre_get_posts v functions.php),
 * - volitelný filtr termínu OD/DO (GET 'od','do') – ponechá jen zájezdy
 *   s termínem, jehož DATUM ODJEZDU spadá do zadaného rozmezí,
 * - karty stejné jako archiv (žlutý info pruh, cena, počet termínů).
 */
get_header();

$s_query = get_search_query();

$od_raw = isset( $_GET['od'] ) ? sanitize_text_field( wp_unslash( $_GET['od'] ) ) : '';
$do_raw = isset( $_GET['do'] ) ? sanitize_text_field( wp_unslash( $_GET['do'] ) ) : '';
$od_ts  = ( $od_raw && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $od_raw ) ) ? strtotime( $od_raw . ' 00:00:00 UTC' ) : 0;
$do_ts  = ( $do_raw && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $do_raw ) ) ? strtotime( $do_raw . ' 23:59:59 UTC' ) : 0;
$filter = ( $od_ts || $do_ts );
$today  = strtotime( 'today' );

// ── Sestav karty z výsledků (main query je už omezený na trip) ───────────────
$cards = array();

if ( have_posts() ) :
    while ( have_posts() ) : the_post();
        $id    = get_the_ID();
        $alt   = get_post_meta( $id, '_karavela_title', true );
        $title = $alt ?: get_the_title();

        $tm_od   = (array) ( get_post_meta( $id, 'tm_od_p',        true ) ?: [] );
        $tm_do   = (array) ( get_post_meta( $id, 'tm_do_p',        true ) ?: [] );
        $tm_celk = (array) ( get_post_meta( $id, 'tm_cena_celk_p', true ) ?: [] );
        $tm_fm   = (array) ( get_post_meta( $id, 'tm_cena_fm_p',   true ) ?: [] );
        $tm_lm   = (array) ( get_post_meta( $id, 'tm_cena_lm_p',   true ) ?: [] );
        $tm_info = (array) ( get_post_meta( $id, 'tm_info_p',      true ) ?: [] );

        // Vyber index zobrazovaného termínu
        $idx = -1;
        if ( $filter ) {
            // První termín, jehož ODJEZD spadá do rozmezí
            foreach ( $tm_od as $i => $od ) {
                if ( empty( $od ) ) continue;
                if ( ( ! $od_ts || $od >= $od_ts ) && ( ! $do_ts || $od <= $do_ts ) ) { $idx = $i; break; }
            }
            if ( $idx === -1 ) continue; // žádný termín v rozmezí → zájezd vynech
        } else {
            // Nejbližší budoucí termín, jinak nejbližší vůbec
            $best = PHP_INT_MAX; $first = -1;
            foreach ( $tm_od as $i => $od ) {
                if ( empty( $od ) ) continue;
                if ( $first === -1 || $od < $tm_od[ $first ] ) $first = $i;
                if ( $od >= $today && $od < $best ) { $best = $od; $idx = $i; }
            }
            if ( $idx === -1 ) $idx = $first;
        }

        if ( $idx === -1 || empty( $tm_od[ $idx ] ) ) continue;

        $cena_fm = is_numeric( $tm_fm[ $idx ] ?? '' ) ? (int) $tm_fm[ $idx ] : 0;
        $cena_lm = is_numeric( $tm_lm[ $idx ] ?? '' ) ? (int) $tm_lm[ $idx ] : 0;

        $cards[] = array(
            'title'     => $title,
            'thumbnail' => get_the_post_thumbnail_url( $id, 'homepage_thumbnail' ),
            'link'      => get_the_permalink( $id ),
            'tm_od'     => date( 'd.m.Y', $tm_od[ $idx ] ),
            'tm_do'     => ! empty( $tm_do[ $idx ] ) ? date( 'd.m.Y', $tm_do[ $idx ] ) : '',
            'day_count' => ! empty( $tm_do[ $idx ] ) ? (int) floor( ( $tm_do[ $idx ] - $tm_od[ $idx ] ) / DAY_IN_SECONDS ) + 1 : 0,
            'cena'      => $tm_celk[ $idx ] ?? 0,
            'cena_fm'   => number_format( $cena_fm, 0, ',', ' ' ),
            'cena_lm'   => $cena_lm ? number_format( $cena_lm, 0, ',', ' ' ) : '',
            'infop'     => $tm_info[ $idx ] ?? '',
            'moreterm'  => count( array_filter( $tm_od ) ),
            'timestamp' => (int) $tm_od[ $idx ],
        );
    endwhile;
    wp_reset_postdata();
endif;

usort( $cards, fn( $a, $b ) => $a['timestamp'] <=> $b['timestamp'] );
?>

<main role="main" class="content content--subpage content--search">
  <div class="container">
    <div class="row">
      <div class="col-sm-12">
        <h1 class="title title--h1">Vyhledávání zájezdů</h1>
      </div>
    </div>

    <!-- Vyhledávací / filtrační formulář -->
    <div class="row">
      <div class="col-sm-12">
        <form method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>" class="k26-search-form">
          <div class="k26-search-form__row">
            <label class="k26-search-form__field k26-search-form__field--text">
              <span>Název zájezdu</span>
              <input type="search" name="s" value="<?php echo esc_attr( $s_query ); ?>" placeholder="Např. Japonsko">
            </label>
            <label class="k26-search-form__field">
              <span>V termínu od</span>
              <input type="date" name="od" value="<?php echo esc_attr( $od_raw ); ?>">
            </label>
            <label class="k26-search-form__field">
              <span>do</span>
              <input type="date" name="do" value="<?php echo esc_attr( $do_raw ); ?>">
            </label>
            <button type="submit" class="k26-search-form__submit">Vyhledat</button>
          </div>
        </form>
      </div>
    </div>

    <div class="row">
      <div class="col-sm-12">
        <p class="k26-search-summary">
          <?php
          if ( $s_query !== '' || $filter ) {
              echo 'Nalezeno <strong>' . count( $cards ) . '</strong> zájezdů';
              if ( $s_query !== '' ) echo ' pro „' . esc_html( $s_query ) . '"';
              if ( $od_ts ) echo ' od ' . esc_html( date( 'd.m.Y', $od_ts ) );
              if ( $do_ts ) echo ' do ' . esc_html( date( 'd.m.Y', $do_ts ) );
              echo '.';
          }
          ?>
        </p>
      </div>
    </div>

    <div class="row recommended-trips">
      <?php if ( $cards ) : foreach ( $cards as $trip ) :
          $cena_default  = number_format( (float) $trip['cena'], 0, ',', ' ' );
          $cena_firstmin = firstminute( $trip['tm_od'], $trip['cena'] );
          $moreterm      = $trip['moreterm'];
      ?>
        <div class="col-sm-4 col-md-3">
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
        <div class="col-sm-12">
          <p>Nenašli jsme žádné zájezdy<?php echo $s_query !== '' ? ' pro „' . esc_html( $s_query ) . '"' : ''; ?>. Zkuste upravit hledaný výraz nebo termín.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php get_footer(); ?>

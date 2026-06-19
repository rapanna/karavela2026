<?php get_header(); ?>

<!-- Slider -->
<div class="slider">
<?php
$slider_query = new WP_Query( array(
    'post_type'      => 'trip',
    'posts_per_page' => 5,
    'no_found_rows'  => true,
    'tax_query'      => array( array(
        'taxonomy' => 'categories',
        'field'    => 'slug',
        'terms'    => 'slider',
    ) ),
) );

$slider_items = array();

if ( $slider_query->have_posts() ) {
    while ( $slider_query->have_posts() ) {
        $slider_query->the_post();
        $id           = get_the_ID();
        $alt_title    = get_post_meta( $id, '_karavela_title', true );
        $promo_date   = get_post_meta( $id, '_karavela_promo_date', true );
        $tm_od        = get_post_meta( $id, 'tm_od_p', true );
        $tm_do        = get_post_meta( $id, 'tm_do_p', true );
        $key          = array_search( (int) $promo_date, (array) $tm_od );
        $key          = ( $key !== false ) ? $key : 0;

        $terms      = get_the_terms( $id, 'categories' );
        $guaranteed = false;
        if ( is_array( $terms ) ) {
            foreach ( $terms as $term ) {
                if ( (int) $term->term_id === 385 ) {
                    $guaranteed = true;
                    break;
                }
            }
        }

        $slider_items[] = array(
            'title'      => $alt_title ?: get_the_title(),
            'thumbnail'  => get_the_post_thumbnail_url( $id, 'slider_homepage' ),
            'permalink'  => get_the_permalink( $id ),
            'tm_od'      => ! empty( $tm_od[ $key ] ) ? date( 'd.m.Y', $tm_od[ $key ] ) : '',
            'tm_do'      => ! empty( $tm_do[ $key ] ) ? date( 'd.m.Y', $tm_do[ $key ] ) : '',
            'timestamp'  => ! empty( $tm_od[ $key ] ) ? $tm_od[ $key ] : 0,
            'guaranteed' => $guaranteed,
        );
    }
    wp_reset_postdata();
}

usort( $slider_items, fn( $a, $b ) => $a['timestamp'] <=> $b['timestamp'] );

foreach ( $slider_items as $slide ) : ?>
    <div class="slider-item">
        <div class="slider-item__img" style="background-image: url('<?php echo esc_url( $slide['thumbnail'] ); ?>');"></div>
        <div class="slider-item-text">
            <h2><?php echo esc_html( $slide['title'] ); ?></h2>
            <?php if ( $slide['tm_od'] ) : ?>
                <?php if ( $slide['guaranteed'] ) : ?>
                <h3>Garantovaný zájezd</h3>
            <?php endif; ?>
            <h4><?php echo esc_html( $slide['tm_od'] ); ?> – <?php echo esc_html( $slide['tm_do'] ); ?></h4>
            <?php endif; ?>
            <a href="<?php echo esc_url( $slide['permalink'] ); ?>" class="slider-item-text__link">Více informací</a>
        </div>
    </div>
<?php endforeach; ?>
</div><!-- .slider -->

<!-- Doporučujeme -->
<main role="main" class="content content--hp">
    <div class="view">
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <h1><a href="<?php echo esc_url( get_post_type_archive_link( 'trip' ) ); ?>">Doporučujeme</a></h1>
                </div>
            </div>
            <div class="row recommended-trips">
<?php
$main_query = new WP_Query( array(
    'post_type'      => 'trip',
    'posts_per_page' => 29,
    'no_found_rows'  => true,
    'tax_query'      => array( array(
        'taxonomy' => 'categories',
        'field'    => 'name',
        'terms'    => 'Doporučujeme',
    ) ),
) );

$trips = array();

if ( $main_query->have_posts() ) {
    while ( $main_query->have_posts() ) {
        $main_query->the_post();
        $id        = get_the_ID();
        $alt_title = get_post_meta( $id, '_karavela_title', true );
        $datumy    = karavela_tmps( $id );
        $trip_info = karavela_preffered_date( $datumy, $id );

        if ( empty( $trip_info ) ) {
            asort( $datumy );
            $trip_info = karavela_single_trip_date( $datumy );
        }

        if ( empty( $trip_info['tm_od_p'][0] ) ) continue;

        $cena_fm = is_numeric( $trip_info['tm_cena_fm_p'][0] ?? '' ) ? (int) $trip_info['tm_cena_fm_p'][0] : 0;
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
            'cena_fm'   => number_format( $cena_fm, 0, ',', ' ' ),
            'cena_lm'   => $cena_lm ? number_format( $cena_lm, 0, ',', ' ' ) : '',
            'infop'     => $trip_info['tm_info_p'][0] ?? '',
            'moreterm'  => count( (array) $tm_od ),
            'timestamp' => $trip_info['tm_od_p'][0],
        );
    }
    wp_reset_postdata();
}

usort( $trips, fn( $a, $b ) => $a['timestamp'] <=> $b['timestamp'] );

foreach ( $trips as $trip ) :
    $cena_default   = number_format( (float) $trip['cena'], 0, ',', ' ' );
    $cena_firstmin  = firstminute( $trip['tm_od'], $trip['cena'] );
    $moreterm       = $trip['moreterm'];
?>
                <div class="col-sm-4 col-md-3">
                    <div class="box">
                        <a href="<?php echo esc_url( $trip['link'] ); ?>" class="box-link">
                            <?php if ( $trip['infop'] ) : ?>
                                <div class="box-info"><?php echo esc_html( $trip['infop'] ); ?></div>
                            <?php endif; ?>
                            <img src="<?php echo esc_url( $trip['thumbnail'] ); ?>" alt="<?php echo esc_attr( $trip['title'] ); ?>" style="max-width:100%;" class="box-link__img">
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
<?php endforeach; ?>
            </div><!-- .row -->
        </div><!-- .container -->
    </div><!-- .view -->
</main>

<?php get_footer(); ?>

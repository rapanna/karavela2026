<?php get_header(); ?>

<?php if ( have_posts() ) : while ( have_posts() ) : the_post();

    $post_id = get_the_ID();

    // Termíny
    $tm_kod_p       = (array) ( get_post_meta( $post_id, 'tm_kod_p',       true ) ?: [] );
    $tm_od_p        = (array) ( get_post_meta( $post_id, 'tm_od_p',        true ) ?: [] );
    $tm_do_p        = (array) ( get_post_meta( $post_id, 'tm_do_p',        true ) ?: [] );
    $tm_cena_celk_p = (array) ( get_post_meta( $post_id, 'tm_cena_celk_p', true ) ?: [] );
    $tm_cena_fm_p   = (array) ( get_post_meta( $post_id, 'tm_cena_fm_p',   true ) ?: [] );
    $tm_cena_lm_p   = (array) ( get_post_meta( $post_id, 'tm_cena_lm_p',   true ) ?: [] );
    $tm_info_p      = (array) ( get_post_meta( $post_id, 'tm_info_p',      true ) ?: [] );
    $pocet_terminu  = count( $tm_od_p );

    // Program zájezdu
    $prog_den   = (array) ( get_post_meta( $post_id, 'k26_prog_den',   true ) ?: [] );
    $prog_popis = (array) ( get_post_meta( $post_id, 'k26_prog_popis', true ) ?: [] );

    // Reference
    $ref_jmena    = (array) ( get_post_meta( $post_id, 'k26_ref_jmeno',   true ) ?: [] );
    $ref_foto_ids = (array) ( get_post_meta( $post_id, 'k26_ref_foto_id', true ) ?: [] );
    $ref_texty    = (array) ( get_post_meta( $post_id, 'k26_ref_text',    true ) ?: [] );
    $ma_reference = ! empty( array_filter( $ref_jmena ) );

    // Galerie
    $gallery_id = (int) get_post_meta( $post_id, 'trip_ngg_gallery_id', true );

    // Ostatní meta
    $karavela_title     = get_post_meta( $post_id, '_karavela_title', true );
    $pocet_klientu      = get_post_meta( $post_id, 'pocet_klientu',   true );
    $pojisteni          = get_post_meta( $post_id, 'pojisteni',        true );
    $zahrnuje_items     = k26_get_cena_items( $post_id, 'k26_cena_zahrnuje',   'cena_zahrnuje' );
    $nezahrnuje_items   = k26_get_cena_items( $post_id, 'k26_cena_nezahrnuje', 'cena_nezahrnuje' );

    // GPS
    $longitudes = (array) ( get_post_meta( $post_id, 'longitudes', true ) ?: [] );
    $latitudes  = (array) ( get_post_meta( $post_id, 'latitudes',  true ) ?: [] );
    $city_names = (array) ( get_post_meta( $post_id, 'city-names', true ) ?: [] );
    $ma_mapu    = ! empty( $longitudes[0] );

    // Hlavní obrázek (featured image)
    $thumbnail = get_the_post_thumbnail_url( $post_id, 'slider_homepage' );
?>

<?php if ( $thumbnail ) : ?>
<div class="slider">
    <div class="slider-item">
        <div class="slider-item__img" style="background-image: url('<?php echo esc_url( $thumbnail ); ?>');"></div>
        <h2 class="slider-item__title"><?php echo esc_html( $karavela_title ?: get_the_title() ); ?></h2>
    </div>
</div>
<?php endif; ?>

<main role="main" class="content content--subpage content--trip">
    <div class="container">
        <div class="row">
            <div class="col-md-12 content--subpage__main content-trip">

                <?php if ( function_exists( 'bcn_display' ) ) : ?>
                <div class="breadcrumbs" typeof="BreadcrumbList" vocab="https://schema.org/">
                    <?php bcn_display(); ?>
                </div>
                <?php endif; ?>

                <h1 class="title title--text title--h1"><?php the_title(); ?></h1>

                <div class="single-trip__content">

                    <div class="description">
                        <?php the_content(); ?>
                    </div>

                    <!-- ── Termíny ───────────────────────────────────────────── -->
                    <?php if ( $pocet_terminu && $tm_od_p[0] ) : ?>
                    <div class="term">
                        <h3 class="title title--h3 title--term">Termíny</h3>
                        <table cellspacing="10" class="term--table">
                            <thead>
                                <tr>
                                    <th>Termín</th>
                                    <th>Počet dní</th>
                                    <th>Cena</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php for ( $i = 0; $i < $pocet_terminu; $i++ ) :
                                if ( empty( $tm_od_p[ $i ] ) ) continue;
                                $day_count = (int) floor( ( $tm_do_p[ $i ] - $tm_od_p[ $i ] ) / DAY_IN_SECONDS ) + 1;
                                $cena_fm   = firstminute( date( 'd.m.Y', $tm_od_p[ $i ] ), $tm_cena_celk_p[ $i ] );
                            ?>
                                <tr>
                                    <td>
                                        <?php echo esc_html( date( 'd.m.Y', $tm_od_p[ $i ] ) . ' – ' . date( 'd.m.Y', $tm_do_p[ $i ] ) ); ?>
                                        <?php if ( ! empty( $tm_info_p[ $i ] ) ) : ?>
                                            <br><small class="red">Info: <?php echo esc_html( $tm_info_p[ $i ] ); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="day"><?php echo $day_count; ?> dní</td>
                                    <td class="price">
                                        <?php echo esc_html( number_format( $tm_cena_celk_p[ $i ], 0, ',', ' ' ) . ' Kč' ); ?>
                                        <?php if ( $cena_fm ) : ?>
                                            <br><span class="box-link-cost__fm">Cena FM: <?php echo esc_html( $cena_fm ); ?> Kč</span>
                                        <?php endif; ?>
                                        <?php if ( ! empty( $tm_cena_lm_p[ $i ] ) ) : ?>
                                            <br><strong style="color:red">Cena LM: <?php echo esc_html( number_format( $tm_cena_lm_p[ $i ], 0, ',', ' ' ) ); ?> Kč</strong>
                                        <?php endif; ?>
                                    </td>
                                    <td class="rezervovat">
                                        <a href="#" data-code="<?php echo esc_attr( $tm_kod_p[ $i ] ); ?>" class="term__btn">Rezervovat</a>
                                        <small>Kód: <?php echo esc_html( $tm_kod_p[ $i ] ); ?></small>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                            </tbody>
                        </table>

                        <?php if ( $pocet_klientu ) : ?>
                        <h4 style="margin-top:2em;margin-left:14px">Počet klientů</h4>
                        <div style="margin-left:14px"><?php echo esc_html( strip_tags( html_entity_decode( $pocet_klientu ) ) ); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- ── Program zájezdu ───────────────────────────────────── -->
                    <?php if ( ! empty( array_filter( $prog_popis ) ) ) : ?>
                    <div class="program-zajezdu">
                        <div class="row">
                            <div class="col-sm-12"><h3>Program zájezdu</h3></div>
                        </div>
                        <?php foreach ( $prog_popis as $i => $popis ) :
                            if ( ! trim( strip_tags( $popis ) ) ) continue;
                            $den = trim( $prog_den[ $i ] ?? '' );
                        ?>
                        <?php if ( ! $den ) : ?>
                            <div class="row row--program">
                                <div class="col-md-12"><strong><?php echo wp_kses_post( $popis ); ?></strong></div>
                            </div>
                        <?php else : ?>
                            <div class="row row--program">
                                <div class="col-md-2 col-sm-3"><span><?php echo esc_html( $den ); ?></span></div>
                                <div class="col-md-10 col-sm-9"><span><?php echo wp_kses_post( $popis ); ?></span></div>
                            </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- ── Cena zahrnuje / nezahrnuje ────────────────────────── -->
                    <?php if ( $zahrnuje_items || $nezahrnuje_items ) : ?>
                    <div class="reference top_trip">
                        <?php if ( $zahrnuje_items ) : ?>
                        <div class="row">
                            <div class="col-sm-12"><h3>Cena zahrnuje</h3></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <ul><?php foreach ( $zahrnuje_items as $polo ) echo '<li>' . wp_kses_post( $polo ) . '</li>'; ?></ul>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ( $nezahrnuje_items ) : ?>
                        <div class="row">
                            <div class="col-sm-12"><h3 style="margin-top:0.8em">Cena nezahrnuje</h3></div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <ul><?php foreach ( $nezahrnuje_items as $polo ) echo '<li>' . wp_kses_post( $polo ) . '</li>'; ?></ul>
                                <?php if ( empty( $pojisteni ) || $pojisteni === '0' ) : ?>
                                <ul class="cestpojisteni"><li><strong><a href="<?php echo esc_url( get_permalink( 145 ) ); ?>">Zajišťujeme tato cestovní pojištění</a></strong></li></ul>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- ── GPS mapa ──────────────────────────────────────────── -->
                    <?php if ( $ma_mapu ) :
                        $gps_points = array_map( null, $latitudes, $longitudes, $city_names );
                        $map_zoom   = get_post_meta( $post_id, 'k26_map_zoom', true );
                    ?>
                    <div class="google-map-container">
                        <div id="map-gps-route"
                             style="width:100%;height:350px;"
                             data-points="<?php echo esc_attr( wp_json_encode( $gps_points ) ); ?>"
                             data-zoom="<?php echo esc_attr( $map_zoom ); ?>">
                        </div>
                    </div>
                    <script>
                    document.addEventListener('DOMContentLoaded', function(){
                        if (typeof L === 'undefined') return;
                        var el = document.getElementById('map-gps-route');
                        var pts = JSON.parse(el.dataset.points || '[]');
                        var manualZoom = parseInt(el.dataset.zoom, 10); // NaN = automaticky
                        var map = L.map(el, { scrollWheelZoom: false });
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                            maxZoom: 18
                        }).addTo(map);
                        var latlngs = [];
                        pts.forEach(function(p){
                            if (!p || p[0] == null || p[1] == null) return;
                            var lat = parseFloat(p[0]), lng = parseFloat(p[1]), name = p[2] || '';
                            latlngs.push([lat, lng]);
                            var m = L.marker([lat, lng]).addTo(map);
                            if (name) m.bindPopup(name);
                        });
                        if (latlngs.length === 1) {
                            map.setView(latlngs[0], isNaN(manualZoom) ? 6 : manualZoom);
                        } else if (latlngs.length > 1) {
                            L.polyline(latlngs, { color: '#e63e23', weight: 3 }).addTo(map);
                            var bounds = L.latLngBounds(latlngs);
                            if (isNaN(manualZoom)) {
                                map.fitBounds(bounds.pad(0.15));
                            } else {
                                map.setView(bounds.getCenter(), manualZoom);
                            }
                        }
                    }); // DOMContentLoaded
                    </script>
                    <?php endif; ?>

                    <!-- ── Galerie ───────────────────────────────────────────── -->
                    <?php if ( $gallery_id ) :
                        $gallery_html = karavela_photogallery_presentation( $gallery_id );
                    ?>
                    <?php if ( $gallery_html ) : ?>
                    <div class="trip--photogallery">
                        <h3>Galerie</h3>
                        <?php echo $gallery_html; ?>
                    </div>
                    <script>
                    jQuery(function($){
                        $('.gallery-images').slick({
                            dots: false,
                            infinite: false,
                            adaptiveHeight: false,
                            lazyLoad: 'ondemand',
                            speed: 300,
                            slidesToShow: 3,
                            slidesToScroll: 3,
                            responsive: [
                                { breakpoint: 1024, settings: { slidesToShow: 3, slidesToScroll: 3 } },
                                { breakpoint: 600,  settings: { slidesToShow: 2, slidesToScroll: 2 } },
                                { breakpoint: 480,  settings: { slidesToShow: 1, slidesToScroll: 1 } }
                            ]
                        });

                        // Lightbox (GLightbox) – po inicializaci slicku, ať se naváže na reálné slidy
                        if (typeof GLightbox !== 'undefined') {
                            GLightbox({ selector: '.trip--photogallery .glightbox' });
                        }
                    });
                    </script>
                    <?php endif; ?>
                    <?php endif; ?>

                    <!-- ── Reference ─────────────────────────────────────────── -->
                    <?php if ( $ma_reference ) : ?>
                    <div class="ref">
                        <div class="row">
                            <div class="col-sm-12"><h3>Reference</h3></div>
                        </div>
                        <?php foreach ( $ref_jmena as $i => $jmeno ) :
                            if ( ! trim( $jmeno ) ) continue;
                            $foto_id  = (int) ( $ref_foto_ids[ $i ] ?? 0 );
                            $foto_url = $foto_id ? wp_get_attachment_image_url( $foto_id, 'square_thumbnail' ) : '';
                            $text     = $ref_texty[ $i ] ?? '';
                        ?>
                        <div class="recommended-trips-trip">
                            <div class="row">
                                <div class="col-sm-3">
                                    <?php if ( $foto_url ) : ?>
                                    <img src="<?php echo esc_url( $foto_url ); ?>" alt="<?php echo esc_attr( $jmeno ); ?>" class="recommended-trips__img">
                                    <?php endif; ?>
                                </div>
                                <div class="col-sm-9">
                                    <h4 class="recommended-trips__title"><?php echo esc_html( $jmeno ); ?></h4>
                                    <p class="recommended-trips-date"><?php echo wp_kses_post( $text ); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                </div><!-- .single-trip__content -->

                <?php k26_render_reservation_form( $post_id ); ?>

            </div><!-- .col-md-12 -->
        </div><!-- .row -->
    </div><!-- .container -->
</main>

<?php endwhile; endif; ?>

<?php get_footer(); ?>

<?php
defined( 'ABSPATH' ) || exit;

// ── Registrace feedu ──────────────────────────────────────────────────────────

add_action( 'init', function () {
    add_feed( 'sdovolena-feed', 'k26_sdovolena_feed' );
} );

// ── XML feed ──────────────────────────────────────────────────────────────────

function k26_sdovolena_feed(): void {

    $ubytovani_labels = [
        '0' => 'nestanoveno',
        '1' => 'hotel',
        '2' => 'turistický hotel, ubytovna',
        '3' => 'penzion',
        '4' => 'kemp',
        '5' => 'stan',
        '6' => 'kombinace - ubytovna a stany',
        '7' => 'jiné',
        '8' => 'kombinace - hotel, stan',
    ];

    header( 'Content-Type: text/xml; charset=utf-8' );
    echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
    echo "<tours>\n";
    echo "<currency_id>1</currency_id>\n";

    $posts = get_posts( [
        'post_type'        => 'trip',
        'post_status'      => 'publish',
        'posts_per_page'   => -1,
        'orderby'          => 'post_date',
        'order'            => 'DESC',
        'suppress_filters' => true,
    ] );

    foreach ( $posts as $post ) {
        $exportovat = get_post_meta( $post->ID, 'sdovolenka_exportovat', true );

        // Podpora starého formátu 'Ano' i nového '1'
        if ( $exportovat !== '1' && $exportovat !== 'Ano' ) continue;

        $id_zajezdu    = get_post_meta( $post->ID, 'id_zajezdu',            true );
        $typ_zajezdu   = get_post_meta( $post->ID, 'typ-zajezdu-dovolenka', true );
        $typ_dopravy   = get_post_meta( $post->ID, 'typ-dopravy',           true );
        $typ_ubytovani = get_post_meta( $post->ID, 'typ-ubytovani',         true );
        $uroven_ubyt   = get_post_meta( $post->ID, 'uroven-ubyt',           true );
        $typ_stravy    = get_post_meta( $post->ID, 'typ-stravy',            true );

        $post_link = get_permalink( $post->ID );

        echo "\t<tour>\n";
        echo "\t\t<tour_id>"          . esc_xml( $id_zajezdu )                  . "</tour_id>\n";
        echo "\t\t<tour_type>"        . esc_xml( $typ_zajezdu )                 . "</tour_type>\n";
        echo "\t\t<tour_detail_url>"  . esc_url( $post_link )                   . "</tour_detail_url>\n";
        echo "\t\t<tour_priority>0</tour_priority>\n";

        // Lokace – z taxonomie 'kategorie', country_id = popis termu
        $kategorie = wp_get_post_terms( $post->ID, 'kategorie' );
        echo "\t\t<locations>\n";
        foreach ( $kategorie as $term ) {
            if ( $term->description !== '' ) {
                echo "\t\t\t<location><country_id>" . esc_xml( $term->description ) . "</country_id></location>\n";
            }
        }
        echo "\t\t</locations>\n";

        echo "\t\t<tour_title>" . esc_xml( get_the_title( $post->ID ) )                   . "</tour_title>\n";
        echo "\t\t<tour_desc>"  . esc_xml( wp_strip_all_tags( $post->post_content ) ) . "</tour_desc>\n";

        // Ubytování
        $ubytovani_label = $ubytovani_labels[ $typ_ubytovani ] ?? 'nestanoveno';
        echo "\t\t<accommodation>\n";
        echo "\t\t\t<type>"     . esc_xml( $ubytovani_label ) . "</type>\n";
        echo "\t\t\t<class_id>" . esc_xml( $uroven_ubyt )     . "</class_id>\n";
        echo "\t\t</accommodation>\n";

        // Program – nový formát: k26_prog_den[] / k26_prog_popis[]
        $prog_den   = (array) ( get_post_meta( $post->ID, 'k26_prog_den',   true ) ?: [] );
        $prog_popis = (array) ( get_post_meta( $post->ID, 'k26_prog_popis', true ) ?: [] );
        if ( ! empty( array_filter( $prog_popis ) ) ) {
            echo "\t\t<program><tour_program>\n";
            foreach ( $prog_popis as $i => $popis ) {
                if ( ! $popis ) continue;
                echo "\t\t\t<day>\n";
                echo "\t\t\t\t<title>" . esc_xml( wp_strip_all_tags( $prog_den[ $i ] ?? '' ) ) . "</title>\n";
                echo "\t\t\t\t<desc>"  . esc_xml( wp_strip_all_tags( $popis ) )                . "</desc>\n";
                echo "\t\t\t</day>\n";
            }
            echo "\t\t</tour_program></program>\n";
        }

        // Fotky – nový formát: trip_ngg_gallery_id (int)
        echo "\t\t<photos>\n";
        $gid     = (int) get_post_meta( $post->ID, 'trip_ngg_gallery_id', true );
        $counter = 0;
        if ( $gid ) {
            // Přímý DB dotaz – NGG 4.x find_all() generuje v feed kontextu špatný SQL
            global $wpdb;
            $images = $wpdb->get_results( $wpdb->prepare(
                "SELECT filename, alttext, galleryid FROM {$wpdb->prefix}ngg_pictures
                 WHERE galleryid = %d ORDER BY sortorder ASC LIMIT 10",
                $gid
            ) ) ?: [];
            $gallery_path = $wpdb->get_var( $wpdb->prepare(
                "SELECT path FROM {$wpdb->prefix}ngg_gallery WHERE gid = %d",
                $gid
            ) );
            $base_url = trailingslashit( get_bloginfo( 'url' ) ) . trailingslashit( ltrim( $gallery_path, '/' ) );

            foreach ( $images as $image ) {
                $counter++;
                echo "\t\t\t<photo>\n";
                echo "\t\t\t\t<order>{$counter}</order>\n";
                echo "\t\t\t\t<url>"  . esc_url( $base_url . $image->filename )                              . "</url>\n";
                echo "\t\t\t\t<desc>" . esc_xml( 'Fotka zájezdu: ' . ( $image->alttext ?? '' ) ) . "</desc>\n";
                echo "\t\t\t</photo>\n";
            }
        }
        if ( $counter === 0 ) {
            echo "\t\t\t<photo>\n";
            echo "\t\t\t\t<order>1</order>\n";
            echo "\t\t\t\t<url>" . esc_url( get_bloginfo( 'url' ) . '/wp-content/uploads/no-image.png' ) . "</url>\n";
            echo "\t\t\t\t<desc>Obrázek není k dispozici</desc>\n";
            echo "\t\t\t</photo>\n";
        }
        echo "\t\t</photos>\n";

        // term_groups
        echo "\t\t<term_groups><term_group>\n";

        echo "\t\t\t<board><id>"     . esc_xml( $typ_stravy )  . "</id></board>\n";
        echo "\t\t\t<transport><id>" . esc_xml( $typ_dopravy ) . "</id></transport>\n";

        // Odjezdová místa – nový formát: každý řádek = jedno místo
        $mista_raw = get_post_meta( $post->ID, 'seznam_odjezdovych_mist', true );
        $mista     = $mista_raw
            ? array_filter( array_map( 'trim', explode( "\n", $mista_raw ) ) )
            : [ 'Praha' ];
        echo "\t\t\t<dept_places>\n";
        foreach ( $mista as $misto ) {
            echo "\t\t\t\t<place>" . esc_xml( $misto ) . "</place>\n";
        }
        echo "\t\t\t</dept_places>\n";

        // Cena zahrnuje – nový formát: k26_cena_zahrnuje[] array
        $zahrnuje = array_filter( (array) ( get_post_meta( $post->ID, 'k26_cena_zahrnuje', true ) ?: [] ) );
        if ( $zahrnuje ) {
            echo "\t\t\t<price_incl>\n";
            foreach ( $zahrnuje as $item ) {
                echo "\t\t\t\t<item><desc>" . esc_xml( wp_strip_all_tags( $item ) ) . "</desc></item>\n";
            }
            echo "\t\t\t</price_incl>\n";
        }

        // Cena nezahrnuje
        $nezahrnuje = array_filter( (array) ( get_post_meta( $post->ID, 'k26_cena_nezahrnuje', true ) ?: [] ) );
        if ( $nezahrnuje ) {
            echo "\t\t\t<price_excl>\n";
            foreach ( $nezahrnuje as $item ) {
                echo "\t\t\t\t<item><desc>" . esc_xml( wp_strip_all_tags( $item ) ) . "</desc></item>\n";
            }
            echo "\t\t\t</price_excl>\n";
        }

        // Termíny
        $tm_kod_p       = (array) ( get_post_meta( $post->ID, 'tm_kod_p',       true ) ?: [] );
        $tm_od_p        = (array) ( get_post_meta( $post->ID, 'tm_od_p',        true ) ?: [] );
        $tm_do_p        = (array) ( get_post_meta( $post->ID, 'tm_do_p',        true ) ?: [] );
        $tm_cena_celk_p = (array) ( get_post_meta( $post->ID, 'tm_cena_celk_p', true ) ?: [] );

        if ( ! empty( $tm_kod_p[0] ) ) {
            echo "\t\t\t<terms>\n";
            foreach ( $tm_kod_p as $i => $kod ) {
                if ( ! $kod ) continue;
                $od    = (int) ( $tm_od_p[ $i ]        ?? 0 );
                $do    = (int) ( $tm_do_p[ $i ]        ?? 0 );
                $cena  = (int) ( $tm_cena_celk_p[ $i ] ?? 0 );
                $days  = ( $od && $do ) ? (int) floor( ( $do - $od ) / 86400 ) + 1 : 0;

                echo "\t\t\t\t<term>\n";
                echo "\t\t\t\t\t<id>"          . esc_xml( $kod )                                          . "</id>\n";
                echo "\t\t\t\t\t<start>"       . ( $od ? date( 'Y-m-d', $od ) : '' )                     . "</start>\n";
                echo "\t\t\t\t\t<end>"         . ( $do ? date( 'Y-m-d', $do ) : '' )                     . "</end>\n";
                echo "\t\t\t\t\t<day_count>"   . $days                                                    . "</day_count>\n";
                echo "\t\t\t\t\t<night_count>" . max( 0, $days - 1 )                                      . "</night_count>\n";
                echo "\t\t\t\t\t<prices><price><desc_id>1</desc_id><final_price>{$cena}</final_price></price></prices>\n";
                echo "\t\t\t\t\t<purchase_url>" . esc_url( add_query_arg( 'related', $i, $post_link ) ) . "</purchase_url>\n";
                echo "\t\t\t\t</term>\n";
            }
            echo "\t\t\t</terms>\n";
        }

        echo "\t\t</term_group></term_groups>\n";
        echo "\t</tour>\n";
    }

    echo '</tours>';
}

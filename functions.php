<?php

defined( 'ABSPATH' ) || exit;

// ─── Metaboxy ─────────────────────────────────────────────────────────────────

require_once get_template_directory() . '/includes/metabox-terminy.php';
require_once get_template_directory() . '/includes/metabox-galerie.php';
require_once get_template_directory() . '/includes/metabox-detail.php';
require_once get_template_directory() . '/includes/metabox-specifikace.php';
require_once get_template_directory() . '/includes/metabox-program.php';
require_once get_template_directory() . '/includes/metabox-reference.php';
require_once get_template_directory() . '/includes/metabox-sdovolenka.php';
require_once get_template_directory() . '/includes/metabox-itinerar.php';
require_once get_template_directory() . '/includes/feed-sdovolenka.php';
require_once get_template_directory() . '/includes/admin-options.php';
require_once get_template_directory() . '/includes/reservation-form.php';
require_once get_template_directory() . '/includes/contact-form.php';

// ─── Theme setup ──────────────────────────────────────────────────────────────

add_action( 'after_setup_theme', 'k26_setup' );
function k26_setup() {
    add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails', array( 'trip', 'reportaze', 'pruvodce' ) );
    add_theme_support( 'menus' );

    add_image_size( 'vylet-thumb',            160, 113 );
    add_image_size( 'post-autor',             220, 220, array( 'center', 'center' ) );
    add_image_size( 'zajezd-single-thumb',    534, 150 );
    add_image_size( 'trip-obrazek',           747, 510 );
    add_image_size( 'square_thumbnail',       264, 264, true );
    add_image_size( 'homepage_thumbnail',     264, 206, true );
    add_image_size( 'slider_homepage',       1440, 470, true );

    register_nav_menus( array(
        'header_menu'    => 'Hlavní navigace',
        'kontinent_menu' => 'Kontinenty',
        'usefull_links'  => 'Užitečné odkazy',
        'why_links'      => 'Proč s námi',
    ) );
}

// ─── Sidebary / widgety ────────────────────────────────────────────────────────

add_action( 'widgets_init', 'k26_widgets_init' );
function k26_widgets_init() {
    $sidebars = array(
        array( 'name' => 'MenuRight',          'id' => 'menu_right',  'class' => 'menu_right' ),
        array( 'name' => 'MenuLeft',           'id' => 'menu_left',   'class' => 'menu_left' ),
        array( 'name' => 'menu_pod',           'id' => 'menu_pod',    'class' => 'menu_right' ),
        array( 'name' => 'Kontakt v hlavičce', 'id' => 'header_text', 'class' => '' ),
    );
    foreach ( $sidebars as $s ) {
        register_sidebar( array(
            'name'          => $s['name'],
            'id'            => $s['id'],
            'before_widget' => '<div id="%1$s" class="' . $s['class'] . ' %2$s">',
            'after_widget'  => "</div>\n",
            'before_title'  => '',
            'after_title'   => '',
        ) );
    }
}

add_filter( 'widget_text', 'do_shortcode' );

// ─── Skripty a styly ───────────────────────────────────────────────────────────

add_action( 'wp_enqueue_scripts', 'k26_enqueue' );
function k26_enqueue() {
    $ver = wp_get_theme()->get( 'Version' );

    // Styly
    wp_enqueue_style( 'k26-style', get_template_directory_uri() . '/css/style.css', array(), $ver );
    wp_enqueue_style( 'k26-datepicker', get_template_directory_uri() . '/css/datepicker.css', array(), $ver );

    // jQuery – WP core verze (nechceme vlastní bundle)
    wp_enqueue_script( 'jquery' );

    // Slick carousel
    wp_enqueue_script( 'k26-slick', get_template_directory_uri() . '/js/slick.min.js', array( 'jquery' ), '1.8.1', true );

    // Hlavní JS šablony
    wp_enqueue_script( 'k26-script', get_template_directory_uri() . '/js/script.js', array( 'jquery', 'k26-slick' ), $ver, true );
    wp_enqueue_script( 'k26-custom', get_template_directory_uri() . '/js/custom.js', array( 'jquery' ), $ver, true );
    wp_enqueue_script( 'k26-dropdown', get_template_directory_uri() . '/js/dropdown.js', array( 'jquery' ), '3.3.7', true );

    if ( is_front_page() ) {
        wp_add_inline_script( 'k26-script', "jQuery('.slider').slick({dots:true,lazyLoad:'ondemand',autoplay:true,autoplaySpeed:8000});" );
    }

    // Leaflet – jen na detailu tripu s GPS daty
    if ( is_singular( 'trip' ) ) {
        wp_enqueue_style(  'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
        wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',  [], '1.9.4', true );

        // JS detailu zájezdu – inicializace mapy + galerie (slick/GLightbox)
        wp_enqueue_script( 'k26-single-trip', get_template_directory_uri() . '/js/single-trip.js', array( 'jquery', 'k26-slick', 'leaflet' ), $ver, true );

        // Rezervační formulář – (volitelně) Google reCAPTCHA v2
        if ( k26_setting( 'karavela_recaptcha_site' ) ) {
            wp_enqueue_script( 'google-recaptcha', 'https://www.google.com/recaptcha/api.js', array(), null, true );
        }

        // Lightbox galerie – jen když má zájezd galerii (GLightbox, MIT)
        if ( get_post_meta( get_queried_object_id(), 'trip_ngg_gallery_id', true ) ) {
            wp_enqueue_style(  'glightbox', 'https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/css/glightbox.min.css', array(), '3.3.0' );
            wp_enqueue_script( 'glightbox', 'https://cdn.jsdelivr.net/npm/glightbox@3.3.0/dist/js/glightbox.min.js', array(), '3.3.0', true );
        }
    }

    // Předání AJAX URL do JS
    wp_localize_script( 'k26-script', 'karavela', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'offset'   => 1,
    ) );
}

// ─── Custom post types ────────────────────────────────────────────────────────

add_action( 'init', 'k26_register_post_types' );
function k26_register_post_types() {
    register_post_type( 'trip', array(
        'label'       => 'Zájezd',
        'public'      => true,
        'has_archive' => true,
        'supports'    => array( 'title', 'editor', 'thumbnail' ),
        'rewrite'     => array( 'slug' => 'zajezd' ),
    ) );

    register_post_type( 'reportaze', array(
        'label'       => 'Reportáže',
        'public'      => true,
        'has_archive' => true,
        'supports'    => array( 'title', 'editor', 'thumbnail' ),
    ) );

    if ( ! post_type_exists( 'pruvodce' ) ) {
        $labels = array(
            'name'                  => 'Průvodci',
            'singular_name'         => 'Průvodce',
            'menu_name'             => 'Průvodci',
            'all_items'             => 'Všichni průvodci',
            'add_new_item'          => 'Přidat nového průvodce',
            'edit_item'             => 'Editace průvodce',
            'featured_image'        => 'Foto průvodce',
            'set_featured_image'    => 'Vybrat fotku průvodce',
            'remove_featured_image' => 'Odstranit fotku průvodce',
        );
        register_post_type( 'pruvodce', array(
            'label'             => 'Průvodce',
            'labels'            => $labels,
            'supports'          => array( 'title', 'editor', 'thumbnail' ),
            'taxonomies'        => array( 'post_tag' ),
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'menu_position'     => 5,
            'menu_icon'         => 'dashicons-admin-users',
            'has_archive'       => true,
            'capability_type'   => 'page',
            'publicly_queryable' => true,
        ) );
    }
}

// ─── Taxonomie ────────────────────────────────────────────────────────────────

add_action( 'init', 'k26_register_taxonomies' );
function k26_register_taxonomies() {
    register_taxonomy( 'kategorie', 'trip', array(
        'hierarchical'      => true,
        'label'             => 'Země',
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'zajezdy', 'with_front' => false ),
        'public'            => true,
        'show_in_nav_menus' => true,
        'show_admin_column' => true,
    ) );

    register_taxonomy( 'categories', 'trip', array(
        'label'        => 'Kategorie',
        'rewrite'      => array( 'slug' => 'rubriky' ),
        'hierarchical' => true,
    ) );
}

// ─── Vyhledávání omezit na typ "trip" ─────────────────────────────────────────

add_filter( 'pre_get_posts', 'k26_search_only_trips' );
function k26_search_only_trips( WP_Query $query ) {
    if ( $query->is_search() && ! is_admin() && $query->is_main_query() ) {
        $query->set( 'post_type', array( 'trip' ) );
    }
    if ( $query->is_tax( 'kategorie' ) && $query->is_main_query() ) {
        $query->set( 'posts_per_page', -1 );
    }
}

// ─── Skryj "read more" link z excerpt ─────────────────────────────────────────

add_filter( 'the_content_more_link', '__return_empty_string' );

// ─── Navigační walkery ────────────────────────────────────────────────────────

class K26_Header_Menu_Walker extends Walker_Nav_Menu {

    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $output .= "\n";
    }

    public function end_lvl( &$output, $depth = 0, $args = null ) {
        $output .= "\n";
    }

    public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
        $item       = $data_object;
        $classes    = empty( $item->classes ) ? array() : (array) $item->classes;
        $classes[]  = 'header-menu-list-item';

        $class_str  = esc_attr( implode( ' ', array_filter( $classes ) ) );
        $attributes = '';
        if ( ! empty( $item->attr_title ) ) $attributes .= ' title="' . esc_attr( $item->attr_title ) . '"';
        if ( ! empty( $item->target ) )     $attributes .= ' target="' . esc_attr( $item->target ) . '"';
        if ( ! empty( $item->xfn ) )        $attributes .= ' rel="' . esc_attr( $item->xfn ) . '"';
        if ( ! empty( $item->url ) )        $attributes .= ' href="' . esc_attr( $item->url ) . '"';

        $link_classes = array_merge( $classes, array( 'header-menu-list-item__link' ) );
        $link_class   = esc_attr( implode( ' ', array_filter( $link_classes ) ) );
        $attributes  .= ' class="' . $link_class . '"';

        $title = apply_filters( 'the_title', $item->title, $item->ID );

        $output .= '<li class="' . $class_str . '">';
        $output .= ( $args->before ?? '' );
        $output .= '<a' . $attributes . '>';
        $output .= ( $args->link_before ?? '' ) . $title . ( $args->link_after ?? '' );
        $output .= '</a>';
        $output .= ( $args->after ?? '' ) . '</li>';
    }

    public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
        $output .= "\n";
    }
}

class K26_Footer_Menu_Walker extends K26_Header_Menu_Walker {

    public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
        $item      = $data_object;
        $classes   = empty( $item->classes ) ? array() : (array) $item->classes;
        $classes[] = 'brown-list-item';

        $class_str  = esc_attr( implode( ' ', array_filter( $classes ) ) );
        $attributes = '';
        if ( ! empty( $item->attr_title ) ) $attributes .= ' title="' . esc_attr( $item->attr_title ) . '"';
        if ( ! empty( $item->target ) )     $attributes .= ' target="' . esc_attr( $item->target ) . '"';
        if ( ! empty( $item->xfn ) )        $attributes .= ' rel="' . esc_attr( $item->xfn ) . '"';
        if ( ! empty( $item->url ) )        $attributes .= ' href="' . esc_attr( $item->url ) . '"';

        $link_classes = array_merge( $classes, array( 'brown-list-item__link' ) );
        $link_class   = esc_attr( implode( ' ', array_filter( $link_classes ) ) );
        $attributes  .= ' class="' . $link_class . '"';

        $title = apply_filters( 'the_title', $item->title, $item->ID );

        $output .= '<li class="' . $class_str . '">';
        $output .= ( $args->before ?? '' );
        $output .= '<a' . $attributes . '><span>';
        $output .= ( $args->link_before ?? '' ) . $title . ( $args->link_after ?? '' );
        $output .= '</span></a>';
        $output .= ( $args->after ?? '' ) . '</li>';
    }
}

// ─── Walker: Kontinenty (horizontální navbar s dropdownem) ───────────────────

class K26_Kontinent_Menu_Walker extends Walker_Nav_Menu {

    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '<ul class="dropdown-menu">' . "\n";
    }

    public function end_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '</ul>' . "\n";
    }

    public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
        $item    = $data_object;
        $classes = empty( $item->classes ) ? array() : (array) $item->classes;

        if ( $depth === 0 && $this->has_children ) {
            $classes[] = 'dropdown';
            $class_str  = esc_attr( implode( ' ', array_filter( $classes ) ) );
            $output    .= '<li class="' . $class_str . '">';
            $output    .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">';
            $output    .= esc_html( apply_filters( 'the_title', $item->title, $item->ID ) );
            $output    .= ' <span class="caret"></span></a>' . "\n";
        } else {
            $class_str  = esc_attr( implode( ' ', array_filter( $classes ) ) );
            $attributes = '';
            if ( ! empty( $item->attr_title ) ) $attributes .= ' title="' . esc_attr( $item->attr_title ) . '"';
            if ( ! empty( $item->target ) )     $attributes .= ' target="' . esc_attr( $item->target ) . '"';
            if ( ! empty( $item->xfn ) )        $attributes .= ' rel="' . esc_attr( $item->xfn ) . '"';
            if ( ! empty( $item->url ) )        $attributes .= ' href="' . esc_url( $item->url ) . '"';

            $output .= '<li class="' . $class_str . '">';
            $output .= '<a' . $attributes . '>' . esc_html( apply_filters( 'the_title', $item->title, $item->ID ) ) . '</a>' . "\n";
        }
    }

    public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
        $output .= '</li>' . "\n";
    }
}

// ─── Admin: odstranit zbytečné položky menu ────────────────────────────────────

add_action( 'admin_menu', 'k26_remove_admin_menus' );
function k26_remove_admin_menus() {
    remove_menu_page( 'edit-comments.php' );
}

// ─── Pomocné funkce: termíny a ceny zájezdů ───────────────────────────────────

function karavela_tmps( $post_id ) {
    $tm_od_p = get_post_meta( $post_id, 'tm_od_p', true );
    $datumy  = array();
    if ( $tm_od_p ) {
        foreach ( $tm_od_p as $key => $value ) {
            $datumy[ $post_id . '-' . $key ] = $value;
        }
    }
    return $datumy;
}

function karavela_single_trip_date( $trip_dates ) {
    $values = array();
    if ( is_array( $trip_dates ) ) {
        foreach ( $trip_dates as $key => $value ) {
            $kus     = explode( '-', $key );
            $post_id = $kus[0];
            $values['tm_od_p']        = get_post_meta( $post_id, 'tm_od_p', true );
            $values['tm_do_p']        = get_post_meta( $post_id, 'tm_do_p', true );
            $values['tm_cena_celk_p'] = get_post_meta( $post_id, 'tm_cena_celk_p', true );
            $values['tm_cena_fm_p']   = get_post_meta( $post_id, 'tm_cena_fm_p', true );
            $values['tm_cena_lm_p']   = get_post_meta( $post_id, 'tm_cena_lm_p', true );
            $values['tm_info_p']      = get_post_meta( $post_id, 'tm_info_p', true );
            break;
        }
    }
    return $values;
}

function karavela_preffered_date( $trip_dates, $post_id ) {
    $preffer_date = (int) get_post_meta( $post_id, '_karavela_promo_date', true );
    $values       = array();
    if ( is_array( $trip_dates ) ) {
        foreach ( $trip_dates as $key => $value ) {
            $kus      = explode( '-', $key );
            $pid      = $kus[0];
            $od_arr   = get_post_meta( $pid, 'tm_od_p', true );
            $idx      = array_search( $preffer_date, $od_arr );
            if ( $idx !== false ) {
                $do_arr   = get_post_meta( $pid, 'tm_do_p', true );
                $celk_arr = get_post_meta( $pid, 'tm_cena_celk_p', true );
                $fm_arr   = get_post_meta( $pid, 'tm_cena_fm_p', true );
                $lm_arr   = get_post_meta( $pid, 'tm_cena_lm_p', true );
                $info_arr = get_post_meta( $pid, 'tm_info_p', true );
                $values['tm_od_p']        = array( $od_arr[ $idx ] );
                $values['tm_do_p']        = array( $do_arr[ $idx ] );
                $values['tm_cena_celk_p'] = array( $celk_arr[ $idx ] );
                $values['tm_cena_fm_p']   = array( $fm_arr[ $idx ] );
                $values['tm_cena_lm_p']   = array( $lm_arr[ $idx ] );
                $values['tm_info_p']      = array( $info_arr[ $idx ] );
                break;
            }
        }
    }
    return $values;
}

function firstminute( $datum, $cena ) {
    if ( is_int( $datum ) ) {
        $datum = date( 'd.m.Y', $datum );
    }
    $parts    = explode( '.', $datum );
    $time_do  = strtotime( $parts[2] . '-' . $parts[1] . '-' . $parts[0] );
    $plus6m   = strtotime( '+6 month' );
    $plus3m   = strtotime( '+3 month' );
    $cena_int = intval( $cena );

    if ( $plus6m >= $time_do && $plus3m < $time_do ) {
        $fm = ceil( $cena_int - $cena_int * 0.02 );
        $fm = round( $fm, -1 );
        return number_format( $fm, 0, ',', ' ' );
    } elseif ( $plus6m < $time_do ) {
        $fm = ceil( $cena_int - $cena_int * 0.04 );
        $fm = round( $fm, -1 );
        if ( ( $cena_int - $fm ) > 5000 ) {
            $fm = $cena_int - 5000;
        }
        return number_format( $fm, 0, ',', ' ' );
    }
    return '';
}

// ─── Reference zájezdu: nový repeater NEBO fallback na staré reference_box ─────
/**
 * Vrátí pole referencí zájezdu: [ ['jmeno'=>, 'text'=>, 'foto_id'=>], ... ].
 * Primárně nový repeater k26_ref_* (více referencí); když je prázdný,
 * použije staré jednopolové reference_box (HTML blok = jedno svědectví).
 */
function k26_get_references( int $post_id ): array {
    $jmena = (array) ( get_post_meta( $post_id, 'k26_ref_jmeno',   true ) ?: [] );
    $texty = (array) ( get_post_meta( $post_id, 'k26_ref_text',    true ) ?: [] );
    $fota  = (array) ( get_post_meta( $post_id, 'k26_ref_foto_id', true ) ?: [] );

    $out = array();
    $n   = max( count( $jmena ), count( $texty ), count( $fota ) );
    for ( $i = 0; $i < $n; $i++ ) {
        $jmeno = trim( (string) ( $jmena[ $i ] ?? '' ) );
        $text  = (string) ( $texty[ $i ] ?? '' );
        $foto  = (int) ( $fota[ $i ] ?? 0 );
        if ( $jmeno === '' && trim( $text ) === '' && ! $foto ) continue;
        $out[] = array( 'jmeno' => $jmeno, 'text' => $text, 'foto_id' => $foto );
    }

    // Fallback: staré reference_box (jeden HTML blok)
    if ( ! $out ) {
        $box = (string) get_post_meta( $post_id, 'reference_box', true );
        if ( trim( wp_strip_all_tags( $box ) ) !== '' ) {
            $out[] = array( 'jmeno' => '', 'text' => $box, 'foto_id' => 0 );
        }
    }

    return $out;
}

// ─── Pomocná funkce: české měsíce ─────────────────────────────────────────────

function k26_cesky_mesic( int $mesic ): string {
    static $nazvy = array(
        1 => 'leden', 'únor', 'březen', 'duben', 'květen', 'červen',
        'červenec', 'srpen', 'září', 'říjen', 'listopad', 'prosinec',
    );
    return $nazvy[ $mesic ] ?? '';
}

// ─── Galerie NextGEN (slick slider) ──────────────────────────────────────────

function karavela_photogallery_presentation( int $gallery_id ): string {
    if ( ! $gallery_id ) return '';

    // Přímý DB dotaz – NGG 4.x find_all()/get_gallery() vrací ve frontend
    // kontextu prázdno (špatně sestavený SQL), stejně jako ve feedu.
    global $wpdb;

    $path = $wpdb->get_var( $wpdb->prepare(
        "SELECT path FROM {$wpdb->prefix}ngg_gallery WHERE gid = %d",
        $gallery_id
    ) );
    if ( ! $path ) return '';

    $images = $wpdb->get_results( $wpdb->prepare(
        "SELECT filename, alttext FROM {$wpdb->prefix}ngg_pictures
         WHERE galleryid = %d ORDER BY sortorder ASC",
        $gallery_id
    ) ) ?: [];
    if ( empty( $images ) ) return '';

    $base_url = trailingslashit( site_url() ) . trailingslashit( ltrim( $path, '/' ) );

    $html = '<div class="gallery-images">';
    foreach ( $images as $img ) {
        if ( empty( $img->filename ) ) continue;
        $full = $base_url . $img->filename;
        $alt  = esc_attr( $img->alttext ?? '' );
        // data-lazy = slick lazyLoad (ondemand) – nenačítá všech N fotek najednou
        // class glightbox + data-gallery = lightbox s prev/next přes celou galerii
        $html .= '<div class="single-image-gallery">'
               . '<a href="' . esc_url( $full ) . '" class="glightbox" data-gallery="trip-gallery-' . $gallery_id . '"'
               . ( $alt ? ' data-title="' . $alt . '"' : '' ) . '>'
               . '<img data-lazy="' . esc_url( $full ) . '" alt="' . $alt . '">'
               . '</a></div>';
    }
    $html .= '</div>';

    return $html;
}

// ─── Pagination ───────────────────────────────────────────────────────────────

function k26_pagination( WP_Query $query ) {
    $pages = $query->max_num_pages;
    if ( $pages <= 1 ) return;
    $big = 999999999;
    echo paginate_links( array(
        'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
        'format'  => '?paged=%#%',
        'current' => max( 1, get_query_var( 'paged' ) ),
        'total'   => $pages,
    ) );
}

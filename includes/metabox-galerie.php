<?php
defined( 'ABSPATH' ) || exit;

// ── Registrace ────────────────────────────────────────────────────────────────

add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'k26_galerie',
        'Galerie zájezdu',
        'k26_galerie_render',
        'trip',
        'side',
        'default'
    );
} );

// ── Render ────────────────────────────────────────────────────────────────────

function k26_galerie_render( WP_Post $post ): void {
    wp_nonce_field( 'k26_galerie_save', 'k26_galerie_nonce' );

    $current = (int) get_post_meta( $post->ID, 'trip_ngg_gallery_id', true );

    $galleries = [];
    if ( class_exists( '\Imagely\NGG\DataMappers\Gallery' ) ) {
        $galleries = \Imagely\NGG\DataMappers\Gallery::get_instance()->find_all();
    }

    echo '<select name="trip_ngg_gallery_id" id="trip_ngg_gallery_id" style="width:100%">';
    echo '<option value="0">— žádná galerie —</option>';

    foreach ( $galleries as $gallery ) {
        $gid      = (int) $gallery->gid;
        $selected = selected( $current, $gid, false );
        $label    = esc_html( $gallery->title ) . ' (ID: ' . $gid . ')';
        echo '<option value="' . $gid . '"' . $selected . '>' . $label . '</option>';
    }

    echo '</select>';

    if ( $current ) {
        $url = admin_url( 'admin.php?page=nggallery-manage-gallery&gid=' . $current );
        echo '<p style="margin-top:6px;font-size:11px">';
        echo '<a href="' . esc_url( $url ) . '" target="_blank">Upravit galerii v NextGEN ↗</a>';
        echo '</p>';
    }
}

// ── Uložení ───────────────────────────────────────────────────────────────────

add_action( 'save_post_trip', function ( int $post_id ): void {
    if (
        ! isset( $_POST['k26_galerie_nonce'] ) ||
        ! wp_verify_nonce( $_POST['k26_galerie_nonce'], 'k26_galerie_save' ) ||
        ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
        ! current_user_can( 'edit_post', $post_id )
    ) return;

    $gid = isset( $_POST['trip_ngg_gallery_id'] ) ? absint( $_POST['trip_ngg_gallery_id'] ) : 0;
    update_post_meta( $post_id, 'trip_ngg_gallery_id', $gid );
} );

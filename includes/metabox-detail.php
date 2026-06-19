<?php
defined( 'ABSPATH' ) || exit;

// ── Registrace ────────────────────────────────────────────────────────────────

add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'k26_detail',
        'Detail zájezdu',
        'k26_detail_render',
        'trip',
        'normal',
        'high'
    );
} );

// ── Admin CSS ─────────────────────────────────────────────────────────────────

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;
    if ( get_post_type() !== 'trip' ) return;

    wp_add_inline_style( 'wp-admin', '
        #k26_detail table { border-collapse: collapse; width: 100%; }
        #k26_detail th { background: #f0f0f1; padding: 6px 8px; text-align: left; font-size: 12px; width: 200px; }
        #k26_detail td { padding: 6px 8px; vertical-align: middle; }
        #k26_detail td input[type=text],
        #k26_detail td select { width: 100%; max-width: 480px; box-sizing: border-box; }
        #k26_detail .k26-field-note { color: #666; font-size: 11px; margin-top: 3px; }
    ' );
} );

// ── Render ────────────────────────────────────────────────────────────────────

function k26_detail_render( WP_Post $post ): void {
    wp_nonce_field( 'k26_detail_save', 'k26_detail_nonce' );

    $title      = get_post_meta( $post->ID, '_karavela_title',      true );
    $promo_date = (int) get_post_meta( $post->ID, '_karavela_promo_date', true );

    // Načti termíny pro select promo data
    $od_p  = (array) ( get_post_meta( $post->ID, 'tm_od_p',  true ) ?: [] );
    $do_p  = (array) ( get_post_meta( $post->ID, 'tm_do_p',  true ) ?: [] );
    $kod_p = (array) ( get_post_meta( $post->ID, 'tm_kod_p', true ) ?: [] );
    ?>
    <table>
        <tr>
            <th>Alternativní název</th>
            <td>
                <input type="text" name="_karavela_title" value="<?php echo esc_attr( $title ); ?>">
                <p class="k26-field-note">Zobrazuje se místo nadpisu ve výpisech na homepage. Ponechte prázdné = použije se název příspěvku.</p>
            </td>
        </tr>
        <tr>
            <th>Preferovaný termín na homepage</th>
            <td>
                <?php if ( empty( $od_p ) || empty( $od_p[0] ) ) : ?>
                    <em style="color:#999">Nejprve uložte termíny.</em>
                <?php else : ?>
                <select name="_karavela_promo_date">
                    <option value="0">— automaticky (nejbližší) —</option>
                    <?php foreach ( $od_p as $i => $ts ) :
                        if ( ! $ts ) continue;
                        $label = date( 'd.m.Y', $ts );
                        if ( ! empty( $do_p[ $i ] ) ) $label .= ' – ' . date( 'd.m.Y', $do_p[ $i ] );
                        if ( ! empty( $kod_p[ $i ] ) ) $label .= '  (' . $kod_p[ $i ] . ')';
                    ?>
                        <option value="<?php echo $ts; ?>" <?php selected( $promo_date, $ts ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
                <p class="k26-field-note">Který termín se zobrazí na kartičce zájezdu na homepage.</p>
            </td>
        </tr>
    </table>
    <?php
}

// ── Uložení ───────────────────────────────────────────────────────────────────

add_action( 'save_post_trip', function ( int $post_id ): void {
    if (
        ! isset( $_POST['k26_detail_nonce'] ) ||
        ! wp_verify_nonce( $_POST['k26_detail_nonce'], 'k26_detail_save' ) ||
        ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
        ! current_user_can( 'edit_post', $post_id )
    ) return;

    update_post_meta( $post_id, '_karavela_title',      sanitize_text_field( $_POST['_karavela_title'] ?? '' ) );
    update_post_meta( $post_id, '_karavela_promo_date', absint( $_POST['_karavela_promo_date'] ?? 0 ) );
} );

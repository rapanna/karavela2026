<?php
defined( 'ABSPATH' ) || exit;

// ── Registrace ────────────────────────────────────────────────────────────────

add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'k26_sdovolenka',
        'Sdovolenka export',
        'k26_sdovolenka_render',
        'trip',
        'normal',
        'default'
    );
} );

// ── Admin CSS ─────────────────────────────────────────────────────────────────

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;
    if ( get_post_type() !== 'trip' ) return;

    wp_add_inline_style( 'wp-admin', '
        #k26_sdovolenka table { border-collapse: collapse; width: 100%; }
        #k26_sdovolenka th { background: #f0f0f1; padding: 6px 8px; text-align: left; font-size: 12px; width: 180px; }
        #k26_sdovolenka td { padding: 6px 8px; vertical-align: middle; }
        #k26_sdovolenka td select { width: 100%; max-width: 320px; box-sizing: border-box; }
        #k26_sdovolenka .k26-export-row th { background: #e8f5e9; }
    ' );
} );

// ── Render ────────────────────────────────────────────────────────────────────

function k26_sdovolenka_render( WP_Post $post ): void {
    wp_nonce_field( 'k26_sdovolenka_save', 'k26_sdovolenka_nonce' );

    $exportovat    = get_post_meta( $post->ID, 'sdovolenka_exportovat',  true );
    $typ_zajezdu   = get_post_meta( $post->ID, 'typ-zajezdu-dovolenka', true );
    $typ_dopravy   = get_post_meta( $post->ID, 'typ-dopravy',           true );
    $typ_ubytovani = get_post_meta( $post->ID, 'typ-ubytovani',         true );
    $uroven_ubyt   = get_post_meta( $post->ID, 'uroven-ubyt',           true );
    $typ_stravy    = get_post_meta( $post->ID, 'typ-stravy',            true );

    // Stará data měla 'Ano'/'Ne', nová mají '1'/''
    $checked = ( $exportovat === '1' || $exportovat === 'Ano' );

    $types = [
        '200' => 'poznávací',
        '100' => 'pobytové',
        '101' => 'pobytové – u moře',
        '102' => 'pobytové – v metropoli',
        '103' => 'pobytové – relaxační',
        '104' => 'pobytové – na horách',
        '300' => 'aktivní',
        '301' => 'aktivní – s aerobikem',
        '302' => 'aktivní – na kolo',
        '303' => 'aktivní – inline brusle',
        '304' => 'aktivní – lyže',
        '305' => 'aktivní – hory, turistika',
        '306' => 'aktivní – na vodě',
        '307' => 'aktivní – golf',
        '400' => 'plavby',
        '500' => 'eurovíkend',
    ];

    $transport = [
        '1' => 'vlastní',
        '2' => 'letecky',
        '3' => 'vlakem',
        '4' => 'autobusem',
        '5' => 'lodí',
        '6' => 'kombinovaná',
    ];

    $ubytovani = [
        '0' => 'nestanoveno',
        '1' => 'hotel',
        '2' => 'turistický hotel, ubytovna',
        '3' => 'penzion',
        '4' => 'kemp',
        '5' => 'stan',
        '6' => 'kombinace – ubytovna a stany',
        '8' => 'kombinace – hotel, stan',
        '7' => 'jiné',
    ];

    $urovne = [
        '1'  => 'nespecifikováno',
        '2'  => '1 *',   '3'  => '1 *+',
        '4'  => '2 *',   '5'  => '2 *+',
        '6'  => '3 *',   '7'  => '3 *+',
        '8'  => '4 *',   '9'  => '4 *+',
        '10' => '5 *',   '11' => '5 *+',
    ];

    $strava = [
        '1' => 'bez stravy',
        '2' => 'snídaně',
        '3' => 'večeře',
        '4' => 'polopenze',
        '5' => 'plná penze',
        '6' => 'all inclusive',
        '7' => 'dle programu',
    ];
    ?>
    <table>
        <tr class="k26-export-row">
            <th>Exportovat do Sdovolenky</th>
            <td>
                <input type="checkbox" name="sdovolenka_exportovat" value="1" <?php checked( $checked ); ?>>
                <span style="color:#666;font-size:11px;margin-left:6px">Feed: <a href="<?php echo esc_url( get_bloginfo('url') . '/feed/sdovolena-feed/' ); ?>" target="_blank"><?php echo esc_url( get_bloginfo('url') . '/feed/sdovolena-feed/' ); ?></a></span>
            </td>
        </tr>
        <tr>
            <th>Typ zájezdu</th>
            <td>
                <select name="typ-zajezdu-dovolenka">
                    <?php foreach ( $types as $val => $label ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>"<?php selected( $typ_zajezdu, $val ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>Doprava</th>
            <td>
                <select name="typ-dopravy">
                    <?php foreach ( $transport as $val => $label ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>"<?php selected( $typ_dopravy, $val ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>Ubytování</th>
            <td>
                <select name="typ-ubytovani">
                    <?php foreach ( $ubytovani as $val => $label ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>"<?php selected( $typ_ubytovani, $val ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>Úroveň ubytování</th>
            <td>
                <select name="uroven-ubyt">
                    <?php foreach ( $urovne as $val => $label ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>"<?php selected( $uroven_ubyt, $val ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>Strava</th>
            <td>
                <select name="typ-stravy">
                    <?php foreach ( $strava as $val => $label ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>"<?php selected( $typ_stravy, $val ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>
    <?php
}

// ── Uložení ───────────────────────────────────────────────────────────────────

add_action( 'save_post_trip', function ( int $post_id ): void {
    if (
        ! isset( $_POST['k26_sdovolenka_nonce'] ) ||
        ! wp_verify_nonce( $_POST['k26_sdovolenka_nonce'], 'k26_sdovolenka_save' ) ||
        ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
        ! current_user_can( 'edit_post', $post_id )
    ) return;

    update_post_meta( $post_id, 'sdovolenka_exportovat',  ! empty( $_POST['sdovolenka_exportovat'] ) ? '1' : '' );
    update_post_meta( $post_id, 'typ-zajezdu-dovolenka', sanitize_text_field( $_POST['typ-zajezdu-dovolenka'] ?? '200' ) );
    update_post_meta( $post_id, 'typ-dopravy',           sanitize_text_field( $_POST['typ-dopravy']           ?? '1'   ) );
    update_post_meta( $post_id, 'typ-ubytovani',         sanitize_text_field( $_POST['typ-ubytovani']         ?? '0'   ) );
    update_post_meta( $post_id, 'uroven-ubyt',           sanitize_text_field( $_POST['uroven-ubyt']           ?? '1'   ) );
    update_post_meta( $post_id, 'typ-stravy',            sanitize_text_field( $_POST['typ-stravy']            ?? '1'   ) );
} );

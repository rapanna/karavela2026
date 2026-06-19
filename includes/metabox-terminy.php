<?php
defined( 'ABSPATH' ) || exit;

// ── Registrace metaboxu ───────────────────────────────────────────────────────

add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'k26_terminy',
        'Termíny zájezdu',
        'k26_terminy_render',
        'trip',
        'normal',
        'high'
    );
} );

// ── Admin CSS + JS (jen na stránce editace trip) ──────────────────────────────

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;
    if ( get_post_type() !== 'trip' ) return;

    wp_add_inline_style( 'wp-admin', '
        #k26_terminy table { border-collapse: collapse; width: 100%; }
        #k26_terminy th { background: #f0f0f1; padding: 6px 8px; text-align: left; font-size: 12px; white-space: nowrap; }
        #k26_terminy td { padding: 4px 4px; vertical-align: middle; }
        #k26_terminy td input[type=text],
        #k26_terminy td input[type=date],
        #k26_terminy td input[type=number] { width: 100%; box-sizing: border-box; }
        #k26_terminy td select { width: 100%; }
        #k26_terminy .k26-row-remove { color: #b32d2e; cursor: pointer; border: none; background: none; font-size: 16px; line-height: 1; padding: 2px 6px; }
        #k26_terminy .k26-row-remove:hover { color: #8a1f1f; }
        #k26_terminy tfoot td { padding-top: 10px; }
        .k26-col-kod   { width: 70px; }
        .k26-col-od    { width: 115px; }
        .k26-col-do    { width: 115px; }
        .k26-col-cena  { width: 90px; }
        .k26-col-fm    { width: 90px; }
        .k26-col-lm    { width: 90px; }
        .k26-col-info  { }
        .k26-col-del   { width: 30px; text-align: center; }
    ' );
} );

// ── Render ────────────────────────────────────────────────────────────────────

function k26_terminy_render( WP_Post $post ): void {
    wp_nonce_field( 'k26_terminy_save', 'k26_terminy_nonce' );

    $kod  = (array) ( get_post_meta( $post->ID, 'tm_kod_p',       true ) ?: [] );
    $od   = (array) ( get_post_meta( $post->ID, 'tm_od_p',        true ) ?: [] );
    $do   = (array) ( get_post_meta( $post->ID, 'tm_do_p',        true ) ?: [] );
    $celk = (array) ( get_post_meta( $post->ID, 'tm_cena_celk_p', true ) ?: [] );
    $fm   = (array) ( get_post_meta( $post->ID, 'tm_cena_fm_p',   true ) ?: [] );
    $lm   = (array) ( get_post_meta( $post->ID, 'tm_cena_lm_p',   true ) ?: [] );
    $info = (array) ( get_post_meta( $post->ID, 'tm_info_p',      true ) ?: [] );

    $count = max( count( $kod ), 1 );
    ?>
    <table id="k26-terminy-table">
        <thead>
            <tr>
                <th class="k26-col-kod">Kód</th>
                <th class="k26-col-od">Od</th>
                <th class="k26-col-do">Do</th>
                <th class="k26-col-cena">Cena (Kč)</th>
                <th class="k26-col-fm">FM cena</th>
                <th class="k26-col-lm">LM cena</th>
                <th class="k26-col-info">Info</th>
                <th class="k26-col-del"></th>
            </tr>
        </thead>
        <tbody id="k26-terminy-body">
        <?php for ( $i = 0; $i < $count; $i++ ) :
            $od_val = ! empty( $od[ $i ] ) ? date( 'Y-m-d', (int) $od[ $i ] ) : '';
            $do_val = ! empty( $do[ $i ] ) ? date( 'Y-m-d', (int) $do[ $i ] ) : '';
            ?>
            <tr class="k26-termin-row">
                <td class="k26-col-kod"><input type="text"   name="tm_kod_p[]"       value="<?php echo esc_attr( $kod[ $i ] ?? '' ); ?>"></td>
                <td class="k26-col-od"> <input type="date"   name="tm_od_p[]"        value="<?php echo esc_attr( $od_val ); ?>"></td>
                <td class="k26-col-do"> <input type="date"   name="tm_do_p[]"        value="<?php echo esc_attr( $do_val ); ?>"></td>
                <td class="k26-col-cena"><input type="number" name="tm_cena_celk_p[]" value="<?php echo esc_attr( $celk[ $i ] ?? '' ); ?>" min="0"></td>
                <td class="k26-col-fm"> <input type="number" name="tm_cena_fm_p[]"   value="<?php echo esc_attr( $fm[ $i ] ?? '' ); ?>"   min="0"></td>
                <td class="k26-col-lm"> <input type="number" name="tm_cena_lm_p[]"   value="<?php echo esc_attr( $lm[ $i ] ?? '' ); ?>"   min="0"></td>
                <td class="k26-col-info"><input type="text"  name="tm_info_p[]"      value="<?php echo esc_attr( $info[ $i ] ?? '' ); ?>"></td>
                <td class="k26-col-del"><button type="button" class="k26-row-remove" title="Odebrat">✕</button></td>
            </tr>
        <?php endfor; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8">
                    <button type="button" id="k26-terminy-add" class="button">+ Přidat termín</button>
                </td>
            </tr>
        </tfoot>
    </table>

    <script>
    (function(){
        var tbody = document.getElementById('k26-terminy-body');

        var tpl = '<tr class="k26-termin-row">'
            + '<td class="k26-col-kod"><input type="text"   name="tm_kod_p[]"       value=""></td>'
            + '<td class="k26-col-od"> <input type="date"   name="tm_od_p[]"        value=""></td>'
            + '<td class="k26-col-do"> <input type="date"   name="tm_do_p[]"        value=""></td>'
            + '<td class="k26-col-cena"><input type="number" name="tm_cena_celk_p[]" value="" min="0"></td>'
            + '<td class="k26-col-fm"> <input type="number" name="tm_cena_fm_p[]"   value="" min="0"></td>'
            + '<td class="k26-col-lm"> <input type="number" name="tm_cena_lm_p[]"   value="" min="0"></td>'
            + '<td class="k26-col-info"><input type="text"  name="tm_info_p[]"      value=""></td>'
            + '<td class="k26-col-del"><button type="button" class="k26-row-remove" title="Odebrat">✕</button></td>'
            + '</tr>';

        document.getElementById('k26-terminy-add').addEventListener('click', function(){
            tbody.insertAdjacentHTML('beforeend', tpl);
        });

        tbody.addEventListener('click', function(e){
            if ( e.target.classList.contains('k26-row-remove') ) {
                var rows = tbody.querySelectorAll('.k26-termin-row');
                if ( rows.length > 1 ) {
                    e.target.closest('.k26-termin-row').remove();
                }
            }
        });
    })();
    </script>
    <?php
}

// ── Uložení ───────────────────────────────────────────────────────────────────

add_action( 'save_post_trip', function ( int $post_id ): void {
    if (
        ! isset( $_POST['k26_terminy_nonce'] ) ||
        ! wp_verify_nonce( $_POST['k26_terminy_nonce'], 'k26_terminy_save' ) ||
        ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
        ! current_user_can( 'edit_post', $post_id )
    ) return;

    $fields = [
        'tm_kod_p'       => 'sanitize_text_field',
        'tm_cena_celk_p' => 'absint',
        'tm_cena_fm_p'   => 'absint',
        'tm_cena_lm_p'   => 'absint',
        'tm_info_p'      => 'sanitize_text_field',
    ];

    foreach ( $fields as $key => $sanitizer ) {
        $raw  = isset( $_POST[ $key ] ) ? (array) $_POST[ $key ] : [];
        $data = array_map( $sanitizer, $raw );
        update_post_meta( $post_id, $key, $data );
    }

    // Datum: YYYY-MM-DD → Unix timestamp (UTC půlnoc)
    foreach ( [ 'tm_od_p', 'tm_do_p' ] as $key ) {
        $raw  = isset( $_POST[ $key ] ) ? (array) $_POST[ $key ] : [];
        $data = array_map( function ( $v ) {
            $v = sanitize_text_field( $v );
            return ( $v && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) )
                ? (int) strtotime( $v . ' 00:00:00 UTC' )
                : 0;
        }, $raw );
        update_post_meta( $post_id, $key, $data );
    }
} );

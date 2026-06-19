<?php
defined( 'ABSPATH' ) || exit;

// ── Registrace ────────────────────────────────────────────────────────────────

add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'k26_program',
        'Program zájezdu',
        'k26_program_render',
        'trip',
        'normal',
        'default'
    );
} );

// ── Admin CSS (jen na stránce editace trip) ───────────────────────────────────

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;
    if ( get_post_type() !== 'trip' ) return;

    wp_add_inline_style( 'wp-admin', '
        #k26_program table { border-collapse: collapse; width: 100%; }
        #k26_program th { background: #f0f0f1; padding: 6px 8px; text-align: left; font-size: 12px; }
        #k26_program td { padding: 4px; vertical-align: top; }
        #k26_program td input[type=text] { width: 100%; box-sizing: border-box; }
        #k26_program .k26-popis-wrap textarea { width: 100%; box-sizing: border-box; resize: vertical; min-height: 48px; }
        #k26_program .k26-popis-toolbar { margin-bottom: 3px; }
        #k26_program .k26-btn-bold { font-weight: bold; font-size: 12px; padding: 1px 7px; min-height: 24px; line-height: 22px; }
        #k26_program .k26-col-den   { width: 130px; }
        #k26_program .k26-col-popis { }
        #k26_program .k26-col-del   { width: 30px; text-align: center; }
        #k26_program .k26-row-remove { color: #b32d2e; cursor: pointer; border: none; background: none; font-size: 16px; line-height: 1; padding: 2px 6px; }
        #k26_program .k26-row-remove:hover { color: #8a1f1f; }
        #k26_program tfoot td { padding-top: 10px; }
    ' );
} );

// ── Render ────────────────────────────────────────────────────────────────────

function k26_program_render( WP_Post $post ): void {
    wp_nonce_field( 'k26_program_save', 'k26_program_nonce' );

    $den   = (array) ( get_post_meta( $post->ID, 'k26_prog_den',   true ) ?: [] );
    $popis = (array) ( get_post_meta( $post->ID, 'k26_prog_popis', true ) ?: [] );

    $count = max( count( $den ), 1 );
    ?>
    <p style="color:#666;font-size:12px;margin-bottom:8px;">
        Pole <em>Den</em> nechte prázdné pro řádky bez označení (nadpisy, poznámky).
    </p>
    <table id="k26-program-table">
        <thead>
            <tr>
                <th class="k26-col-den">Den (např. 1. den:)</th>
                <th class="k26-col-popis">Popis</th>
                <th class="k26-col-del"></th>
            </tr>
        </thead>
        <tbody id="k26-program-body">
        <?php for ( $i = 0; $i < $count; $i++ ) : ?>
            <tr class="k26-program-row">
                <td class="k26-col-den"><input type="text" name="k26_prog_den[]" value="<?php echo esc_attr( $den[ $i ] ?? '' ); ?>" placeholder="1. den:"></td>
                <td class="k26-col-popis">
                    <div class="k26-popis-wrap">
                        <div class="k26-popis-toolbar">
                            <button type="button" class="button button-small k26-btn-bold" title="Tučně (označte text a klikněte)">B</button>
                        </div>
                        <textarea name="k26_prog_popis[]"><?php echo esc_textarea( $popis[ $i ] ?? '' ); ?></textarea>
                    </div>
                </td>
                <td class="k26-col-del"><button type="button" class="k26-row-remove" title="Odebrat">✕</button></td>
            </tr>
        <?php endfor; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">
                    <button type="button" id="k26-program-add" class="button">+ Přidat řádek</button>
                </td>
            </tr>
        </tfoot>
    </table>

    <script>
    (function(){
        var tbody = document.getElementById('k26-program-body');

        var tpl = '<tr class="k26-program-row">'
            + '<td class="k26-col-den"><input type="text" name="k26_prog_den[]" value="" placeholder="1. den:"></td>'
            + '<td class="k26-col-popis"><div class="k26-popis-wrap">'
            +   '<div class="k26-popis-toolbar"><button type="button" class="button button-small k26-btn-bold" title="Tučně (označte text a klikněte)">B</button></div>'
            +   '<textarea name="k26_prog_popis[]"></textarea>'
            + '</div></td>'
            + '<td class="k26-col-del"><button type="button" class="k26-row-remove" title="Odebrat">✕</button></td>'
            + '</tr>';

        document.getElementById('k26-program-add').addEventListener('click', function(){
            tbody.insertAdjacentHTML('beforeend', tpl);
        });

        tbody.addEventListener('click', function(e){
            if ( e.target.classList.contains('k26-row-remove') ) {
                var rows = tbody.querySelectorAll('.k26-program-row');
                if ( rows.length > 1 ) {
                    e.target.closest('.k26-program-row').remove();
                }
            }

            if ( e.target.classList.contains('k26-btn-bold') ) {
                var ta = e.target.closest('.k26-popis-wrap').querySelector('textarea');
                var start = ta.selectionStart;
                var end   = ta.selectionEnd;
                if ( start === end ) return; // nic označeno
                var selected = ta.value.substring( start, end );
                var replacement = '<strong>' + selected + '</strong>';
                ta.value = ta.value.substring( 0, start ) + replacement + ta.value.substring( end );
                // Nastav kurzor za vložený tag
                ta.selectionStart = ta.selectionEnd = start + replacement.length;
                ta.focus();
            }
        });
    })();
    </script>
    <?php
}

// ── Uložení ───────────────────────────────────────────────────────────────────

add_action( 'save_post_trip', function ( int $post_id ): void {
    if (
        ! isset( $_POST['k26_program_nonce'] ) ||
        ! wp_verify_nonce( $_POST['k26_program_nonce'], 'k26_program_save' ) ||
        ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
        ! current_user_can( 'edit_post', $post_id )
    ) return;

    $den   = isset( $_POST['k26_prog_den'] )   ? array_map( 'sanitize_text_field', (array) $_POST['k26_prog_den'] )   : [];
    $popis = isset( $_POST['k26_prog_popis'] ) ? array_map( 'wp_kses_post',        (array) $_POST['k26_prog_popis'] ) : [];

    update_post_meta( $post_id, 'k26_prog_den',   $den );
    update_post_meta( $post_id, 'k26_prog_popis', $popis );
} );

<?php
defined( 'ABSPATH' ) || exit;

// ── Registrace ────────────────────────────────────────────────────────────────

add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'k26_reference',
        'Reference',
        'k26_reference_render',
        'trip',
        'normal',
        'default'
    );
} );

// ── Admin CSS + JS (jen na stránce editace trip) ──────────────────────────────

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;
    if ( get_post_type() !== 'trip' ) return;

    wp_enqueue_media();

    wp_add_inline_style( 'wp-admin', '
        #k26_reference table { border-collapse: collapse; width: 100%; }
        #k26_reference th { background: #f0f0f1; padding: 6px 8px; text-align: left; font-size: 12px; }
        #k26_reference td { padding: 6px 4px; vertical-align: top; }
        #k26_reference td input[type=text] { width: 100%; box-sizing: border-box; }
        #k26_reference td textarea { width: 100%; box-sizing: border-box; resize: vertical; min-height: 60px; }
        #k26_reference .k26-col-jmeno  { width: 180px; }
        #k26_reference .k26-col-foto   { width: 120px; }
        #k26_reference .k26-col-text   { }
        #k26_reference .k26-col-del    { width: 30px; text-align: center; }
        #k26_reference .k26-ref-foto-wrap img { max-width: 90px; height: auto; display: block; margin-bottom: 4px; border: 1px solid #ddd; }
        #k26_reference .k26-ref-foto-btn { font-size: 11px; }
        #k26_reference .k26-ref-foto-remove { font-size: 11px; color: #b32d2e; cursor: pointer; display: none; }
        #k26_reference .k26-row-remove { color: #b32d2e; cursor: pointer; border: none; background: none; font-size: 16px; line-height: 1; padding: 2px 6px; }
        #k26_reference .k26-row-remove:hover { color: #8a1f1f; }
        #k26_reference tfoot td { padding-top: 10px; }
    ' );
} );

// ── Render ────────────────────────────────────────────────────────────────────

function k26_reference_render( WP_Post $post ): void {
    wp_nonce_field( 'k26_reference_save', 'k26_reference_nonce' );

    $jmena   = (array) ( get_post_meta( $post->ID, 'k26_ref_jmeno',   true ) ?: [] );
    $foto_ids = (array) ( get_post_meta( $post->ID, 'k26_ref_foto_id', true ) ?: [] );
    $texty   = (array) ( get_post_meta( $post->ID, 'k26_ref_text',    true ) ?: [] );

    $count = max( count( $jmena ), 1 );
    ?>
    <table id="k26-reference-table">
        <thead>
            <tr>
                <th class="k26-col-jmeno">Jméno</th>
                <th class="k26-col-foto">Foto</th>
                <th class="k26-col-text">Text reference</th>
                <th class="k26-col-del"></th>
            </tr>
        </thead>
        <tbody id="k26-reference-body">
        <?php for ( $i = 0; $i < $count; $i++ ) :
            $foto_id  = (int) ( $foto_ids[ $i ] ?? 0 );
            $foto_src = $foto_id ? wp_get_attachment_image_url( $foto_id, 'thumbnail' ) : '';
            ?>
            <tr class="k26-ref-row">
                <td class="k26-col-jmeno"><input type="text" name="k26_ref_jmeno[]" value="<?php echo esc_attr( $jmena[ $i ] ?? '' ); ?>" placeholder="Jméno zákazníka"></td>
                <td class="k26-col-foto">
                    <div class="k26-ref-foto-wrap">
                        <?php if ( $foto_src ) : ?>
                            <img src="<?php echo esc_url( $foto_src ); ?>" alt="">
                        <?php else : ?>
                            <img src="" alt="" style="display:none">
                        <?php endif; ?>
                        <input type="hidden" name="k26_ref_foto_id[]" class="k26-ref-foto-id" value="<?php echo esc_attr( $foto_id ?: '' ); ?>">
                        <button type="button" class="button button-small k26-ref-foto-btn">Vybrat foto</button>
                        <a href="#" class="k26-ref-foto-remove" style="<?php echo $foto_id ? 'display:inline' : 'display:none'; ?>">Odebrat</a>
                    </div>
                </td>
                <td class="k26-col-text"><textarea name="k26_ref_text[]"><?php echo esc_textarea( $texty[ $i ] ?? '' ); ?></textarea></td>
                <td class="k26-col-del"><button type="button" class="k26-row-remove" title="Odebrat řádek">✕</button></td>
            </tr>
        <?php endfor; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">
                    <button type="button" id="k26-reference-add" class="button">+ Přidat referenci</button>
                </td>
            </tr>
        </tfoot>
    </table>

    <script>
    (function($){
        var tbody = document.getElementById('k26-reference-body');

        function initRow(row) {
            var btn    = row.querySelector('.k26-ref-foto-btn');
            var inp    = row.querySelector('.k26-ref-foto-id');
            var img    = row.querySelector('img');
            var remove = row.querySelector('.k26-ref-foto-remove');

            btn.addEventListener('click', function(e){
                e.preventDefault();
                var frame = wp.media({
                    title: 'Vybrat foto reference',
                    button: { text: 'Použít' },
                    multiple: false,
                    library: { type: 'image' }
                });
                frame.on('select', function(){
                    var att = frame.state().get('selection').first().toJSON();
                    inp.value = att.id;
                    img.src   = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
                    img.style.display = '';
                    remove.style.display = 'inline';
                });
                frame.open();
            });

            remove.addEventListener('click', function(e){
                e.preventDefault();
                inp.value        = '';
                img.src          = '';
                img.style.display = 'none';
                remove.style.display = 'none';
            });
        }

        // Init existing rows
        tbody.querySelectorAll('.k26-ref-row').forEach(initRow);

        // Add row
        document.getElementById('k26-reference-add').addEventListener('click', function(){
            var tpl = '<tr class="k26-ref-row">'
                + '<td class="k26-col-jmeno"><input type="text" name="k26_ref_jmeno[]" value="" placeholder="Jméno zákazníka"></td>'
                + '<td class="k26-col-foto"><div class="k26-ref-foto-wrap">'
                + '<img src="" alt="" style="display:none">'
                + '<input type="hidden" name="k26_ref_foto_id[]" class="k26-ref-foto-id" value="">'
                + '<button type="button" class="button button-small k26-ref-foto-btn">Vybrat foto</button>'
                + '<a href="#" class="k26-ref-foto-remove" style="display:none">Odebrat</a>'
                + '</div></td>'
                + '<td class="k26-col-text"><textarea name="k26_ref_text[]"></textarea></td>'
                + '<td class="k26-col-del"><button type="button" class="k26-row-remove" title="Odebrat řádek">✕</button></td>'
                + '</tr>';

            tbody.insertAdjacentHTML('beforeend', tpl);
            initRow( tbody.lastElementChild );
        });

        // Remove row
        tbody.addEventListener('click', function(e){
            if ( e.target.classList.contains('k26-row-remove') ) {
                var rows = tbody.querySelectorAll('.k26-ref-row');
                if ( rows.length > 1 ) {
                    e.target.closest('.k26-ref-row').remove();
                }
            }
        });
    })(jQuery);
    </script>
    <?php
}

// ── Uložení ───────────────────────────────────────────────────────────────────

add_action( 'save_post_trip', function ( int $post_id ): void {
    if (
        ! isset( $_POST['k26_reference_nonce'] ) ||
        ! wp_verify_nonce( $_POST['k26_reference_nonce'], 'k26_reference_save' ) ||
        ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
        ! current_user_can( 'edit_post', $post_id )
    ) return;

    $jmena    = isset( $_POST['k26_ref_jmeno'] )   ? array_map( 'sanitize_text_field', (array) $_POST['k26_ref_jmeno'] )   : [];
    $foto_ids = isset( $_POST['k26_ref_foto_id'] ) ? array_map( 'absint',              (array) $_POST['k26_ref_foto_id'] ) : [];
    $texty    = isset( $_POST['k26_ref_text'] )    ? array_map( 'wp_kses_post',        (array) $_POST['k26_ref_text'] )    : [];

    update_post_meta( $post_id, 'k26_ref_jmeno',   $jmena );
    update_post_meta( $post_id, 'k26_ref_foto_id', $foto_ids );
    update_post_meta( $post_id, 'k26_ref_text',    $texty );
} );

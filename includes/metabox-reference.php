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
    wp_enqueue_editor(); // TinyMCE pro omezený wysiwyg v referencích

    wp_add_inline_style( 'wp-admin', '
        #k26_reference .k26-ref-card { border: 1px solid #dcdcde; border-radius: 4px; padding: 12px; margin-bottom: 14px; background: #fff; }
        #k26_reference .k26-ref-head { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 10px; }
        #k26_reference .k26-ref-jmeno { flex: 1 1 auto; }
        #k26_reference .k26-ref-jmeno label,
        #k26_reference .k26-ref-foto label { display: block; font-size: 11px; font-weight: 600; color: #50575e; margin-bottom: 3px; }
        #k26_reference .k26-ref-jmeno input[type=text] { width: 100%; box-sizing: border-box; }
        #k26_reference .k26-ref-foto { flex: 0 0 120px; }
        #k26_reference .k26-ref-foto-wrap img { max-width: 90px; height: auto; display: block; margin-bottom: 4px; border: 1px solid #ddd; }
        #k26_reference .k26-ref-foto-btn { font-size: 11px; }
        #k26_reference .k26-ref-foto-remove { font-size: 11px; color: #b32d2e; cursor: pointer; display: none; margin-left: 6px; }
        #k26_reference .k26-ref-del { flex: 0 0 auto; }
        #k26_reference .k26-row-remove { color: #b32d2e; cursor: pointer; border: 1px solid #dcdcde; background: #fff; border-radius: 3px; font-size: 15px; line-height: 1; padding: 3px 8px; }
        #k26_reference .k26-row-remove:hover { color: #fff; background: #b32d2e; border-color: #b32d2e; }
        #k26_reference .k26-ref-text label { display: block; font-size: 11px; font-weight: 600; color: #50575e; margin-bottom: 3px; }
        #k26_reference .wp-editor-wrap { margin-bottom: 0; }
        #k26_reference #k26-reference-add { margin-top: 4px; }
    ' );
} );

// ── Render ────────────────────────────────────────────────────────────────────

function k26_reference_render( WP_Post $post ): void {
    wp_nonce_field( 'k26_reference_save', 'k26_reference_nonce' );

    $jmena    = (array) ( get_post_meta( $post->ID, 'k26_ref_jmeno',   true ) ?: [] );
    $foto_ids = (array) ( get_post_meta( $post->ID, 'k26_ref_foto_id', true ) ?: [] );
    $texty    = (array) ( get_post_meta( $post->ID, 'k26_ref_text',    true ) ?: [] );

    $count = max( count( $jmena ), 1 );
    ?>
    <div id="k26-reference-list">
    <?php for ( $i = 0; $i < $count; $i++ ) :
        $foto_id  = (int) ( $foto_ids[ $i ] ?? 0 );
        $foto_src = $foto_id ? wp_get_attachment_image_url( $foto_id, 'thumbnail' ) : '';
        ?>
        <div class="k26-ref-card">
            <div class="k26-ref-head">
                <div class="k26-ref-jmeno">
                    <label>Jméno</label>
                    <input type="text" name="k26_ref_jmeno[]" value="<?php echo esc_attr( $jmena[ $i ] ?? '' ); ?>" placeholder="Jméno zákazníka">
                </div>
                <div class="k26-ref-foto">
                    <label>Foto</label>
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
                </div>
                <div class="k26-ref-del">
                    <label>&nbsp;</label>
                    <button type="button" class="k26-row-remove" title="Odebrat referenci">✕</button>
                </div>
            </div>
            <div class="k26-ref-text">
                <label>Text reference</label>
                <textarea id="k26_ref_text_<?php echo $i; ?>" name="k26_ref_text[]"><?php echo esc_textarea( $texty[ $i ] ?? '' ); ?></textarea>
            </div>
        </div>
    <?php endfor; ?>
    </div>
    <button type="button" id="k26-reference-add" class="button">+ Přidat referenci</button>

    <script>
    jQuery(function($){
        var list    = document.getElementById('k26-reference-list');
        var counter = <?php echo (int) $count; ?>; // další volný index pro id editoru

        // Omezený TinyMCE: jen tučně + odstavce, žádné speciální značky.
        function editorSettings() {
            return {
                mediaButtons: false,
                quicktags: false,
                tinymce: {
                    toolbar1: 'formatselect | bold italic | alignleft aligncenter alignright | undo redo',
                    toolbar2: '', toolbar3: '', toolbar4: '',
                    block_formats: 'Odstavec=p;Nadpis (velký)=h2;Nadpis (střední)=h3;Nadpis (malý)=h4',
                    menubar: false,
                    statusbar: false,
                    branding: false,
                    height: 160,
                    plugins: 'paste',
                    forced_root_block: 'p',
                    valid_elements: 'p[style],h2[style],h3[style],h4[style],br,strong/b,em/i',
                    valid_styles: { '*': 'text-align' },
                    keep_styles: false,
                    paste_as_text: false
                }
            };
        }

        function initEditor(id) {
            if ( window.wp && wp.editor && wp.editor.initialize ) {
                wp.editor.initialize(id, editorSettings());
            }
        }
        function removeEditor(id) {
            if ( window.wp && wp.editor && wp.editor.remove ) {
                wp.editor.remove(id);
            }
        }

        function initFoto(card) {
            var btn    = card.querySelector('.k26-ref-foto-btn');
            var inp    = card.querySelector('.k26-ref-foto-id');
            var img    = card.querySelector('.k26-ref-foto-wrap img');
            var remove = card.querySelector('.k26-ref-foto-remove');

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
                inp.value         = '';
                img.src           = '';
                img.style.display = 'none';
                remove.style.display = 'none';
            });
        }

        // Init existujících karet
        list.querySelectorAll('.k26-ref-card').forEach(function(card){
            initFoto(card);
            var ta = card.querySelector('.k26-ref-text textarea');
            if ( ta ) initEditor(ta.id);
        });

        // Přidání reference
        document.getElementById('k26-reference-add').addEventListener('click', function(){
            var id  = 'k26_ref_text_' + counter++;
            var tpl = ''
                + '<div class="k26-ref-card">'
                +   '<div class="k26-ref-head">'
                +     '<div class="k26-ref-jmeno"><label>Jméno</label>'
                +       '<input type="text" name="k26_ref_jmeno[]" value="" placeholder="Jméno zákazníka"></div>'
                +     '<div class="k26-ref-foto"><label>Foto</label><div class="k26-ref-foto-wrap">'
                +       '<img src="" alt="" style="display:none">'
                +       '<input type="hidden" name="k26_ref_foto_id[]" class="k26-ref-foto-id" value="">'
                +       '<button type="button" class="button button-small k26-ref-foto-btn">Vybrat foto</button>'
                +       '<a href="#" class="k26-ref-foto-remove" style="display:none">Odebrat</a>'
                +     '</div></div>'
                +     '<div class="k26-ref-del"><label>&nbsp;</label>'
                +       '<button type="button" class="k26-row-remove" title="Odebrat referenci">✕</button></div>'
                +   '</div>'
                +   '<div class="k26-ref-text"><label>Text reference</label>'
                +     '<textarea id="' + id + '" name="k26_ref_text[]"></textarea></div>'
                + '</div>';

            list.insertAdjacentHTML('beforeend', tpl);
            var card = list.lastElementChild;
            initFoto(card);
            initEditor(id);
        });

        // Mazání reference
        list.addEventListener('click', function(e){
            if ( ! e.target.classList.contains('k26-row-remove') ) return;
            var cards = list.querySelectorAll('.k26-ref-card');
            if ( cards.length <= 1 ) return;
            var card = e.target.closest('.k26-ref-card');
            var ta   = card.querySelector('.k26-ref-text textarea');
            if ( ta ) removeEditor(ta.id);
            card.remove();
        });

        // Sync TinyMCE → textarea před odesláním formuláře
        var form = document.getElementById('post');
        if ( form ) {
            form.addEventListener('submit', function(){
                if ( window.tinymce ) window.tinymce.triggerSave();
            });
        }
    });
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

    // Povolené značky: tučně, kurzíva, nadpisy a zarovnání (text-align).
    // wp_kses profiltruje 'style' přes safecss_filter_attr → projde jen bezpečné CSS (vč. text-align).
    $allowed = [
        'p'      => [ 'style' => true ],
        'br'     => [],
        'strong' => [],
        'b'      => [],
        'em'     => [],
        'i'      => [],
        'h2'     => [ 'style' => true ],
        'h3'     => [ 'style' => true ],
        'h4'     => [ 'style' => true ],
    ];

    $jmena    = isset( $_POST['k26_ref_jmeno'] )   ? array_map( 'sanitize_text_field', (array) $_POST['k26_ref_jmeno'] )   : [];
    $foto_ids = isset( $_POST['k26_ref_foto_id'] ) ? array_map( 'absint',              (array) $_POST['k26_ref_foto_id'] ) : [];
    $texty    = isset( $_POST['k26_ref_text'] )
        ? array_map( function ( $t ) use ( $allowed ) { return trim( wp_kses( (string) $t, $allowed ) ); }, (array) $_POST['k26_ref_text'] )
        : [];

    update_post_meta( $post_id, 'k26_ref_jmeno',   $jmena );
    update_post_meta( $post_id, 'k26_ref_foto_id', $foto_ids );
    update_post_meta( $post_id, 'k26_ref_text',    $texty );
} );

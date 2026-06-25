<?php
defined( 'ABSPATH' ) || exit;

// ── Registrace ────────────────────────────────────────────────────────────────

add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'k26_specifikace',
        'Specifikace zájezdu',
        'k26_specifikace_render',
        'trip',
        'normal',
        'default'
    );
} );

// ── Admin CSS ─────────────────────────────────────────────────────────────────

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;
    if ( get_post_type() !== 'trip' ) return;

    wp_enqueue_script( 'jquery-ui-sortable' );

    wp_add_inline_style( 'wp-admin', '
        #k26_specifikace table { border-collapse: collapse; width: 100%; }
        #k26_specifikace th { background: #f0f0f1; padding: 6px 8px; text-align: left; font-size: 12px; width: 200px; vertical-align: top; padding-top: 10px; }
        #k26_specifikace td { padding: 6px 8px; vertical-align: top; }
        #k26_specifikace td input[type=text],
        #k26_specifikace td select { box-sizing: border-box; }
        #k26_specifikace td textarea { width: 100%; box-sizing: border-box; resize: vertical; }
        #k26_specifikace .k26-field-note { color: #666; font-size: 11px; margin-top: 3px; }
        #k26_specifikace input[type=checkbox] { width: 18px; height: 18px; vertical-align: middle; margin-right: 6px; }

        /* Repeater cen */
        .k26-cena-list { width: 100%; }
        .k26-cena-row { display: flex; align-items: center; gap: 4px; margin-bottom: 4px; }
        .k26-cena-row input[type=text] { flex: 1; }
        .k26-cena-handle { cursor: move; color: #8a8a8a; flex-shrink: 0; padding: 2px 4px; font-size: 14px; line-height: 1; user-select: none; }
        .k26-cena-handle:hover { color: #2271b1; }
        .k26-cena-row.ui-sortable-helper { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,.2); }
        .k26-cena-placeholder { background: #fffbe5; outline: 1px dashed #f0c33c; height: 30px; margin-bottom: 4px; }
        .k26-btn-bold { font-weight: bold; font-size: 12px; padding: 1px 7px; min-height: 24px; line-height: 22px; flex-shrink: 0; }
        .k26-row-remove { color: #b32d2e; cursor: pointer; border: none; background: none; font-size: 16px; line-height: 1; padding: 2px 6px; flex-shrink: 0; }
        .k26-row-remove:hover { color: #8a1f1f; }
        .k26-cena-add { margin-top: 4px; }
    ' );
} );

// ── Parser starých dat ────────────────────────────────────────────────────────

function k26_parse_cena_string( string $raw ): array {
    $raw = html_entity_decode( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

    // Nahraď různé varianty <br> a </p> za odřádkování
    $raw = preg_replace( '#<br\s*/?>|</br>|</p>\s*<p[^>]*>#i', "\n", $raw );
    $raw = strip_tags( $raw );

    $items = preg_split( '/\n+/', $raw );

    // Pokud po splitu vznikl jen jeden neprázdný token, zkus středník
    $nonempty = array_filter( array_map( 'trim', $items ) );
    if ( count( $nonempty ) <= 1 && strpos( $raw, ';' ) !== false ) {
        $items = explode( ';', $raw );
    }

    $result = [];
    foreach ( $items as $item ) {
        $item = trim( $item );
        $item = preg_replace( '/^[-–•]\s*/', '', $item ); // odstraň odrážku
        $item = trim( $item );
        if ( $item !== '' ) {
            $result[] = $item;
        }
    }

    return $result;
}

// ── Helper: načti položky (nový formát, nebo parsuj starý) ───────────────────

function k26_get_cena_items( int $post_id, string $new_key, string $old_key ): array {
    $new = get_post_meta( $post_id, $new_key, true );
    if ( is_array( $new ) && ! empty( $new ) ) {
        return $new;
    }
    $old = (string) get_post_meta( $post_id, $old_key, true );
    if ( $old !== '' ) {
        return k26_parse_cena_string( $old );
    }
    return [];
}

// ── Render ────────────────────────────────────────────────────────────────────

function k26_specifikace_render( WP_Post $post ): void {
    wp_nonce_field( 'k26_specifikace_save', 'k26_specifikace_nonce' );

    $id_zajezdu         = get_post_meta( $post->ID, 'id_zajezdu',  true );
    $typ_zajezdu        = get_post_meta( $post->ID, 'typ_zajezdu', true );
    $pocet_klientu      = get_post_meta( $post->ID, 'pocet_klientu', true );
    $seznam_odjezdovych = get_post_meta( $post->ID, 'seznam_odjezdovych_mist', true );
    $pojisteni          = get_post_meta( $post->ID, 'pojisteni',   true );
    $doplnujici_text    = get_post_meta( $post->ID, 'k26_doplnujici_text', true );

    $zahrnuje_items   = k26_get_cena_items( $post->ID, 'k26_cena_zahrnuje',   'cena_zahrnuje' );
    $nezahrnuje_items = k26_get_cena_items( $post->ID, 'k26_cena_nezahrnuje', 'cena_nezahrnuje' );

    if ( empty( $zahrnuje_items ) )   $zahrnuje_items   = [ '' ];
    if ( empty( $nezahrnuje_items ) ) $nezahrnuje_items = [ '' ];

    // Stará odjezdová místa: středníky → řádky
    if ( $seznam_odjezdovych && strpos( $seznam_odjezdovych, ';' ) !== false ) {
        $seznam_odjezdovych = implode( "\n", array_map( 'trim', explode( ';', $seznam_odjezdovych ) ) );
    }

    $typy = [
        '1' => 'Poznávací – exotika',
        '2' => 'Poznávací – Evropa (hotely)',
        '3' => 'Poznávací – Evropa (stany)',
        '4' => 'Horská turistika',
    ];
    ?>
    <table>
        <tr>
            <th>Interní ID zájezdu</th>
            <td>
                <input type="text" name="id_zajezdu" value="<?php echo esc_attr( $id_zajezdu ); ?>" style="max-width:160px">
                <p class="k26-field-note">Používá se pro export do sdovolena.cz.</p>
            </td>
        </tr>
        <tr>
            <th>Typ zájezdu</th>
            <td>
                <select name="typ_zajezdu" style="max-width:300px">
                    <?php foreach ( $typy as $val => $label ) : ?>
                    <option value="<?php echo $val; ?>" <?php selected( $typ_zajezdu, $val ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>

        <!-- Cena zahrnuje -->
        <tr>
            <th>Cena zahrnuje</th>
            <td>
                <div class="k26-cena-list" id="k26-zahrnuje-list">
                    <?php foreach ( $zahrnuje_items as $item ) : ?>
                    <div class="k26-cena-row">
                        <span class="k26-cena-handle" title="Přetáhnout pro změnu pořadí">⠿</span>
                        <button type="button" class="button button-small k26-btn-bold" title="Tučně – označte část textu">B</button>
                        <input type="text" name="k26_cena_zahrnuje[]" value="<?php echo esc_attr( $item ); ?>">
                        <button type="button" class="k26-row-remove" title="Odebrat">✕</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button k26-cena-add" data-list="k26-zahrnuje-list" data-name="k26_cena_zahrnuje[]">+ Přidat položku</button>
            </td>
        </tr>

        <!-- Cena nezahrnuje -->
        <tr>
            <th>Cena nezahrnuje</th>
            <td>
                <div class="k26-cena-list" id="k26-nezahrnuje-list">
                    <?php foreach ( $nezahrnuje_items as $item ) : ?>
                    <div class="k26-cena-row">
                        <span class="k26-cena-handle" title="Přetáhnout pro změnu pořadí">⠿</span>
                        <button type="button" class="button button-small k26-btn-bold" title="Tučně – označte část textu">B</button>
                        <input type="text" name="k26_cena_nezahrnuje[]" value="<?php echo esc_attr( $item ); ?>">
                        <button type="button" class="k26-row-remove" title="Odebrat">✕</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button k26-cena-add" data-list="k26-nezahrnuje-list" data-name="k26_cena_nezahrnuje[]">+ Přidat položku</button>
                <label style="margin-top:8px;display:block">
                    <input type="checkbox" name="pojisteni" value="1" <?php checked( $pojisteni, '1' ); ?>>
                    Nelze pojistit (skryje odkaz na cestovní pojištění)
                </label>
            </td>
        </tr>

        <tr>
            <th>Doplňující text</th>
            <td>
                <textarea name="k26_doplnujici_text" rows="4"><?php echo esc_textarea( $doplnujici_text ); ?></textarea>
                <p class="k26-field-note">Volný text pod sekcí „Cena nezahrnuje" na webu (zachová odstavce).</p>
            </td>
        </tr>

        <tr>
            <th>Počet klientů</th>
            <td>
                <textarea name="pocet_klientu" rows="2"><?php echo esc_textarea( html_entity_decode( $pocet_klientu ) ); ?></textarea>
            </td>
        </tr>
        <tr>
            <th>Odjezdová místa</th>
            <td>
                <textarea name="seznam_odjezdovych_mist" rows="4"><?php echo esc_textarea( $seznam_odjezdovych ); ?></textarea>
                <p class="k26-field-note">Každý řádek = jedno odjezdové místo.</p>
            </td>
        </tr>
    </table>

    <script>
    (function(){
        function initRow( row ) {
            var inp = row.querySelector('input[type=text]');

            row.querySelector('.k26-btn-bold').addEventListener('click', function(){
                var start = inp.selectionStart;
                var end   = inp.selectionEnd;
                if ( start === end ) return;
                var sel = inp.value.substring( start, end );
                var rep = '<strong>' + sel + '</strong>';
                inp.value = inp.value.substring( 0, start ) + rep + inp.value.substring( end );
                inp.selectionStart = inp.selectionEnd = start + rep.length;
                inp.focus();
            });

            row.querySelector('.k26-row-remove').addEventListener('click', function(){
                var list = row.closest('.k26-cena-list');
                if ( list.querySelectorAll('.k26-cena-row').length > 1 ) {
                    row.remove();
                }
            });
        }

        document.querySelectorAll('.k26-cena-row').forEach( initRow );

        // Drag & drop řazení řádků (jQuery UI Sortable, úchyt = grip ⠿).
        // Pořadí <div> v DOM = pořadí v $_POST poli = pořadí uložení, žádný sync netřeba.
        if ( window.jQuery && jQuery.fn.sortable ) {
            jQuery('.k26-cena-list').sortable({
                axis: 'y',
                handle: '.k26-cena-handle',
                items: '> .k26-cena-row',
                placeholder: 'k26-cena-placeholder',
                forcePlaceholderSize: true
            });
        }

        document.querySelectorAll('.k26-cena-add').forEach(function( btn ){
            btn.addEventListener('click', function(){
                var list = document.getElementById( btn.dataset.list );
                var name = btn.dataset.name;
                var div  = document.createElement('div');
                div.className = 'k26-cena-row';
                div.innerHTML = '<span class="k26-cena-handle" title="Přetáhnout pro změnu pořadí">⠿</span>'
                    + '<button type="button" class="button button-small k26-btn-bold" title="Tučně">B</button>'
                    + '<input type="text" name="' + name + '" value="">'
                    + '<button type="button" class="k26-row-remove" title="Odebrat">✕</button>';
                list.appendChild( div );
                initRow( div );
                div.querySelector('input').focus();
            });
        });
    })();
    </script>
    <?php
}

// ── Uložení ───────────────────────────────────────────────────────────────────

add_action( 'save_post_trip', function ( int $post_id ): void {
    if (
        ! isset( $_POST['k26_specifikace_nonce'] ) ||
        ! wp_verify_nonce( $_POST['k26_specifikace_nonce'], 'k26_specifikace_save' ) ||
        ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
        ! current_user_can( 'edit_post', $post_id )
    ) return;

    update_post_meta( $post_id, 'id_zajezdu',  sanitize_text_field( $_POST['id_zajezdu']  ?? '' ) );
    update_post_meta( $post_id, 'typ_zajezdu', sanitize_text_field( $_POST['typ_zajezdu'] ?? '1' ) );

    // Cena zahrnuje / nezahrnuje – pole řádků
    foreach ( [ 'k26_cena_zahrnuje', 'k26_cena_nezahrnuje' ] as $key ) {
        $raw   = isset( $_POST[ $key ] ) ? (array) $_POST[ $key ] : [];
        $items = array_values( array_filter( array_map( 'wp_kses_post', $raw ), 'strlen' ) );
        update_post_meta( $post_id, $key, $items );
    }

    update_post_meta( $post_id, 'pocet_klientu', wp_kses_post( $_POST['pocet_klientu'] ?? '' ) );

    update_post_meta( $post_id, 'k26_doplnujici_text', wp_kses_post( $_POST['k26_doplnujici_text'] ?? '' ) );

    $mista = implode( "\n", array_filter( array_map( 'trim', explode( "\n", $_POST['seznam_odjezdovych_mist'] ?? '' ) ) ) );
    update_post_meta( $post_id, 'seznam_odjezdovych_mist', sanitize_textarea_field( $mista ) );

    if ( ! empty( $_POST['pojisteni'] ) ) {
        update_post_meta( $post_id, 'pojisteni', '1' );
    } else {
        delete_post_meta( $post_id, 'pojisteni' );
    }
} );

<?php
defined( 'ABSPATH' ) || exit;

// ── Registrace ────────────────────────────────────────────────────────────────

add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'k26_itinerar',
        'Itinerář (GPS mapa)',
        'k26_itinerar_render',
        'trip',
        'normal',
        'default'
    );
} );

// ── Leaflet v admin ───────────────────────────────────────────────────────────

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) return;
    if ( get_post_type() !== 'trip' ) return;

    wp_enqueue_style(
        'leaflet',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
        [],
        '1.9.4'
    );
    wp_enqueue_script(
        'leaflet',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        [],
        '1.9.4',
        true
    );

    wp_add_inline_style( 'wp-admin', '
        #k26_itinerar .k26-itin-layout { display: flex; gap: 16px; align-items: flex-start; }
        #k26_itinerar .k26-itin-col-map    { flex: 0 0 50%; min-width: 0; }
        #k26_itinerar .k26-itin-col-points { flex: 0 0 calc(50% - 16px); min-width: 0; max-height: 460px; overflow-y: auto; }
        @media (max-width: 1400px) {
            #k26_itinerar .k26-itin-layout { flex-direction: column; }
            #k26_itinerar .k26-itin-col-map    { flex: 1 1 auto; width: 100%; }
            #k26_itinerar .k26-itin-col-points { flex: 1 1 auto; width: 100%; max-height: none; overflow-y: visible; }
        }
        #k26_itinerar #k26-itin-map { width: 100%; height: 420px; border: 1px solid #ddd; }
        #k26_itinerar #k26-itin-search { display: flex; gap: 6px; margin-top: 8px; }
        #k26_itinerar #k26-itin-search input { flex: 1; }
        #k26_itinerar table { border-collapse: collapse; width: 100%; }
        #k26_itinerar th { background: #f0f0f1; padding: 5px 8px; text-align: left; font-size: 11px; }
        #k26_itinerar td { padding: 4px 6px; font-size: 12px; vertical-align: middle; }
        #k26_itinerar td input[type=text] { width: 100%; box-sizing: border-box; font-size: 12px; }
        #k26_itinerar .k26-col-nr   { width: 24px; text-align: center; color: #999; }
        #k26_itinerar .k26-col-lat,
        #k26_itinerar .k26-col-lng  { width: 90px; }
        #k26_itinerar .k26-col-del  { width: 28px; text-align: center; }
        #k26_itinerar .k26-itin-del { color: #b32d2e; cursor: pointer; border: none; background: none; font-size: 16px; padding: 2px 4px; }
        #k26_itinerar .k26-itin-hint { color: #666; font-size: 11px; margin: 6px 0 0; }
        #k26_itinerar tfoot td { padding-top: 8px; }
        #k26-itin-modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,.5); z-index:99999; align-items:center; justify-content:center; }
        #k26-itin-modal.open { display:flex; }
        #k26-itin-modal-inner { background:#fff; padding:20px; border-radius:4px; min-width:300px; }
        #k26-itin-modal-inner h3 { margin:0 0 12px; font-size:14px; }
        #k26-itin-modal-inner input { width:100%; margin-bottom:10px; box-sizing:border-box; }
        #k26-itin-modal-inner .k26-modal-btns { display:flex; gap:8px; justify-content:flex-end; }
    ' );
} );

// ── Render ────────────────────────────────────────────────────────────────────

function k26_itinerar_render( WP_Post $post ): void {
    wp_nonce_field( 'k26_itinerar_save', 'k26_itinerar_nonce' );

    $longitudes = (array) ( get_post_meta( $post->ID, 'longitudes',  true ) ?: [] );
    $latitudes  = (array) ( get_post_meta( $post->ID, 'latitudes',   true ) ?: [] );
    $city_names = (array) ( get_post_meta( $post->ID, 'city-names',  true ) ?: [] );

    $points_json = wp_json_encode(
        array_map( null, $latitudes, $longitudes, $city_names )
    );
    ?>

    <!-- Modal pro název bodu -->
    <div id="k26-itin-modal">
        <div id="k26-itin-modal-inner">
            <h3>Název místa</h3>
            <input type="text" id="k26-itin-modal-name" placeholder="Praha, Tokio, …">
            <div class="k26-modal-btns">
                <button type="button" id="k26-itin-modal-cancel" class="button">Zrušit</button>
                <button type="button" id="k26-itin-modal-ok" class="button button-primary">Přidat bod</button>
            </div>
        </div>
    </div>

    <div class="k26-itin-layout">

    <!-- Levý sloupec: mapa + vyhledávání -->
    <div class="k26-itin-col-map">
        <div id="k26-itin-map"></div>
        <div id="k26-itin-search">
            <input type="text" id="k26-itin-search-input" placeholder="Vyhledat místo (Nominatim)…">
            <button type="button" id="k26-itin-search-btn" class="button">Hledat</button>
        </div>
    </div>

    <!-- Pravý sloupec: seznam bodů -->
    <div class="k26-itin-col-points">
    <table id="k26-itin-table">
        <thead>
            <tr>
                <th class="k26-col-nr">#</th>
                <th class="k26-col-name">Název</th>
                <th class="k26-col-lat">Zeměpisná šířka</th>
                <th class="k26-col-lng">Zeměpisná délka</th>
                <th class="k26-col-del"></th>
            </tr>
        </thead>
        <tbody id="k26-itin-body">
        <?php foreach ( $latitudes as $i => $lat ) :
            if ( $lat === '' && $longitudes[ $i ] === '' ) continue;
        ?>
            <tr class="k26-itin-row">
                <td class="k26-col-nr"><?php echo $i + 1; ?></td>
                <td class="k26-col-name"><input type="text" name="gps_info_name[]" value="<?php echo esc_attr( $city_names[ $i ] ?? '' ); ?>"></td>
                <td class="k26-col-lat"><input type="text" name="gps_info_lat[]"  value="<?php echo esc_attr( $lat ); ?>" class="k26-lat"></td>
                <td class="k26-col-lng"><input type="text" name="gps_info_lot[]"  value="<?php echo esc_attr( $longitudes[ $i ] ?? '' ); ?>" class="k26-lng"></td>
                <td class="k26-col-del"><button type="button" class="k26-itin-del" title="Odebrat">✕</button></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr><td colspan="5"><p class="k26-itin-hint">Klikněte na mapu pro přidání bodu. Pořadí řádků = pořadí trasy.</p></td></tr>
        </tfoot>
    </table>
    </div><!-- /.k26-itin-col-points -->

    </div><!-- /.k26-itin-layout -->

    <script>
    document.addEventListener('DOMContentLoaded', function() {
    (function() {
        var points  = <?php echo $points_json; ?>;
        var map     = L.map('k26-itin-map', { scrollWheelZoom: false });
        var markers = [];
        var polyline = L.polyline([], { color: '#e63e23', weight: 3 }).addTo(map);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap',
            maxZoom: 18
        }).addTo(map);

        // Výchozí pohled – střed Evropy / světa
        map.setView([20, 15], 2);

        // ── Inicializace z uložených dat ─────────────────────────────────────
        if (points && points.length && points[0]) {
            points.forEach(function(p) {
                if (p && p[0] != null && p[1] != null) {
                    addMarkerToMap(parseFloat(p[0]), parseFloat(p[1]), p[2] || '');
                }
            });
            fitMap();
        }

        // ── Klik na mapu → modal ─────────────────────────────────────────────
        var pendingLatLng = null;

        map.on('click', function(e) {
            pendingLatLng = e.latlng;
            document.getElementById('k26-itin-modal-name').value = '';
            document.getElementById('k26-itin-modal').classList.add('open');
            setTimeout(function(){ document.getElementById('k26-itin-modal-name').focus(); }, 50);
        });

        document.getElementById('k26-itin-modal-ok').addEventListener('click', function() {
            if (!pendingLatLng) return;
            var name = document.getElementById('k26-itin-modal-name').value.trim();
            addPoint(pendingLatLng.lat, pendingLatLng.lng, name);
            closeModal();
        });

        document.getElementById('k26-itin-modal-cancel').addEventListener('click', closeModal);

        document.getElementById('k26-itin-modal-name').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); document.getElementById('k26-itin-modal-ok').click(); }
            if (e.key === 'Escape') { e.preventDefault(); closeModal(); }
        });

        function closeModal() {
            pendingLatLng = null;
            document.getElementById('k26-itin-modal').classList.remove('open');
        }

        // ── Vyhledávání (Nominatim) ──────────────────────────────────────────
        document.getElementById('k26-itin-search-btn').addEventListener('click', doSearch);
        document.getElementById('k26-itin-search-input').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') { e.preventDefault(); doSearch(); }
        });

        function doSearch() {
            var q = document.getElementById('k26-itin-search-input').value.trim();
            if (!q) return;
            fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q) + '&limit=1', {
                headers: { 'Accept-Language': 'cs' }
            })
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if (!data.length) { alert('Místo nenalezeno.'); return; }
                var r = data[0];
                map.setView([parseFloat(r.lat), parseFloat(r.lon)], 8);
                // Nabídne přidání bodu
                pendingLatLng = { lat: parseFloat(r.lat), lng: parseFloat(r.lon) };
                document.getElementById('k26-itin-modal-name').value = r.display_name.split(',')[0];
                document.getElementById('k26-itin-modal').classList.add('open');
                setTimeout(function(){ document.getElementById('k26-itin-modal-name').focus(); }, 50);
            })
            .catch(function(){ alert('Chyba při vyhledávání.'); });
        }

        // ── Přidání bodu ─────────────────────────────────────────────────────
        function addPoint(lat, lng, name) {
            addMarkerToMap(lat, lng, name);
            addTableRow(lat, lng, name);
            // fitMap() se nevolá – zachovává aktuální pohled uživatele
        }

        function addMarkerToMap(lat, lng, name) {
            var m = L.marker([lat, lng]).addTo(map);
            if (name) m.bindPopup(name);
            markers.push(m);
            redrawPolyline();
        }

        function redrawPolyline() {
            polyline.setLatLngs(markers.map(function(m){ return m.getLatLng(); }));
        }

        function fitMap() {
            if (markers.length === 1) {
                map.setView(markers[0].getLatLng(), 6);
            } else if (markers.length > 1) {
                var group = new L.featureGroup(markers);
                map.fitBounds(group.getBounds().pad(0.15));
            }
        }

        // ── Tabulka ──────────────────────────────────────────────────────────
        function addTableRow(lat, lng, name) {
            var tbody = document.getElementById('k26-itin-body');
            var nr = tbody.querySelectorAll('.k26-itin-row').length + 1;
            var tr = document.createElement('tr');
            tr.className = 'k26-itin-row';
            tr.innerHTML =
                '<td class="k26-col-nr">' + nr + '</td>' +
                '<td class="k26-col-name"><input type="text" name="gps_info_name[]" value="' + escAttr(name) + '"></td>' +
                '<td class="k26-col-lat"><input type="text" name="gps_info_lat[]" value="' + escAttr(lat.toFixed(6)) + '" class="k26-lat"></td>' +
                '<td class="k26-col-lng"><input type="text" name="gps_info_lot[]" value="' + escAttr(lng.toFixed(6)) + '" class="k26-lng"></td>' +
                '<td class="k26-col-del"><button type="button" class="k26-itin-del" title="Odebrat">✕</button></td>';
            tbody.appendChild(tr);
        }

        // Odebrat řádek
        document.getElementById('k26-itin-body').addEventListener('click', function(e) {
            if (!e.target.classList.contains('k26-itin-del')) return;
            var rows  = Array.from(document.querySelectorAll('.k26-itin-row'));
            var idx   = rows.indexOf(e.target.closest('tr'));
            e.target.closest('tr').remove();
            // Odeber marker
            if (markers[idx]) {
                map.removeLayer(markers[idx]);
                markers.splice(idx, 1);
                redrawPolyline();
            }
            renumberRows();
        });

        function renumberRows() {
            document.querySelectorAll('.k26-itin-row').forEach(function(tr, i) {
                tr.querySelector('.k26-col-nr').textContent = i + 1;
            });
        }

        function escAttr(s) {
            return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
        }
    })();
    }); // DOMContentLoaded
    </script>
    <?php
}

// ── Uložení ───────────────────────────────────────────────────────────────────

add_action( 'save_post_trip', function ( int $post_id ): void {
    if (
        ! isset( $_POST['k26_itinerar_nonce'] ) ||
        ! wp_verify_nonce( $_POST['k26_itinerar_nonce'], 'k26_itinerar_save' ) ||
        ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ||
        ! current_user_can( 'edit_post', $post_id )
    ) return;

    $lats   = array_map( 'sanitize_text_field', (array) ( $_POST['gps_info_lat']  ?? [] ) );
    $lngs   = array_map( 'sanitize_text_field', (array) ( $_POST['gps_info_lot']  ?? [] ) );
    $names  = array_map( 'sanitize_text_field', (array) ( $_POST['gps_info_name'] ?? [] ) );

    // Odfiltruj prázdné řádky (obě souřadnice prázdné)
    $clean_lats = $clean_lngs = $clean_names = [];
    foreach ( $lats as $i => $lat ) {
        if ( $lat === '' && ( $lngs[ $i ] ?? '' ) === '' ) continue;
        $clean_lats[]  = $lat;
        $clean_lngs[]  = $lngs[ $i ] ?? '';
        $clean_names[] = $names[ $i ] ?? '';
    }

    update_post_meta( $post_id, 'latitudes',   $clean_lats );
    update_post_meta( $post_id, 'longitudes',  $clean_lngs );
    update_post_meta( $post_id, 'city-names',  $clean_names );
} );

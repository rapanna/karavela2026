<?php

defined( 'ABSPATH' ) || exit;

// ─── Registrace stránek ───────────────────────────────────────────────────────

add_action( 'admin_menu', 'k26_register_options_pages' );

function k26_register_options_pages() {
    $h1 = add_menu_page(
        'Nastavení Karavela', 'Nastavení', 'manage_options',
        'k26-nastaveni', 'k26_render_nastaveni',
        'dashicons-admin-generic', 61
    );
    $h2 = add_submenu_page( 'k26-nastaveni', 'Proč Karavela?', 'Proč Karavela?', 'manage_options', 'k26-whys',  'k26_render_whys' );
    $h3 = add_submenu_page( 'k26-nastaveni', 'Slevy',          'Slevy',          'manage_options', 'k26-slevy', 'k26_render_slevy' );

    add_action( 'admin_enqueue_scripts', function ( $hook ) use ( $h1, $h2, $h3 ) {
        if ( in_array( $hook, [ $h1, $h2, $h3 ], true ) ) {
            wp_enqueue_media();
        }
    } );
}

// ─── Helper ───────────────────────────────────────────────────────────────────

function k26_setting( $field ) {
    $opts = get_option( 'karavela_settings_', [] );
    return isset( $opts[ $field ] ) ? $opts[ $field ] : '';
}

// ─── NASTAVENÍ ────────────────────────────────────────────────────────────────

function k26_render_nastaveni() {
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && check_admin_referer( 'k26_nastaveni_save', 'k26_nonce' ) ) {
        $data = [];

        foreach ( [ 'karavela_main_telefon', 'karavela_alt_telefon', 'karavela_alt_telefon2',
                    'karavela_email', 'karavela_adresa',
                    'karavela_objednavky_email', 'karavela_kontakt_email',
                    'karavela_recaptcha_site', 'karavela_recaptcha_secret' ] as $f ) {
            $data[ $f ] = sanitize_text_field( $_POST[ $f ] ?? '' );
        }

        $data['karavela_podminky'] = wp_kses_post( $_POST['karavela_podminky'] ?? '' );

        $partners = [];
        foreach ( (array) ( $_POST['partners_image'] ?? [] ) as $i => $img ) {
            $img = esc_url_raw( $img );
            $url = esc_url_raw( $_POST['partners_url'][ $i ] ?? '' );
            if ( $img || $url ) {
                $partners[] = [ 'image' => $img, 'url' => $url ];
            }
        }
        $data['partners'] = $partners;

        update_option( 'karavela_settings_', $data );
        echo '<div class="notice notice-success is-dismissible"><p>Nastavení uloženo.</p></div>';
    }

    $opts     = get_option( 'karavela_settings_', [] );
    $v        = fn( $k ) => esc_attr( $opts[ $k ] ?? '' );
    $partners = $opts['partners'] ?? [];
    ?>
    <div class="wrap">
        <h1>Nastavení Karavela</h1>
        <form method="post">
            <?php wp_nonce_field( 'k26_nastaveni_save', 'k26_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">Telefon (hlavní)</th>
                    <td><input type="text" name="karavela_main_telefon" value="<?= $v( 'karavela_main_telefon' ) ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row">Telefon 2</th>
                    <td><input type="text" name="karavela_alt_telefon" value="<?= $v( 'karavela_alt_telefon' ) ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row">Telefon 3</th>
                    <td><input type="text" name="karavela_alt_telefon2" value="<?= $v( 'karavela_alt_telefon2' ) ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row">Email</th>
                    <td>
                        <input type="text" name="karavela_email" value="<?= $v( 'karavela_email' ) ?>" class="regular-text">
                        <p class="description">Veřejný kontaktní e-mail zobrazený v patičce webu.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Adresa</th>
                    <td><input type="text" name="karavela_adresa" value="<?= $v( 'karavela_adresa' ) ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row">Email na objednávky</th>
                    <td>
                        <input type="text" name="karavela_objednavky_email" value="<?= $v( 'karavela_objednavky_email' ) ?>" class="regular-text">
                        <p class="description">Cílová adresa pro rezervační formulář.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Email pro kontakt</th>
                    <td>
                        <input type="text" name="karavela_kontakt_email" value="<?= $v( 'karavela_kontakt_email' ) ?>" class="regular-text">
                        <p class="description">Cílová adresa pro kontaktní formulář.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">reCAPTCHA – Site key</th>
                    <td>
                        <input type="text" name="karavela_recaptcha_site" value="<?= $v( 'karavela_recaptcha_site' ) ?>" class="regular-text">
                        <p class="description">Google reCAPTCHA v2 „Site key" pro formuláře. Prázdné = captcha vypnuta.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">reCAPTCHA – Secret key</th>
                    <td>
                        <input type="text" name="karavela_recaptcha_secret" value="<?= $v( 'karavela_recaptcha_secret' ) ?>" class="regular-text">
                        <p class="description">Google reCAPTCHA v2 „Secret key" (serverové ověření).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Obchodní podmínky</th>
                    <td><?php wp_editor( $opts['karavela_podminky'] ?? '', 'karavela_podminky', [ 'textarea_rows' => 10 ] ); ?></td>
                </tr>
                <tr>
                    <th scope="row">Partneři</th>
                    <td>
                        <div id="k26-partners-list">
                            <?php foreach ( $partners as $p ) : ?>
                            <div class="k26-repeater-row" style="display:flex;gap:8px;align-items:center;margin-bottom:6px;">
                                <input type="text" name="partners_image[]" value="<?= esc_attr( $p['image'] ?? '' ) ?>" class="regular-text" placeholder="URL loga">
                                <button type="button" class="button k26-media-btn">Vybrat</button>
                                <input type="text" name="partners_url[]" value="<?= esc_attr( $p['url'] ?? $p[''] ?? '' ) ?>" class="regular-text" placeholder="Odkaz (URL)">
                                <button type="button" class="button k26-remove-row">×</button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button" id="k26-add-partner">+ Přidat partnera</button>
                    </td>
                </tr>
            </table>
            <p><input type="submit" class="button button-primary" value="Uložit nastavení"></p>
        </form>
    </div>
    <script>
    (function($){
        var rowTpl = '<div class="k26-repeater-row" style="display:flex;gap:8px;align-items:center;margin-bottom:6px;">'
            + '<input type="text" name="partners_image[]" value="" class="regular-text" placeholder="URL loga">'
            + '<button type="button" class="button k26-media-btn">Vybrat</button>'
            + '<input type="text" name="partners_url[]" value="" class="regular-text" placeholder="Odkaz (URL)">'
            + '<button type="button" class="button k26-remove-row">×</button>'
            + '</div>';

        $('#k26-add-partner').on('click', function() {
            $('#k26-partners-list').append(rowTpl);
        });

        $(document).on('click', '.k26-remove-row', function() {
            $(this).closest('.k26-repeater-row').remove();
        });

        $(document).on('click', '.k26-media-btn', function() {
            var $imgInput = $(this).prev('input[type=text]');
            var frame = wp.media({ title: 'Vybrat logo', button: { text: 'Použít' }, multiple: false });
            frame.on('select', function() {
                $imgInput.val(frame.state().get('selection').first().toJSON().url);
            });
            frame.open();
        });
    })(jQuery);
    </script>
    <?php
}

// ─── PROČ KARAVELA? ───────────────────────────────────────────────────────────

function k26_render_whys() {
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && check_admin_referer( 'k26_whys_save', 'k26_nonce' ) ) {
        $whys = [];
        foreach ( (array) ( $_POST['why_number'] ?? [] ) as $i => $num ) {
            $num  = sanitize_text_field( $num );
            $text = sanitize_textarea_field( $_POST['why_text'][ $i ] ?? '' );
            if ( $num || $text ) {
                $whys[] = [ 'number' => $num, 'text' => $text ];
            }
        }
        update_option( 'karavela_whys_', [ 'whys' => $whys ] );
        echo '<div class="notice notice-success is-dismissible"><p>Uloženo.</p></div>';
    }

    $whys = get_option( 'karavela_whys_', [] )['whys'] ?? [];
    ?>
    <div class="wrap">
        <h1>Proč Karavela?</h1>
        <p class="description">Zobrazuje se v patičce na všech stránkách mimo detail zájezdu (max. 4 záznamy).</p>
        <form method="post" style="margin-top:16px;">
            <?php wp_nonce_field( 'k26_whys_save', 'k26_nonce' ); ?>
            <div id="k26-whys-list">
                <?php foreach ( $whys as $w ) : ?>
                <div class="k26-repeater-row" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:8px;">
                    <input type="text" name="why_number[]" value="<?= esc_attr( $w['number'] ?? '' ) ?>" style="width:120px;" placeholder="Číslo / hodnota">
                    <textarea name="why_text[]" rows="3" style="width:420px;"><?= esc_textarea( $w['text'] ?? '' ) ?></textarea>
                    <button type="button" class="button k26-remove-row">×</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button" id="k26-add-why">+ Přidat důvod</button>
            <p style="margin-top:16px;"><input type="submit" class="button button-primary" value="Uložit"></p>
        </form>
    </div>
    <script>
    (function($){
        var tpl = '<div class="k26-repeater-row" style="display:flex;gap:8px;align-items:flex-start;margin-bottom:8px;">'
            + '<input type="text" name="why_number[]" value="" style="width:120px;" placeholder="Číslo / hodnota">'
            + '<textarea name="why_text[]" rows="3" style="width:420px;"></textarea>'
            + '<button type="button" class="button k26-remove-row">×</button>'
            + '</div>';

        $('#k26-add-why').on('click', function() {
            $('#k26-whys-list').append(tpl);
        });

        $(document).on('click', '.k26-remove-row', function() {
            $(this).closest('.k26-repeater-row').remove();
        });
    })(jQuery);
    </script>
    <?php
}

// ─── SLEVY ────────────────────────────────────────────────────────────────────

function k26_render_slevy() {
    if ( 'POST' === $_SERVER['REQUEST_METHOD'] && check_admin_referer( 'k26_slevy_save', 'k26_nonce' ) ) {
        $slevy = [];
        foreach ( (array) ( $_POST['sleva_title'] ?? [] ) as $i => $title ) {
            $title = sanitize_text_field( $title );
            $text  = wp_kses_post( $_POST['sleva_text'][ $i ] ?? '' );
            if ( $title || $text ) {
                $slevy[] = [ 'title' => $title, 'text' => $text ];
            }
        }
        update_option( 'karavela_slevy', [ 'slevy' => $slevy ] );
        echo '<div class="notice notice-success is-dismissible"><p>Uloženo.</p></div>';
    }

    $slevy = get_option( 'karavela_slevy', [] )['slevy'] ?? [];
    ?>
    <div class="wrap">
        <h1>Slevy</h1>
        <p class="description">Zobrazují se v rezervačním formuláři jako výběr slevy.</p>
        <form method="post" style="margin-top:16px;">
            <?php wp_nonce_field( 'k26_slevy_save', 'k26_nonce' ); ?>
            <div id="k26-slevy-list">
                <?php foreach ( $slevy as $s ) : ?>
                <div class="k26-repeater-row" style="border:1px solid #ddd;padding:14px;margin-bottom:10px;background:#fff;max-width:700px;">
                    <p>
                        <label><strong>Název slevy</strong><br>
                        <input type="text" name="sleva_title[]" value="<?= esc_attr( $s['title'] ?? '' ) ?>" class="regular-text"></label>
                    </p>
                    <p>
                        <label><strong>Popis</strong><br>
                        <textarea name="sleva_text[]" rows="4" class="large-text"><?= esc_textarea( $s['text'] ?? '' ) ?></textarea></label>
                    </p>
                    <button type="button" class="button k26-remove-row">Odebrat</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button" id="k26-add-sleva">+ Přidat slevu</button>
            <p style="margin-top:16px;"><input type="submit" class="button button-primary" value="Uložit"></p>
        </form>
    </div>
    <script>
    (function($){
        var tpl = '<div class="k26-repeater-row" style="border:1px solid #ddd;padding:14px;margin-bottom:10px;background:#fff;max-width:700px;">'
            + '<p><label><strong>Název slevy</strong><br><input type="text" name="sleva_title[]" value="" class="regular-text"></label></p>'
            + '<p><label><strong>Popis</strong><br><textarea name="sleva_text[]" rows="4" class="large-text"></textarea></label></p>'
            + '<button type="button" class="button k26-remove-row">Odebrat</button>'
            + '</div>';

        $('#k26-add-sleva').on('click', function() {
            $('#k26-slevy-list').append(tpl);
        });

        $(document).on('click', '.k26-remove-row', function() {
            $(this).closest('.k26-repeater-row').remove();
        });
    })(jQuery);
    </script>
    <?php
}

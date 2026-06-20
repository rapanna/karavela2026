<?php
/**
 * Rezervační formulář (detail zájezdu).
 *
 * Náhrada za starý includes/reservation_form.php. Změny:
 *  - reCAPTCHA klíče čte z nastavení (k26_setting), nejsou natvrdo v kódu;
 *    bez klíčů se captcha přeskočí (lokální vývoj).
 *  - opraven bug `$paged` – dropdown termínů nyní vypisuje VŠECHNY termíny.
 *  - cílový e-mail: k26_setting('karavela_objednavky_email').
 *  - slevy: get_option('karavela_slevy')['slevy'].
 *  - sanitizace vstupů + nonce + honeypot.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Obsahuje text odkaz (http/www)? Anti-spam.
 */
function k26_text_contains_link( $text ) {
	return (bool) preg_match( '/\b(?:https?:\/\/|www\.)[^\s]+/i', (string) $text );
}

/**
 * Ověří reCAPTCHA v2 odpověď. Vrací true, pokud captcha není nakonfigurována
 * (klíče prázdné) – aby formulář fungoval i bez reCAPTCHA.
 */
function k26_verify_recaptcha( $response ) {
	$secret = k26_setting( 'karavela_recaptcha_secret' );
	if ( ! $secret ) {
		return true; // captcha vypnuta
	}
	if ( ! $response ) {
		return false;
	}

	$res = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', array(
		'timeout' => 8,
		'body'    => array(
			'secret'   => $secret,
			'response' => $response,
			'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
		),
	) );

	if ( is_wp_error( $res ) ) {
		return false;
	}
	$data = json_decode( wp_remote_retrieve_body( $res ) );
	return ! empty( $data->success );
}

/**
 * Rate-limit odesílání formulářů podle IP (transient).
 * Vrací true, pokud je IP přes limit (= požadavek zablokovat).
 *
 * @param string $bucket Identifikátor formuláře (oddělené počítadlo).
 * @param int    $max    Max. počet odeslání v okně.
 * @param int    $window Délka okna v sekundách.
 */
function k26_form_rate_limited( $bucket, $max = 5, $window = 600 ) {
	$ip = $_SERVER['REMOTE_ADDR'] ?? '';
	if ( '' === $ip ) {
		return false; // bez IP nelze spolehlivě omezit – nech projít
	}
	$key   = 'k26_rl_' . $bucket . '_' . md5( $ip );
	$count = (int) get_transient( $key );
	if ( $count >= $max ) {
		return true;
	}
	set_transient( $key, $count + 1, $window );
	return false;
}

/**
 * Zpracuje odeslaný rezervační formulář. Vrací pole se zprávou:
 *   [ 'type' => 'success'|'error'|'', 'message' => string ]
 */
function k26_handle_reservation_submit( $post_id ) {
	$result = array( 'type' => '', 'message' => '' );

	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['k26_reservation_submit'] ) ) {
		return $result;
	}

	// Nonce
	if ( ! isset( $_POST['k26_res_nonce'] ) || ! wp_verify_nonce( $_POST['k26_res_nonce'], 'k26_reservation' ) ) {
		$result['type']    = 'error';
		$result['message'] = 'Platnost formuláře vypršela, zkuste jej prosím odeslat znovu.';
		return $result;
	}

	// Rate-limit: max 5 odeslání / 10 min z jedné IP
	if ( k26_form_rate_limited( 'reservation' ) ) {
		$result['type']    = 'error';
		$result['message'] = 'Příliš mnoho pokusů. Zkuste to prosím za chvíli znovu nebo nám zavolejte.';
		return $result;
	}

	// Honeypoty: skryté pole reservace_web musí být prázdné, titul také.
	if ( ! empty( $_POST['reservace_web'] ) || ! empty( trim( (string) ( $_POST['reservation_titul'] ?? '' ) ) ) ) {
		$result['type']    = 'error';
		$result['message'] = 'Formulář se nepodařilo odeslat.';
		return $result;
	}

	// reCAPTCHA
	if ( ! k26_verify_recaptcha( $_POST['g-recaptcha-response'] ?? '' ) ) {
		$result['type']    = 'error';
		$result['message'] = 'Prosím, potvrďte, že nejste robot.';
		return $result;
	}

	// Anti-spam: žádné odkazy v textových polích
	$check_fields = array(
		'reservation_name', 'reservation_surname', 'reservation_phone',
		'reservation_adress', 'reservation_hopin', 'reservation_persons',
		'reservation_comments', 'reservation_trip_name',
	);
	foreach ( $check_fields as $f ) {
		if ( k26_text_contains_link( $_POST[ $f ] ?? '' ) ) {
			$result['type']    = 'error';
			$result['message'] = 'Formulář se nepodařilo odeslat.';
			return $result;
		}
	}

	// Sanitizace
	$name      = sanitize_text_field( $_POST['reservation_name']     ?? '' );
	$surname   = sanitize_text_field( $_POST['reservation_surname']  ?? '' );
	$phone     = sanitize_text_field( $_POST['reservation_phone']    ?? '' );
	$email     = sanitize_email(      $_POST['reservation_email']    ?? '' );
	$adress    = sanitize_textarea_field( $_POST['reservation_adress']   ?? '' );
	$hopin     = sanitize_textarea_field( $_POST['reservation_hopin']    ?? '' );
	$persons   = sanitize_textarea_field( $_POST['reservation_persons']  ?? '' );
	$comments  = sanitize_textarea_field( $_POST['reservation_comments'] ?? '' );
	$trip_name = sanitize_text_field( $_POST['reservation_trip_name'] ?? get_the_title( $post_id ) );
	$trip_name = html_entity_decode( $trip_name, ENT_QUOTES, 'UTF-8' );

	// Kód termínu: z tlačítka u termínu (reservation_code) nebo z dropdownu
	$term_code = sanitize_text_field( $_POST['reservation_code'] ?? '' );
	if ( '' === $term_code ) {
		$term_code = sanitize_text_field( $_POST['trip-term-date'] ?? '' );
	}

	// Dohledání detailu termínu podle kódu – ať má majitel v mailu datum, ne jen kód
	$term_label = '';
	if ( '' !== $term_code ) {
		$kods = (array) ( get_post_meta( $post_id, 'tm_kod_p',       true ) ?: array() );
		$ods  = (array) ( get_post_meta( $post_id, 'tm_od_p',        true ) ?: array() );
		$dos  = (array) ( get_post_meta( $post_id, 'tm_do_p',        true ) ?: array() );
		$ceny = (array) ( get_post_meta( $post_id, 'tm_cena_celk_p', true ) ?: array() );
		$idx  = array_search( (string) $term_code, array_map( 'strval', $kods ), true );
		if ( false !== $idx && ! empty( $ods[ $idx ] ) ) {
			$term_label = date( 'd.m.Y', $ods[ $idx ] ) . ' – ' . date( 'd.m.Y', $dos[ $idx ] ?? $ods[ $idx ] );
			if ( ! empty( $ceny[ $idx ] ) ) {
				$term_label .= ', ' . number_format( (float) $ceny[ $idx ], 0, ',', ' ' ) . ' Kč';
			}
		}
	}

	$slevy = array();
	foreach ( (array) ( $_POST['slevy'] ?? array() ) as $s ) {
		$slevy[] = sanitize_text_field( $s );
	}

	// Povinná pole
	if ( '' === $name || '' === $surname || '' === $phone || ! is_email( $email ) ) {
		$result['type']    = 'error';
		$result['message'] = 'Vyplňte prosím jméno, příjmení, telefon a platný e-mail.';
		return $result;
	}

	// Sestavení e-mailu
	$to = k26_setting( 'karavela_objednavky_email' );
	if ( ! is_email( $to ) ) {
		$to = get_option( 'admin_email' );
	}

	$subject = 'Rezervace zájezdu - ' . $trip_name;

	$body  = '<p>Dobrý den, zde jsou detaily rezervace.</p>';
	$rows  = array(
		'Jméno'         => $name,
		'Příjmení'      => $surname,
		'Telefon'       => $phone,
		'Email'         => $email,
		'Adresa'        => $adress,
		'Nástupní místo'=> $hopin,
		'Další osoby'   => $persons,
		'Poznámky'      => $comments,
		'Název zájezdu' => $trip_name,
	);
	if ( '' !== $term_label ) {
		$rows['Termín'] = $term_label;
	}
	$rows['Kód zájezdu'] = $term_code;
	foreach ( $rows as $label => $val ) {
		$body .= esc_html( $label ) . ': ' . nl2br( esc_html( $val ) ) . '<br/>';
	}
	if ( $slevy ) {
		$body .= 'Slevy: ' . esc_html( implode( ', ', $slevy ) ) . '<br/>';
	}

	$headers = array(
		'Content-type: text/html; charset=UTF-8',
		'From: Karavela <web_karavela@karavela.cz>',
		'Reply-To: ' . $name . ' ' . $surname . ' <' . $email . '>',
	);

	$sent = wp_mail( $to, $subject, $body, $headers );
	// Kopie zákazníkovi
	wp_mail( $email, $subject, $body, $headers );

	if ( $sent ) {
		$result['type']    = 'success';
		$result['message'] = 'Děkujeme, vaši rezervaci jsme přijali. Brzy se vám ozveme.';
	} else {
		$result['type']    = 'error';
		$result['message'] = 'Rezervaci se nepodařilo odeslat. Zkuste to prosím znovu nebo nám zavolejte.';
	}

	return $result;
}

/**
 * Vykreslí rezervační formulář (modal). Volat uvnitř smyčky na single-trip.php.
 */
function k26_render_reservation_form( $post_id = 0 ) {
	$post_id = $post_id ?: get_the_ID();

	$result = k26_handle_reservation_submit( $post_id );

	// Termíny
	$tm_kod_p = (array) ( get_post_meta( $post_id, 'tm_kod_p', true ) ?: array() );
	$tm_od_p  = (array) ( get_post_meta( $post_id, 'tm_od_p',  true ) ?: array() );
	$tm_do_p  = (array) ( get_post_meta( $post_id, 'tm_do_p',  true ) ?: array() );

	// Slevy
	$discounts = get_option( 'karavela_slevy', array() )['slevy'] ?? array();

	// reCAPTCHA site key
	$recaptcha_site = k26_setting( 'karavela_recaptcha_site' );

	$trip_title = get_the_title( $post_id );
	?>
	<div class="content--trip__reservation-form" id="trip-reservation-form">
		<div class="container">
			<div class="row">
				<div class="col-sm-12">
					<div class="content--trip__reservation-form__content">
						<button type="button" class="close">×</button>
						<h3 class="title title--h3">Rezervace</h3>

						<?php if ( $result['message'] ) : ?>
							<p class="reservation-message reservation-message--<?php echo esc_attr( $result['type'] ); ?>">
								<?php echo esc_html( $result['message'] ); ?>
							</p>
						<?php endif; ?>

						<?php if ( 'success' !== $result['type'] ) : ?>
						<form method="post" role="form">
							<?php wp_nonce_field( 'k26_reservation', 'k26_res_nonce' ); ?>
							<input type="hidden" name="reservation_trip_name" id="reservation_trip_name" value="<?php echo esc_attr( $trip_title ); ?>">
							<input type="hidden" name="reservation_code" id="reservation_code" value="">

							<div class="row">
								<div class="col-sm-8">
									<div class="row">
										<!-- honeypot: titul -->
										<div class="col-sm-6 reservationform" style="display:none">
											<div class="form-group">
												<label for="reservation_titul">Titul</label>
												<input type="text" class="form-control" id="reservation_titul" name="reservation_titul" autocomplete="off" tabindex="-1">
											</div>
										</div>

										<div class="col-sm-6">
											<div class="form-group">
												<label for="reservation_name">Jméno</label>
												<input type="text" class="form-control" id="reservation_name" name="reservation_name" placeholder="Jméno" required>
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group">
												<label for="reservation_surname">Příjmení</label>
												<input type="text" class="form-control" id="reservation_surname" name="reservation_surname" placeholder="Příjmení" required>
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group">
												<label for="reservation_phone">Telefon</label>
												<input type="text" class="form-control" id="reservation_phone" name="reservation_phone" placeholder="Telefon" required>
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group">
												<label for="reservation_email">Email</label>
												<input type="email" class="form-control" id="reservation_email" name="reservation_email" placeholder="Email" required>
											</div>
											<!-- honeypot: reservace_web -->
											<div class="form-group nolimit" style="display:none">
												<label for="reservace_web">Úprava</label>
												<input type="text" class="form-control" id="reservace_web" name="reservace_web" autocomplete="off" tabindex="-1">
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group">
												<label for="reservation_adress">Adresa</label>
												<textarea class="form-control" rows="3" id="reservation_adress" name="reservation_adress"></textarea>
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group reservation-date">
												<label for="trip-term-date">Termín:</label>
												<select class="form-control" name="trip-term-date" id="trip-term-date">
													<option value="">Zvolte termín</option>
													<?php foreach ( $tm_kod_p as $i => $kod ) :
														if ( empty( $tm_od_p[ $i ] ) ) {
															continue;
														}
														$label = date( 'd.m.Y', $tm_od_p[ $i ] )
															. ' - ' . date( 'd.m.Y', $tm_do_p[ $i ] ?? $tm_od_p[ $i ] );
													?>
													<option value="<?php echo esc_attr( $kod ); ?>"><?php echo esc_html( $label ); ?></option>
													<?php endforeach; ?>
												</select>
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group">
												<label for="reservation_hopin">Nástupní místo</label>
												<textarea class="form-control" rows="3" placeholder="V případě, že nástup na zájezd je možný na více místech, uveďte prosím toto nástupní místo" id="reservation_hopin" name="reservation_hopin"></textarea>
											</div>
										</div>
										<?php if ( is_array( $discounts ) && $discounts ) : ?>
										<div class="col-sm-12">
											<div class="form-group checkboxes-group slevy" style="font-size: 80%;">
												<h4>V současné době poskytujeme slevy na naše zájezdy až 8 %.</h4>
												<p>Příslušející slevy pod bodem a, b, c, d můžete sčítat do max. hodnoty 5 000 Kč.:</p>
												<?php foreach ( $discounts as $discount ) : ?>
												<div class="checkbox">
													<label>
														<input type="checkbox" name="slevy[]" value="<?php echo esc_attr( $discount['title'] ?? '' ); ?>">
														<b><?php echo esc_html( $discount['title'] ?? '' ); ?></b> - <?php echo wp_kses_post( $discount['text'] ?? '' ); ?>
													</label>
												</div>
												<?php endforeach; ?>
												<div class="checkbox">
													<label><input type="checkbox" name="slevy[]" value="beze slevy" checked>- neuplatňuji žádnou slevu</label>
												</div>
											</div>
										</div>
										<?php endif; ?>
										<div class="col-sm-6">
											<div class="form-group">
												<label for="reservation_persons">Další osoby</label>
												<textarea class="form-control" rows="3" placeholder="V případě, že máte zájem zarezervovat více míst (např. rodinná dovolená), uveďte prosím, celá jména dalších osob, které se zájezdu zúčastní" id="reservation_persons" name="reservation_persons"></textarea>
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group">
												<label for="reservation_comments">Poznámky</label>
												<textarea class="form-control" rows="3" placeholder="Chcete-li pracovníkům CK sdělit ještě něco dalšího, máte možnost využít posledního políčka formuláře" id="reservation_comments" name="reservation_comments"></textarea>
											</div>
										</div>
									</div>
								</div>
								<div class="col-sm-4">
									<?php if ( $recaptcha_site ) : ?>
									<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $recaptcha_site ); ?>"></div>
									<?php endif; ?>
									<input type="submit" name="k26_reservation_submit" class="btn btn-info" value="Rezervovat">
								</div>
							</div>
						</form>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php if ( $result['message'] ) : ?>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		var modal = document.getElementById('trip-reservation-form');
		if (modal) { modal.classList.add('open'); }
	});
	</script>
	<?php endif;
}

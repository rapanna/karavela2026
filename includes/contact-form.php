<?php
/**
 * Kontaktní formulář „Zavolejte mi" (modal na detailu zájezdu).
 *
 * Náhrada za starý includes/contact_form.php. Změny:
 *  - reCAPTCHA klíče z nastavení (k26_setting), bez klíčů se captcha přeskočí;
 *  - cílový e-mail: k26_setting('karavela_kontakt_email');
 *  - sanitizace vstupů + nonce + honeypot.
 * Pomocné funkce k26_text_contains_link() a k26_verify_recaptcha() se sdílejí
 * z reservation-form.php (načteno dříve ve functions.php).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Zpracuje odeslaný kontaktní formulář. Vrací [ 'type' => '', 'message' => '' ].
 */
function k26_handle_contact_submit( $post_id ) {
	$result = array( 'type' => '', 'message' => '' );

	if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || ! isset( $_POST['k26_contact_submit'] ) ) {
		return $result;
	}

	// Nonce
	if ( ! isset( $_POST['k26_contact_nonce'] ) || ! wp_verify_nonce( $_POST['k26_contact_nonce'], 'k26_contact' ) ) {
		$result['type']    = 'error';
		$result['message'] = 'Platnost formuláře vypršela, zkuste jej prosím odeslat znovu.';
		return $result;
	}

	// Honeypoty: reservace_web i titul musí být prázdné
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
	foreach ( array( 'reservation_name', 'reservation_surname', 'reservation_phone', 'reservation_trip_name' ) as $f ) {
		if ( k26_text_contains_link( $_POST[ $f ] ?? '' ) ) {
			$result['type']    = 'error';
			$result['message'] = 'Formulář se nepodařilo odeslat.';
			return $result;
		}
	}

	// Sanitizace
	$name      = sanitize_text_field( $_POST['reservation_name']    ?? '' );
	$surname   = sanitize_text_field( $_POST['reservation_surname'] ?? '' );
	$phone     = sanitize_text_field( $_POST['reservation_phone']   ?? '' );
	$trip_name = sanitize_text_field( $_POST['reservation_trip_name'] ?? get_the_title( $post_id ) );
	$trip_name = html_entity_decode( $trip_name, ENT_QUOTES, 'UTF-8' );

	// Povinná pole
	if ( '' === $name || '' === $surname || '' === $phone ) {
		$result['type']    = 'error';
		$result['message'] = 'Vyplňte prosím jméno, příjmení a telefon.';
		return $result;
	}

	// Cílový e-mail
	$to = k26_setting( 'karavela_kontakt_email' );
	if ( ! is_email( $to ) ) {
		$to = get_option( 'admin_email' );
	}

	$subject = 'Kontaktní formulář – ' . $trip_name;

	$body  = '<p>Dobrý den, klient žádá o kontaktování.</p>';
	$rows  = array(
		'Jméno'         => $name,
		'Příjmení'      => $surname,
		'Telefon'       => $phone,
		'Název zájezdu' => $trip_name,
	);
	foreach ( $rows as $label => $val ) {
		$body .= esc_html( $label ) . ': ' . esc_html( $val ) . '<br/>';
	}

	$headers = array(
		'Content-type: text/html; charset=UTF-8',
		'From: Karavela <web_karavela@karavela.cz>',
		'Reply-To: Karavela <karavela@karavela.cz>',
	);

	$sent = wp_mail( $to, $subject, $body, $headers );

	if ( $sent ) {
		$result['type']    = 'success';
		$result['message'] = 'Děkujeme, brzy se vám ozveme.';
	} else {
		$result['type']    = 'error';
		$result['message'] = 'Zprávu se nepodařilo odeslat. Zkuste to prosím znovu nebo nám zavolejte.';
	}

	return $result;
}

/**
 * Vykreslí kontaktní formulář „Zavolejte mi" (modal). Volat na single-trip.php.
 */
function k26_render_contact_form( $post_id = 0 ) {
	$post_id = $post_id ?: get_the_ID();

	$result         = k26_handle_contact_submit( $post_id );
	$recaptcha_site = k26_setting( 'karavela_recaptcha_site' );
	$trip_title     = get_the_title( $post_id );
	?>
	<div class="content--trip__reservation-form content--trip__reservation-form-contakt" id="trip-contact-form">
		<div class="container">
			<div class="row">
				<div class="col-sm-12">
					<div class="content--trip__reservation-form__content">
						<button type="button" class="close">×</button>
						<h3 class="title title--h3">Zavolejte mi</h3>

						<?php if ( $result['message'] ) : ?>
							<p class="reservation-message reservation-message--<?php echo esc_attr( $result['type'] ); ?>">
								<?php echo esc_html( $result['message'] ); ?>
							</p>
						<?php endif; ?>

						<?php if ( 'success' !== $result['type'] ) : ?>
						<form method="post" role="form">
							<?php wp_nonce_field( 'k26_contact', 'k26_contact_nonce' ); ?>
							<input type="hidden" name="reservation_trip_name" id="reservation_trip_name_c" value="<?php echo esc_attr( $trip_title ); ?>">

							<div class="row">
								<!-- honeypot: titul -->
								<div class="col-sm-6 reservationform" style="display:none">
									<div class="form-group">
										<label for="reservation_titul_c">Titul</label>
										<input type="text" class="form-control" id="reservation_titul_c" name="reservation_titul" autocomplete="off" tabindex="-1">
									</div>
								</div>

								<div class="col-sm-6">
									<div class="form-group">
										<label for="reservation_name_c">Jméno</label>
										<input type="text" class="form-control" id="reservation_name_c" name="reservation_name" placeholder="Jméno" required>
									</div>
								</div>
								<div class="col-sm-6">
									<div class="form-group">
										<label for="reservation_surname_c">Příjmení</label>
										<input type="text" class="form-control" id="reservation_surname_c" name="reservation_surname" placeholder="Příjmení" required>
									</div>
								</div>
								<div class="col-sm-12">
									<div class="form-group">
										<label for="reservation_phone_c">Telefon</label>
										<input type="text" class="form-control" id="reservation_phone_c" name="reservation_phone" placeholder="Telefon" required>
									</div>
									<!-- honeypot: reservace_web -->
									<div class="form-group nolimit" style="display:none">
										<label for="reservace_web_c">Úprava</label>
										<input type="text" class="form-control" id="reservace_web_c" name="reservace_web" autocomplete="off" tabindex="-1">
									</div>
								</div>
							</div>

							<?php if ( $recaptcha_site ) : ?>
							<div class="g-recaptcha" data-sitekey="<?php echo esc_attr( $recaptcha_site ); ?>"></div>
							<?php endif; ?>
							<input type="submit" name="k26_contact_submit" class="btn btn-info" value="Odeslat">
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
		var modal = document.getElementById('trip-contact-form');
		if (modal) { modal.classList.add('open'); }
	});
	</script>
	<?php endif;
}

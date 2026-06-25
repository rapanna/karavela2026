<?php
/**
 * Kontaktní formulář „Zavolejte mi" – INLINE sekce na detailu zájezdu.
 *
 * Náhrada za starý includes/contact_form.php. Změny:
 *  - reCAPTCHA klíče z nastavení (k26_setting), bez klíčů se captcha přeskočí;
 *  - cílový e-mail: k26_setting('karavela_kontakt_email');
 *  - sanitizace vstupů + nonce + honeypot.
 *  - NENÍ modal: formulář je vždy viditelný přímo na stránce.
 *  - Pole: jméno, telefon, e-mail. Povinné jméno + alespoň jeden z dvojice
 *    telefon/e-mail.
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

	// Rate-limit: max 5 odeslání / 10 min z jedné IP (helper z reservation-form.php)
	if ( k26_form_rate_limited( 'contact' ) ) {
		$result['type']    = 'error';
		$result['message'] = 'Příliš mnoho pokusů. Zkuste to prosím za chvíli znovu nebo nám zavolejte.';
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
	foreach ( array( 'reservation_name', 'reservation_phone', 'reservation_email', 'reservation_trip_name' ) as $f ) {
		if ( k26_text_contains_link( $_POST[ $f ] ?? '' ) ) {
			$result['type']    = 'error';
			$result['message'] = 'Formulář se nepodařilo odeslat.';
			return $result;
		}
	}

	// Sanitizace
	$name      = sanitize_text_field( $_POST['reservation_name']  ?? '' );
	$phone     = sanitize_text_field( $_POST['reservation_phone'] ?? '' );
	$email     = sanitize_email( $_POST['reservation_email']      ?? '' );
	$trip_name = sanitize_text_field( $_POST['reservation_trip_name'] ?? get_the_title( $post_id ) );
	$trip_name = html_entity_decode( $trip_name, ENT_QUOTES, 'UTF-8' );

	// Povinné jméno
	if ( '' === $name ) {
		$result['type']    = 'error';
		$result['message'] = 'Vyplňte prosím Vaše jméno.';
		return $result;
	}

	// Alespoň jeden z dvojice telefon / e-mail
	if ( '' === $phone && '' === $email ) {
		$result['type']    = 'error';
		$result['message'] = 'Zanechte nám prosím alespoň jeden kontakt – telefon nebo e-mail.';
		return $result;
	}

	// Pokud byl e-mail vyplněn, musí být platný
	if ( '' !== $email && ! is_email( $email ) ) {
		$result['type']    = 'error';
		$result['message'] = 'Zadaná e-mailová adresa není platná.';
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
		'Telefon'       => $phone !== '' ? $phone : '–',
		'E-mail'        => $email !== '' ? $email : '–',
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
		$result['message'] = 'Děkujeme, ozveme se Vám zpátky v následujících 2 pracovních dnech.';
	} else {
		$result['type']    = 'error';
		$result['message'] = 'Zprávu se nepodařilo odeslat. Zkuste to prosím znovu nebo nám zavolejte.';
	}

	return $result;
}

/**
 * Vykreslí kontaktní formulář „Zavolejte mi" jako inline sekci. Volat na single-trip.php.
 */
function k26_render_contact_form( $post_id = 0 ) {
	$post_id = $post_id ?: get_the_ID();

	$result         = k26_handle_contact_submit( $post_id );
	$recaptcha_site = k26_setting( 'karavela_recaptcha_site' );
	$trip_title     = get_the_title( $post_id );
	?>
	<section class="k26-contact-inline" id="trip-contact-form">
		<div class="k26-contact-inline__box">
			<h3 class="title title--h3">Potřebujete poradit, na něco se zeptat? Kontaktujte nás</h3>
			<p class="k26-contact-inline__intro">Zanechte nám Vaše kontaktní údaje (stačí jeden z nich) a my se Vám ozveme zpátky v následujících 2 pracovních dnech.</p>

			<?php if ( $result['message'] ) : ?>
				<p class="reservation-message reservation-message--<?php echo esc_attr( $result['type'] ); ?>">
					<?php echo esc_html( $result['message'] ); ?>
				</p>
			<?php endif; ?>

			<?php if ( 'success' !== $result['type'] ) : ?>
			<form method="post" role="form" class="k26-contact-form">
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

					<div class="col-sm-12">
						<div class="form-group">
							<label for="reservation_name_c">Jméno</label>
							<input type="text" class="form-control" id="reservation_name_c" name="reservation_name" placeholder="Jméno" required>
						</div>
					</div>
					<div class="col-sm-6">
						<div class="form-group">
							<label for="reservation_phone_c">Telefon</label>
							<input type="tel" class="form-control k26-contact-oneof" id="reservation_phone_c" name="reservation_phone" placeholder="Telefon">
						</div>
					</div>
					<div class="col-sm-6">
						<div class="form-group">
							<label for="reservation_email_c">E-mail</label>
							<input type="email" class="form-control k26-contact-oneof" id="reservation_email_c" name="reservation_email" placeholder="E-mail">
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
	</section>
	<?php if ( $result['message'] ) : ?>
	<script>
	document.addEventListener('DOMContentLoaded', function () {
		var el = document.getElementById('trip-contact-form');
		if ( el ) { el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
	});
	</script>
	<?php endif;
}

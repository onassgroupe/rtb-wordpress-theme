<?php
/**
 * Page Contact — redesign éditorial.
 */
defined( 'ABSPATH' ) || exit;

get_header();

$phone   = onass_mod( 'rtb_phone', '(+226) 25 31 83 53 / 63' );
$email   = onass_mod( 'rtb_email', 'info@rtb.bf' );
$address = onass_mod( 'rtb_address', '01 BP 2530 Ouagadougou 01, Burkina Faso' );

$contacts = [
	[ 'svg' => '<path d="M3 5.5C3 14 10 21 18.5 21a2 2 0 0 0 2-1.7l.3-2a1.5 1.5 0 0 0-1-1.6l-2.7-1a1.5 1.5 0 0 0-1.7.5l-.7.9a12 12 0 0 1-4.7-4.7l.9-.7a1.5 1.5 0 0 0 .5-1.7l-1-2.7a1.5 1.5 0 0 0-1.6-1l-2 .3A2 2 0 0 0 3 5.5z"/>', 'label' => rtb_t( 'Téléphone' ), 'value' => $phone, 'link' => 'tel:' . preg_replace( '/\s+/', '', $phone ), 'live' => 'rtb_phone', 'live_link' => 'data-live-tel="rtb_phone"' ],
	[ 'svg' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>', 'label' => rtb_t( 'E-mail' ), 'value' => $email, 'link' => 'mailto:' . $email, 'live' => 'rtb_email', 'live_link' => 'data-live-mail="rtb_email"' ],
	[ 'svg' => '<path d="M12 21s-7-5.5-7-11a7 7 0 0 1 14 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>', 'label' => rtb_t( 'Adresse' ), 'value' => $address, 'link' => '', 'live' => 'rtb_address', 'live_link' => '' ],
];
?>
<div class="rtb-page-head">
	<div class="rtb-container">
		<div class="rtb-eyebrow rtb-eyebrow--green"><i></i><span><?php echo esc_html( rtb_t( 'NOUS CONTACTER' ) ); ?></span></div>
		<h1><?php echo esc_html( rtb_t( 'Contactez la RTB' ) ); ?></h1>
		<p class="rtb-page-lead"><?php echo esc_html( rtb_t( 'Une question, une information à transmettre à la rédaction, un partenariat ? Nos équipes vous répondent.' ) ); ?></p>
	</div>
</div>

<section class="rtb-container rtb-section" data-cs="rtb_contact">
	<div class="rtb-contact-cards">
		<?php foreach ( $contacts as $c ) : ?>
			<div class="rtb-contact-card">
				<span class="rtb-contact-ico"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php echo $c['svg']; // phpcs:ignore ?></svg></span>
				<span class="rtb-contact-label"><?php echo esc_html( $c['label'] ); ?></span>
				<?php if ( $c['link'] ) : ?>
					<a class="rtb-contact-value" href="<?php echo esc_attr( $c['link'] ); ?>" <?php echo $c['live_link']; // phpcs:ignore ?>><span data-live="<?php echo esc_attr( $c['live'] ); ?>"><?php echo esc_html( $c['value'] ); ?></span></a>
				<?php else : ?>
					<span class="rtb-contact-value" data-live="<?php echo esc_attr( $c['live'] ); ?>"><?php echo esc_html( $c['value'] ); ?></span>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="rtb-contact-grid">
		<!-- Formulaire -->
		<div class="rtb-contact-form-wrap">
			<h2><?php echo esc_html( rtb_t( 'Écrire à la rédaction' ) ); ?></h2>
			<p class="rtb-contact-sub"><?php echo esc_html( rtb_t( "Les champs marqués d'un" ) ); ?> <span style="color:var(--rtb-red)">*</span> <?php echo esc_html( rtb_t( 'sont obligatoires.' ) ); ?></p>
			<form class="rtb-contact-form" id="rtb-contact-form">
				<div class="rtb-field-row">
					<label class="rtb-field">
						<span><?php echo esc_html( rtb_t( 'Nom complet *' ) ); ?></span>
						<input type="text" name="nom" required>
					</label>
					<label class="rtb-field">
						<span><?php echo esc_html( rtb_t( 'E-mail *' ) ); ?></span>
						<input type="email" name="email" required>
					</label>
				</div>
				<label class="rtb-field">
					<span><?php echo esc_html( rtb_t( 'Sujet' ) ); ?></span>
					<input type="text" name="sujet" placeholder="<?php echo esc_attr( rtb_t( 'Objet de votre message' ) ); ?>">
				</label>
				<label class="rtb-field">
					<span><?php echo esc_html( rtb_t( 'Message *' ) ); ?></span>
					<textarea name="message" rows="6" required></textarea>
				</label>
				<div class="rtb-form-actions">
					<button type="submit" class="rtb-btn-submit"><?php echo esc_html( rtb_t( 'Envoyer le message' ) ); ?></button>
					<span class="rtb-form-status" id="rtb-form-status" role="status"></span>
				</div>
			</form>
		</div>

		<!-- Coordonnées & carte -->
		<aside class="rtb-contact-aside">
			<div class="rtb-contact-map">
				<iframe
					title="<?php echo esc_attr( rtb_t( 'Localisation RTB Ouagadougou' ) ); ?>"
					src="https://www.google.com/maps?q=Radiodiffusion+Television+du+Burkina+Ouagadougou&output=embed"
					loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
			</div>
			<div class="rtb-contact-hours">
				<h3><?php echo esc_html( rtb_t( "Horaires d'accueil" ) ); ?></h3>
				<div class="rtb-hours-row"><span><?php echo esc_html( rtb_t( 'Lundi – Vendredi' ) ); ?></span><b><?php echo esc_html( rtb_t( '07h30 – 17h30' ) ); ?></b></div>
				<div class="rtb-hours-row"><span><?php echo esc_html( rtb_t( 'Samedi' ) ); ?></span><b><?php echo esc_html( rtb_t( '08h00 – 12h00' ) ); ?></b></div>
				<div class="rtb-hours-row"><span><?php echo esc_html( rtb_t( 'Dimanche' ) ); ?></span><b><?php echo esc_html( rtb_t( 'Fermé · rédaction en direct' ) ); ?></b></div>
				<div class="rtb-contact-social">
					<?php
					$socials = [
						'Facebook'  => onass_mod( 'rtb_facebook', '#' ),
						'X'         => onass_mod( 'rtb_x', '#' ),
						'Instagram' => onass_mod( 'rtb_instagram', '#' ),
						'YouTube'   => onass_mod( 'rtb_youtube', '#' ),
					];
					foreach ( $socials as $name => $url ) :
						?>
						<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $name ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
		</aside>
	</div>
</section>

<?php
get_footer();

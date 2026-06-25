<?php

namespace RTB\I18n;

defined( 'ABSPATH' ) || exit;

/**
 * Éditeur de traductions dans l'admin : RTB peut compléter/corriger chaque
 * langue sans toucher au code. Les saisies (option `rtb_i18n_overrides`)
 * surchargent les traductions par défaut livrées dans Translator::base().
 */
final class Admin {

	public const OPTION     = 'rtb_i18n_overrides';
	private const CAP        = 'manage_options';
	private const MENU_SLUG  = 'rtb-i18n';
	private const SAVE_ACTION = 'rtb_i18n_save';

	/** Langues éditables (le français est la source). */
	private const LANGS = [
		'en'  => 'English',
		'mos' => 'Mooré',
		'dyu' => 'Dioula',
		'ff'  => 'Fulfuldé',
		'gux' => 'Gulmancéma',
	];

	public function register(): void {
		add_action( 'admin_menu', [ $this, 'menu' ] );
		add_action( 'admin_post_' . self::SAVE_ACTION, [ $this, 'save' ] );
	}

	public function menu(): void {
		add_menu_page(
			'Traductions RTB',
			'Traductions RTB',
			self::CAP,
			self::MENU_SLUG,
			[ $this, 'render' ],
			'dashicons-translation',
			59
		);
	}

	private function currentLangParam(): string {
		$lang = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : 'en';
		return isset( self::LANGS[ $lang ] ) ? $lang : 'en';
	}

	public function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$lang    = $this->currentLangParam();
		$sources = Translator::sources();
		$map     = Translator::strings();
		$saved   = isset( $_GET['updated'] );

		echo '<div class="wrap"><h1>Traductions RTB</h1>';
		echo '<p>Le <strong>français</strong> est la langue source. Modifiez les traductions ci-dessous ; elles priment sur les valeurs par défaut du thème. Champ vide = on retombe sur le français.</p>';

		if ( $saved ) {
			echo '<div class="notice notice-success is-dismissible"><p>Traductions enregistrées.</p></div>';
		}

		// Onglets de langue.
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( self::LANGS as $code => $name ) {
			$url    = add_query_arg( [ 'page' => self::MENU_SLUG, 'lang' => $code ], admin_url( 'admin.php' ) );
			$active = $code === $lang ? ' nav-tab-active' : '';
			echo '<a class="nav-tab' . esc_attr( $active ) . '" href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
		}
		echo '</h2>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="' . esc_attr( self::SAVE_ACTION ) . '">';
		echo '<input type="hidden" name="lang" value="' . esc_attr( $lang ) . '">';
		wp_nonce_field( self::SAVE_ACTION );

		echo '<table class="widefat striped" style="margin-top:12px"><thead><tr>';
		echo '<th style="width:50%">Source (français)</th><th>' . esc_html( self::LANGS[ $lang ] ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $sources as $src ) {
			$val   = $map[ $lang ][ $src ] ?? '';
			$field = 't[' . base64_encode( $src ) . ']';
			$long  = mb_strlen( $src ) > 60;
			echo '<tr>';
			echo '<td><label for="' . esc_attr( md5( $src ) ) . '">' . esc_html( $src ) . '</label></td>';
			echo '<td>';
			if ( $long ) {
				echo '<textarea id="' . esc_attr( md5( $src ) ) . '" name="' . esc_attr( $field ) . '" rows="2" style="width:100%">' . esc_textarea( $val ) . '</textarea>';
			} else {
				echo '<input id="' . esc_attr( md5( $src ) ) . '" type="text" name="' . esc_attr( $field ) . '" value="' . esc_attr( $val ) . '" style="width:100%">';
			}
			echo '</td></tr>';
		}

		echo '</tbody></table>';
		submit_button( 'Enregistrer ' . self::LANGS[ $lang ] );
		echo '</form></div>';
	}

	public function save(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'Accès refusé.' );
		}
		check_admin_referer( self::SAVE_ACTION );

		$lang = isset( $_POST['lang'] ) ? sanitize_key( wp_unslash( $_POST['lang'] ) ) : '';
		if ( ! isset( self::LANGS[ $lang ] ) ) {
			wp_die( 'Langue invalide.' );
		}

		$valid_sources = array_flip( Translator::sources() );
		$posted        = isset( $_POST['t'] ) && is_array( $_POST['t'] ) ? wp_unslash( $_POST['t'] ) : [];
		$clean         = [];
		foreach ( $posted as $b64 => $value ) {
			$src = base64_decode( (string) $b64, true );
			if ( false === $src || ! isset( $valid_sources[ $src ] ) ) {
				continue; // n'accepter que des sources connues
			}
			$value = sanitize_textarea_field( (string) $value );
			if ( '' !== $value ) {
				$clean[ $src ] = $value;
			}
		}

		$over          = get_option( self::OPTION, [] );
		$over          = is_array( $over ) ? $over : [];
		$over[ $lang ] = $clean;
		update_option( self::OPTION, $over, false );

		if ( function_exists( 'rtb_cache_clear' ) ) {
			rtb_cache_clear();
		}

		wp_safe_redirect( add_query_arg(
			[ 'page' => self::MENU_SLUG, 'lang' => $lang, 'updated' => 1 ],
			admin_url( 'admin.php' )
		) );
		exit;
	}
}

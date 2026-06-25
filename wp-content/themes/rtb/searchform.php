<?php defined( 'ABSPATH' ) || exit; ?>
<form role="search" method="get" class="rtb-searchform" action="<?php echo esc_url( rtb_lurl( '/' ) ); ?>">
	<input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php echo esc_attr( rtb_t( 'Rechercher sur RTB…' ) ); ?>" aria-label="<?php echo esc_attr( rtb_t( 'Rechercher' ) ); ?>" data-rtb-instant>
	<button type="submit"><?php echo esc_html( rtb_t( 'Rechercher' ) ); ?></button>
</form>

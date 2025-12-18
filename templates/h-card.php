<?php
/**
 * H-Card template.
 *
 * This template is included from HCard_Author_Widget::widget() with the following variables:
 *
 * @var string   $avatar The avatar HTML.
 * @var string   $url    The author URL.
 * @var string   $name   The author name.
 * @var string   $email  The author email.
 * @var array    $args   Widget arguments.
 * @var \WP_User $user   The user object.
 *
 * @package IndieWeb
 */

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
?>
<div class="hcard-display h-card vcard u-author">
	<div class="hcard-header">
		<?php if ( ! $avatar ) { ?>
			<a class="u-url url fn u-uid" href="<?php echo esc_url( $url ); ?>">
				<p class="hcard-name p-name n"><?php echo esc_html( $name ); ?></p>
			</a>
		<?php } else { ?>
			<a class="u-url url fn u-uid" href="<?php echo esc_url( $url ); ?>"><?php echo wp_kses_post( $avatar ); ?></a>
				<p class="hcard-name p-name n"><?php echo esc_html( $name ); ?></p>
			<?php
		}
		if ( $args['email'] ) {
			?>
			<p>
				<a class="u-email" href="mailto:<?php echo esc_attr( $email ); ?>" <?php echo is_front_page() ? 'rel="me"' : ''; ?>><?php echo esc_html( $email ); ?></a>
			</p>
		<?php } ?>
	</div> <!-- end hcard-header -->
	<div class="hcard-body">
		<ul class="hcard-properties">
			<?php if ( $args['location'] && ( $user->has_prop( 'locality' ) || $user->has_prop( 'region' ) || $user->has_prop( 'country-name' ) ) ) { ?>
				<li class="h-adr adr">
					<?php if ( $user->has_prop( 'locality' ) ) { ?>
						<span class="p-locality locality"><?php echo esc_html( $user->get( 'locality' ) ); ?></span>
						<?php
					}
					if ( $user->has_prop( 'region' ) ) {
						?>
						<span class="p-region region"><?php echo esc_html( $user->get( 'region' ) ); ?></span>
						<?php
					}
					if ( $user->has_prop( 'country-name' ) ) {
						?>
						<span class="p-country-name country-name"><?php echo esc_html( $user->get( 'country-name' ) ); ?></span>
					<?php } ?>
				</li>
				<?php
			}
			if ( $user->has_prop( 'tel' ) && $user->get( 'tel' ) ) {
				?>
				<li>
					<a class="p-tel tel" href="tel:<?php echo esc_attr( $user->get( 'tel' ) ); ?>"><?php echo esc_html( $user->get( 'tel' ) ); ?></a>
				</li>
			<?php } ?>
		</ul> <!-- end hcard-properties -->
		<?php if ( $args['me'] ) { ?>
			<?php self::rel_me_list( $user->ID, is_front_page() ); ?>
			<?php
		}
		if ( $args['notes'] && $user->has_prop( 'description' ) ) {
			?>
			<p class="p-note note"><?php echo wp_kses_post( $user->get( 'description' ) ); ?></p>
		<?php } ?>
	</div> <!-- end hcard-body -->
</div>
<!-- end hcard-display -->

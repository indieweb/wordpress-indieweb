<div class="hcard-display h-card vcard p-author">
	<div class="hcard-header">
		<?php if ( ! $avatar ) { ?>
			<a class="u-url url fn u-uid" href="<?php echo esc_url( $url ); ?>" rel="author">
				<p class="hcard-name p-name n"><?php echo $name; ?></p>
			</a>
		<?php } else { ?>
			<a class="u-url url fn u-uid" href="<?php echo esc_url( $url ); ?>" rel="author"><?php echo $avatar; ?></a>
				<p class="hcard-name p-name n"><?php echo $name; ?></p>
		<?php }
		if ( $args['email'] ) { ?>
			<p>
				<a class="u-email" rel="me" href="mailto:<?php echo $email; ?>"><?php echo $email; ?></a>
			</p>
		<?php } ?>
	</div> <!-- end hcard-header -->
	<div class="hcard-body">
		<ul class="hcard-properties">
			<?php if ( $args['location'] && ( $user->has_prop( 'locality' ) || $user->has_prop( 'region' ) || $user->has_prop( 'country-name' ) ) ) { ?>
				<li class="h-adr adr">
					<?php if ( $user->has_prop( 'locality' ) ) { ?>
						<span class="p-locality locality"><?php echo $user->get( 'locality' ); ?></span>
					<?php } 
					if ( $user->has_prop( 'region' ) ) { ?>
						<span class="p-region region"><?php echo $user->get( 'region' ); ?></span>
					<?php }
					if ( $user->has_prop( 'country-name' ) ) { ?>
						<span class="p-country-name country-name"><?php echo $user->get( 'country-name' ); ?></span>
					<?php } ?>
				</li>
			<?php }
			if ( $user->has_prop( 'tel' ) && $user->get( 'tel' ) ) { ?>
				<li>
					<a class="p-tel tel" href="tel:<?php echo $user->get( 'tel' ); ?>"><?php echo $user->get( 'tel' ); ?></a>
				</li>
			<?php } ?> 
		</ul> <!-- end hcard-properties -->
		<?php if ( $args['me'] ) { ?>
			<?php self::rel_me_list( $user->ID, is_front_page() ); ?>
		<?php }
		if ( $args['notes']  && $user->has_prop( 'description' ) ) { ?>
			<p class="p-note note"><?php echo $user->get( 'description' ); ?></p>
		<?php } ?>
	</div> <!-- end hcard-body -->
</div><!-- end hcard-display -->
<?php

<?php
/**
 * Post Archive Page Settings Field.
 *
 * @package CPTArchivePages
 */

?>
<div class="cptap-setting-fields">
	<label for="<?php echo esc_attr( $args['label_for'] ); ?>">
		<?php
		wp_dropdown_pages(
			array(
				'name'              => esc_attr( $args['label_for'] ),
				'show_option_none'  => esc_html__( '&mdash; Select &mdash;', 'cpt-archive-pages' ),
				'option_none_value' => '0',
				'selected'          => esc_attr( get_option( $args['label_for'] ) ),
			)
		);
		?>
	</label>

	<label>
		<input type="checkbox" name="<?php echo esc_attr( $args['singular'] ); ?>[]" value="<?php echo esc_attr( $args['post_type'] ); ?>" <?php checked( in_array( $args['post_type'], $args['selected_singular'], true ), true ); ?>>
		<?php esc_html_e( 'Use for Singular URLs', 'cpt-archive-pages' ); ?>
	</label>

	<label>
		<input type="checkbox" name="<?php echo esc_attr( $args['use_loop'] ); ?>[]" value="<?php echo esc_attr( $args['post_type'] ); ?>" <?php checked( in_array( $args['post_type'], $args['selected_loop'], true ), true ); ?>>
		<?php esc_html_e( 'Use Loop', 'cpt-archive-pages' ); ?>
	</label>
</div>

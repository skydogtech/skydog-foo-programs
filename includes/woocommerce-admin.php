<?php

/**
 * Use block editor for Woo products.
 */
add_filter( 'use_block_editor_for_post_type', function( $can_edit, $post_type ) {
	if ( 'product' === $post_type ) {
		$can_edit = true;
	}

	return $can_edit;

}, 10, 2 );

/**
 * Allow Woo Product Categories to work in block editor.
 */
add_filter( 'register_taxonomy_args', function( $args, $taxonomy, $object_type ) {
	if ( in_array( $taxonomy, [ 'product_cat', 'product_tag' ] ) ) {
		$args['show_in_rest'] = true;
	}

	return $args;

}, 10, 3 );

/**
 * Remove the short description meta box.
 *
 * @return void
 */
// add_action( 'add_meta_boxes',function() {
// 	remove_meta_box( 'postexcerpt', 'product', 'normal' );
// }, 50 );

/**
 * Product visibility.
 */

/**
 * Allows product visibility metabox to work with block editor.
 *
 * @link https://dev.to/kalimahapps/enable-gutenberg-editor-in-woocommerce-466m
 *
 * @return void
 */
 add_action( 'add_meta_boxes', function() {
	$current_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

	if ( $current_screen && method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
		add_meta_box( 'catalog-visibility', __( 'Catalog visibility', 'cambridgeinsight' ), 'skydog_product_data_visibility', 'product', 'side' );
	}
});

/**
 * Saves product visibility settings.
 */
function skydog_product_data_visibility( $post ) {
	$post_id            = $post->ID;
	$product_object     = $post_id ? wc_get_product( $post_id ) : new WC_Product();
	$current_visibility = $product_object->get_catalog_visibility();
	$current_featured   = wc_bool_to_string( $product_object->get_featured() );
	$visibility_options = wc_get_product_visibility_options();
	?>
	<div class="misc-pub-section" id="catalog-visibility">
		<?php esc_html_e( 'Catalog visibility:', 'woocommerce' ); ?>
		<strong id="catalog-visibility-display">
			<?php
			echo isset( $visibility_options[ $current_visibility ] ) ? esc_html( $visibility_options[ $current_visibility ] ) : esc_html( $current_visibility );

			if ( 'yes' === $current_featured ) {
				echo ', ' . esc_html__( 'Featured', 'woocommerce' );
			}
			?>
		</strong>

		<a href="#catalog-visibility" class="edit-catalog-visibility hide-if-no-js"><?php esc_html_e( 'Edit', 'woocommerce' ); ?></a>

		<div id="catalog-visibility-select" class="hide-if-js">
			<input type="hidden" name="current_visibility" id="current_visibility" value="<?php echo esc_attr( $current_visibility ); ?>" />
			<input type="hidden" name="current_featured" id="current_featured" value="<?php echo esc_attr( $current_featured ); ?>" />

			<?php
			echo '<p>' . esc_html__( 'This setting determines which shop pages products will be listed on.', 'woocommerce' ) . '</p>';

			foreach ( $visibility_options as $name => $label ) {
				echo '<input type="radio" name="_visibility" id="_visibility_' . esc_attr( $name ) . '" value="' . esc_attr( $name ) . '" ' . checked( $current_visibility, $name, false ) . ' data-label="' . esc_attr( $label ) . '" /> <label for="_visibility_' . esc_attr( $name ) . '" class="selectit">' . esc_html( $label ) . '</label><br />';
			}

			echo '<br /><input type="checkbox" name="_featured" id="_featured" ' . checked( $current_featured, 'yes', false ) . ' /> <label for="_featured">' . esc_html__( 'This is a featured product', 'woocommerce' ) . '</label><br />';
			?>
			<p>
				<a href="#catalog-visibility" class="save-post-visibility hide-if-no-js button"><?php esc_html_e( 'OK', 'woocommerce' ); ?></a>
				<a href="#catalog-visibility" class="cancel-post-visibility hide-if-no-js"><?php esc_html_e( 'Cancel', 'woocommerce' ); ?></a>
			</p>
		</div>
	</div>
	<?php
}
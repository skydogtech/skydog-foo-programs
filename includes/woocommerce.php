<?php


/**
 * Content Types
 *******************************/

/**
 * Changes taxonomy names and labels.
 *
 * @param array $args The post type args.
 *
 * @return array
 */
add_filter( 'woocommerce_register_post_type_product', function( $args ) {
	foreach ( $args['labels'] as $key => $label ) {
		$label = str_replace( 'Product', __( 'Program', 'cambridgeinsight' ), $label );
		$label = str_replace( 'product', __( 'program', 'cambridgeinsight' ), $label );

		$args['labels'][ $key ] = $label;
	}

	return $args;

}, 99 );

/**
 * Changes taxonomy names and labels.
 *
 * @param array $args The taxonomy args.
 *
 * @return array
 */
add_filter( 'woocommerce_taxonomy_args_product_cat', function( $args ) {
	$args['rewrite']['slug'] = 'categories';
	$args['hierarchical']    = true;
	$args['label']           = str_replace( 'Product', __( 'Program', 'cambridgeinsight' ), $args['label'] );

	foreach ( $args['labels'] as $key => $label ) {
		$label = str_replace( 'Product', __( 'Program', 'cambridgeinsight' ), $label );
		$label = str_replace( 'product', __( 'program', 'cambridgeinsight' ), $label );

		$args['labels'][ $key ] = $label;
	}

	return $args;

}, 999 );

/**
 * Changes taxonomy names and labels.
 *
 * @param array $args The taxonomy args.
 *
 * @return array
 */
add_filter( 'woocommerce_taxonomy_args_product_tag', function( $args ) {
	$args['rewrite']['slug'] = 'types';
	$args['hierarchical']    = true;
	$args['label']           = str_replace( 'Product', __( 'Program', 'cambridgeinsight' ), $args['label'] );

	foreach ( $args['labels'] as $key => $label ) {
		$label = str_replace( 'Product', __( 'Program', 'cambridgeinsight' ), $label );
		$label = str_replace( 'product', __( 'program', 'cambridgeinsight' ), $label );
		$label = str_replace( 'Tag', __( 'Type', 'cambridgeinsight' ), $label );
		$label = str_replace( 'tag', __( 'type', 'cambridgeinsight' ), $label );

		$args['labels'][ $key ] = $label;
	}

	return $args;

}, 999 );

/**
 * Product Titles.
 *******************************/

/**
 * Adds icon after title.
 *
 * @return
 */
add_action( 'woocommerce_single_product_summary', function() {
	$post_id   = get_the_ID();
	$in_person = has_term( 'in-person', 'product_tag', $post_id ) ? __( 'In person', 'skydog-foo-programs' ) : false;
	$online    = has_term( 'online', 'product_tag', $post_id ) ? __( 'Online', 'skydog-foo-programs' ) : false;
	$hybrid    = has_term( 'hybrid', 'product_tag', $post_id ) ? __( 'Hybrid', 'skydog-foo-programs' ) : false;

	if ( ! ( $in_person || $online || $hybrid ) ) {
		return;
	}

	$icon = $in_person ? 'user-circle' : 'signal-stream';

	$icon = '';

	if ( $in_person ) {
		$icon = 'user-circle';
		$text = $in_person;
	} elseif ( $online ) {
		$icon = 'signal-stream';
		$text = $online;
	} elseif ( $hybrid ) {
		$icon = 'share-alt';
		$text = $hybrid;
	}

	if ( ! $icon ) {
		return;
	}

	$icon = mai_get_icon(
		[
			'icon'         => $icon,
			'style'        => 'regular',
			'size'         => '1em',
			'display'      => 'inline-flex',
			'align'        => 'start',
			// 'margin_top'   => '0.2em',
			// 'margin_right' => '0.1em',
			// 'margin_left'  => '',
		]
	);

	printf( '<span class="skydog-program-type">%s<span class="skydog-program-type-label">%s</span></span>', $icon, $text );

}, 6 );

/**
 * Emails
 *******************************/

/**
 * Add account link to emails.
 *
 * @return void
 */
add_action( 'woocommerce_email_footer', function() {
	$url  = wc_get_page_permalink( 'myaccount' );
	$text = __( 'Log in now to view details of your account.', 'cambridgeinsight' );

	if ( ! $url ) {
		return;
	}

	printf( '<p style="text-align:center;"><a href="%s"></a></center></p>', $url );
});

/**
 * Translate
 *******************************/

/**
 * Change text strings.
 *
 * @return string
 */
add_filter( 'gettext', function( $translated_text, $text, $domain ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return $translated_text;
	}

	if ( 'woocommerce' !== $domain ) {
		return $translated_text;
	}

	switch ( $translated_text ) {
		case 'Product Data' :
			$translated_text = __( 'Program Data', 'cambridgeinsight' );
		break;
		case 'You cannot add that amount of &quot;%s&quot; to the cart because there is not enough stock (%s remaining).' :
			$translated_text = __( 'Not enough seats available for &quot;%s&quot;. Only %s seats remaining).', 'cambridgeinsight' );
		break;
	}

	return $translated_text;

}, 20, 3 );

/**
 * Change In Stock / Out of Stock Text.
 *
 * @return array
 */
add_filter( 'woocommerce_get_availability', function( $availability, $_product ) {
	// Bail if not managing stock.
	if ( ! $_product->managing_stock() ) {
		return $availability;
	}

	// Change In Stock Text.
	if ( $_product->is_in_stock() ) {
		$availability['availability'] = __( 'Available!', 'cambridgeinsight' );
	}
	// Change Out of Stock Text.
	else {
		$availability['availability'] = __( 'This program is no longer available.', 'cambridgeinsight' );
	}

	return $availability;

}, 10, 2 );
<?php

/**
 * Wrapper for `[fooevents_events_list]` shortcode
 * that forces hiding expired events.
 *
 * Adds exclude_cat param for excluding specific product_cat terms.
 *
 * @return string
 */
add_shortcode( 'skydog_events_list', function( $atts ) {
	// Convert "exclude_cat" attribute to "include_cat".
	if ( isset( $atts['exclude_cat'] ) && $atts['exclude_cat'] ) {
		$all = get_terms(
			[

				'taxonomy'	 => 'product_cat',
				'fields'     => 'slugs',
				'hide_empty' => false,
			]
		);

		$cats    = array_map( 'trim', explode( ',', $atts['exclude_cat'] ) );
		$include = array_diff( $all, $cats );

		unset( $atts['exclude_cat'] );

		$atts['include_cat'] = implode( ',', $include );
	}

	// Build shortcode.
	$shortcode = '[fooevents_events_list';
		foreach ( $atts as $key => $value ) {
			$shortcode .= sprintf( ' %s="%s"', $key, $value );
		}
	$shortcode .= ']';

	// Get events, forcing expired events to be hidden/removed.
	$callback =  function( $value ) {
		return 'hide';
	};
	add_filter( 'option_globalWooCommerceEventsExpireOption', $callback, 99 );
	$html = do_shortcode( trim( $shortcode ) );
	remove_filter( 'option_globalWooCommerceEventsExpireOption', $callback, 99 );

	return $html;
});


/**
 * Gets program meta from Sugar Calendar.
 * Left for reference. Couldn't get this fully working with feeds.
 *
 * @return string
 */
add_shortcode( 'skydog_program_details', function( $atts ) {
	return skydog_get_program_details( $atts );
});

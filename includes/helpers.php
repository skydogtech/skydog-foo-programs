<?php

/**
 * Checks if viewing a program archive.
 *
 * @return bool
 */
function skydog_is_program_archive() {
	return is_post_type_archive( 'product' ) || is_tax( 'product_cat' ) || is_tax( 'product_tag' ) || is_tax( 'teacher' );
}

/**
 * If program is expired.
 * Originally taken from `_is_purchaseable` filters in Foo Events.
 *
 * @param int $post_id
 *
 * @return bool
 */
function skydog_is_program_expired( $post_id = 0 ) {
	$post_id = $post_id ?: get_the_ID();

	static $cache = [];

	if ( isset( $cache[ $post_id ] ) ) {
		return $cache[ $post_id ];
	}

	$expired = false;
	$product = function_exists( 'wc_get_product' ) ? wc_get_product( $post_id ) : 0;

	if ( $product ) {
		$timestamp                = get_post_meta( $product->get_id(), 'WooCommerceEventsExpireTimestamp', true );
		$woocommerce_events_event = get_post_meta( $product->get_id(), 'WooCommerceEventsEvent', true );
		$today                    = current_time( 'timestamp' );

		if ( $product->is_type( 'variation' ) ) {
			$woocommerce_events_event = get_post_meta( $product->get_parent_id(), 'WooCommerceEventsEvent', true );
			$timestamp                = get_post_meta( $product->get_parent_id(), 'WooCommerceEventsExpireTimestamp', true );
		}

		if ( ! empty( $timestamp ) && 'Event' === $woocommerce_events_event && $today > $timestamp ) {
			$expired = true;
		}
	}

	$cache[ $post_id ] = $expired;

	return $cache[ $post_id ];
}

/**
 * Gets program details.
 *
 * @param array $atts The details to return.
 *
 * @return string
 */
function skydog_get_program_details( $atts = [] ) {
	// Atts.
	$atts = shortcode_atts(
		[
			'categories' => true,
			'teachers'   => true,
			'types'      => true,
			'times'      => true,
			'start'      => true,
			'end'        => true,
			'sessions'   => true,
		],
		$atts,
		'program_details'
	);

	// Sanitize.
	$atts = array_map( 'mai_sanitize_bool', $atts );

	// Program Type:	Practice Group
	// Teacher:	Narayan Helen Liebenson
	// Time:	6:45 pm - 8:30 pm
	// Start Date:	Tuesday - February 14, 2023
	// End Date:	Tuesday - April 18, 2023
	// Sessions:	9


	$html = '';

	/**
	 * Program Details.
	 ********************/
	$post_id  = get_the_ID();
	$info     = skydog_get_event_info( $post_id );
	$starts   = array_keys( $info );
	$ends     = array_values( $info );
	$start    = $info ? reset( $starts ) : 0;
	$start    = $start ? DateTime::createFromFormat( 'U', $start ) : 0;
	$end      = $info ? reset( $ends ) : 0;
	$end      = $end ? DateTime::createFromFormat( 'U', $end ) : 0;
	$last     = $info ? end( $starts ) : 0;
	$last     = $last ? DateTime::createFromFormat( 'U', $last ) : 0;
	$sessions = (int) get_post_meta( $post_id, 'WooCommerceEventsNumDays', true );

	$html .= '<table class="skydog-program-details">';

		if ( $atts['categories'] ) {
			$categories = skydog_get_the_term_list( 'product_cat' );

			if ( $categories ) {
				$html .= sprintf( '<tr><td>%s</td><td>%s</td></tr>', __( 'Program Type', 'cambridgeinsight' ), $categories );
			}
		}

		if ( $atts['teachers'] ) {
			$teachers   = skydog_get_the_term_list( 'teacher' );

			if ( $teachers ) {
				$html .= sprintf( '<tr><td>%s</td><td>%s</td></tr>', __( 'Teacher', 'cambridgeinsight' ), $teachers );
			}
		}

		if ( $atts['times'] && $start ) {
			$times = $start->format( 'g:i a' );

			if ( $end && ( $start->getTimestamp() !== $end->getTimestamp() ) ) {
				$times .= ' - ' . $end->format( 'g:i a' );
			}

			if ( $times ) {
				$html .= sprintf( '<tr><td>%s</td><td>%s</td></tr>', __( 'Time', 'cambridgeinsight' ), $times );
			}
		}

		if ( $atts['start'] && $start ) {
			$html .= sprintf( '<tr><td>%s</td><td>%s</td></tr>', __( 'Start Date', 'cambridgeinsight' ), $start->format( 'l - M j, o' ) );
		}

		if ( $atts['end'] && $last && ( $start->getTimestamp() !== $last->getTimestamp() ) ) {
			$html .= sprintf( '<tr><td>%s</td><td>%s</td></tr>', __( 'End Date', 'cambridgeinsight' ), $last->format( 'l - M j, o' ) );
		}

		if ( $atts['sessions'] && $sessions && $sessions > 0 ) {
			$html .= sprintf( '<tr><td>%s</td><td>%s</td></tr>', __( 'Sessions', 'cambridgeinsight' ), $sessions );
		}

	$html .= '</table>';

	return $html;
}

/**
 * Gets a term list by taxonomy.
 *
 * @param string   $taxonomy The taxonomy name.
 * @param int|null $post_id  The post ID.
 *
 * @return string
 */
function skydog_get_the_term_list( $taxonomy, $post_id = 0 ) {
	$post_id = $post_id ?: get_the_ID();
	$terms   = get_the_term_list( $post_id, $taxonomy, $before = '', $sep = ', ', $after = '' );

	if ( ! $terms || is_wp_error( $terms ) ) {
		return;
	}

	return $terms;
}

/**
 * Gets term list for List.js.
 *
 * @param string $taxonomy The registered taxonomy name.
 *
 * @return string
 */
function skydog_get_term_list( $taxonomy ) {
	$array = [];
	$terms = get_the_terms( get_the_ID(), $taxonomy );

	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			// Span with name added for sorting alphabetically.
			$array[] = sprintf( '<span data-name="%s"></span><a href="%s">%s</a>', $term->name, get_term_link( $term, $taxonomy ), $term->name );
		}
	}

	return implode( ', ', $array );
}

/**
 * Gets full date and time details.
 *
 * @return string
 */
function skydog_get_date_time_details( $post_id, $i = 0 ) {
	$html   = '';
	$object = skydog_get_date_time_object( $post_id, $i );

	if ( $object ) {
		$html = $object->format( 'M j, Y @g:i a' );
		$html = sprintf( '<span data-timestamp="%s">%s</span>', $object->getTimestamp(), $html );
	}

	return $html;
}

/**
 * Gets start time.
 *
 * @return string
 */
function skydog_get_time_details( $post_id, $i = 0 ) {
	$html   = '';
	$object = skydog_get_date_time_object( $post_id, $i );

	if ( $object ) {
		$html = $object->format( 'g:i a' );
	}

	return $html;
}

/**
 * Gets start time.
 *
 * @return string
 */
function skydog_get_start_time_details( $post_id ) {
	$html       = '';
	$timestamps = skydog_get_event_info( $post_id );
	$timestamps = $timestamps ? array_keys( $timestamps ) : [];
	$first      = $timestamps ? reset( $timestamps ) : 0;
	$object     = $first ? DateTime::createFromFormat( 'U', $first ) : 0;

	if ( $object ) {
		$html = $object->format( 'g:i a' );
	}

	return $html;
}


/**
 * Gets date time object of the event,
 * including the next occurrence of recurring/multi-day events.
 *
 * @param int $post_id The post ID.
 * @param int $i       The current index, for recurring/multi-day events.
 *
 * @return DateTime|false
 */
function skydog_get_date_time_object( $post_id, $i = 0 ) {
	static $cache = [];

	if ( isset( $cache[ $post_id ][ $i ] ) ) {
		return $cache[ $post_id ][ $i ];
	}

	$current    = current_time( 'timestamp' );
	$timestamps = array_keys( skydog_get_event_info( $post_id ) );

	// Remove past occurrences.
	foreach ( $timestamps as $index => $start ) {
		// Skip if event time is older than current time.
		if ( $start < $current ) {
			unset( $timestamps[ $index ] );
		}
	}

	// Reindex.
	$timestamps = array_values( $timestamps );

	if ( $timestamps && isset( $timestamps[ $i ] ) ) {
		$cache[ $post_id ][ $i ] = DateTime::createFromFormat( 'U', $timestamps[ $i ] );
	} else {
		$cache[ $post_id ][ $i ] = false;
	}

	return $cache[ $post_id ][ $i ];
}

/**
 * Gets all timestamps of events.
 *
 * @param int $post_id The post ID.
 *
 * @return array Key of start time and value of end time.
 */
function skydog_get_event_info( $post_id = 0 ) {
	static $cache = [];

	$post_id = $post_id ?: get_the_ID();

	if ( isset( $cache[ $post_id ] ) || ! function_exists( 'get_fooevents_event_info' ) ) {
		return $cache[ $post_id ];
	}

	$cache[ $post_id ] = [];

	$event = get_post_meta( $post_id, 'WooCommerceEventsEvent', true );
	$event = 'Event' === $event;

	if ( ! $event ) {
		return $cache[ $post_id ];
	}

	$info = get_fooevents_event_info( get_post( $post_id ) );
	$type = isset( $info['WooCommerceEventsType'] ) ? $info['WooCommerceEventsType'] : '';

	if ( $type && 'select' === $type ) {
		$dates           = isset( $info['WooCommerceEventsSelectDate'] ) ? $info['WooCommerceEventsSelectDate'] : [];
		$date_hour       = isset( $info['WooCommerceEventsSelectDateHour'] ) ? $info['WooCommerceEventsSelectDateHour'] : [];
		$date_min        = isset( $info['WooCommerceEventsSelectDateMinutes'] ) ? $info['WooCommerceEventsSelectDateMinutes'] : [];
		$date_period     = isset( $info['WooCommerceEventsSelectDatePeriod'] ) ? $info['WooCommerceEventsSelectDatePeriod'] : [];
		$date_hour_end   = isset( $info['WooCommerceEventsSelectDateHourEnd'] ) ? $info['WooCommerceEventsSelectDateHourEnd'] : [];
		$date_min_end    = isset( $info['WooCommerceEventsSelectDateMinutesEnd'] ) ? $info['WooCommerceEventsSelectDateMinutesEnd'] : [];
		$date_period_end = isset( $info['WooCommerceEventsSelectDatePeriodEnd'] ) ? $info['WooCommerceEventsSelectDatePeriodEnd'] : [];

		// If dates.
		if ( $dates ) {
			// Loop through dates.
			foreach ( $dates as $index => $date ) {
				// Skip if no date.
				if ( ! $date ) {
					continue;
				}

				$hour       = isset( $date_hour[ $index ] ) ? $date_hour[ $index ] : '';
				$min        = isset( $date_min[ $index ] ) ? $date_min[ $index ] : '';
				$period     = isset( $date_period[ $index ] ) ? $date_period[ $index ] : '';
				$hour_end   = isset( $date_hour_end[ $index ] ) ? $date_hour_end[ $index ] : '';
				$min_end    = isset( $date_min_end[ $index ] ) ? $date_min_end[ $index ] : '';
				$period_end = isset( $date_period_end[ $index ] ) ? $date_period_end[ $index ] : '';
				$start      = strtotime( sprintf( '%s %s:%s %s', $date, $hour, $min, $period ) );
				$end        = strtotime( sprintf( '%s %s:%s %s', $date, $hour_end, $min_end, $period_end ) );

				// Add timestamp.
				$cache[ $post_id ][ $start ] = $end;
			}
		}

	} else {
		$date = isset( $info['WooCommerceEventsDate'] ) ? $info['WooCommerceEventsDate'] : '';

		if ( $date ) {
			$hour       = isset( $info['WooCommerceEventsHour'] ) ? $info['WooCommerceEventsHour'] : '';
			$min        = isset( $info['WooCommerceEventsMinutes'] ) ? $info['WooCommerceEventsMinutes'] : '';
			$period     = isset( $info['WooCommerceEventsPeriod'] ) ? $info['WooCommerceEventsPeriod'] : '';
			$hour_end   = isset( $info['WooCommerceEventsHourEnd'] ) ? $info['WooCommerceEventsHourEnd'] : '';
			$min_end    = isset( $info['WooCommerceEventsMinutesEnd'] ) ? $info['WooCommerceEventsMinutesEnd'] : '';
			$period_end = isset( $info['WooCommerceEventsEndPeriod'] ) ? $info['WooCommerceEventsEndPeriod'] : '';

			if ( $hour && $min && $period && $hour_end && $min_end && $period_end ) {
				$start = strtotime( sprintf( '%s %s:%s %s', $date, $hour, $min, $period ) );
				$end   = strtotime( sprintf( '%s %s:%s %s', $date, $hour_end, $min_end, $period_end ) );

				// Add timestamp.
				$cache[ $post_id ][ $start ] = $end;
			}
		}
	}

	// Sort by start timestamp.
	ksort( $cache[ $post_id ] );

	return $cache[ $post_id ];
}

/**
 * Gets an event (product) categories for class names.
 *
 * @param int $post_id
 *
 * @return string
 */
function skydog_get_category_classes( $post_id ) {
	$classes = [];
	$terms   = get_the_terms( $post_id, 'product_cat' );

	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$classes[] = $term->slug;
		}
	}

	$terms   = get_the_terms( $post_id, 'product_tag' );

	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$classes[] = $term->slug;
		}
	}

	return trim( implode( ' ', $classes ) );
}

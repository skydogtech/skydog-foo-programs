<?php

/**
 * Displays the calendar via Foo shortcode/template.
 *
 * @param JSON   $local_args  The data from Foo.
 * @param string $calendar_id The calendar ID from Foo.
 *
 * @return void
 */
function skydog_do_calendar( $local_args, $calendar_id ) {
	$data                   = json_decode( stripslashes( $local_args['json_events'] ) );
	$data->contentHeight    = 'auto'; // Remove min-height/overflow.
	$data->timeFormat       = 'h(:mm) t';
	$data->displayEventTime = true;

	foreach ( $data->events as $index => $event ) {
		$teachers = skydog_get_the_term_list( 'teacher', $event->post_id );
		$teachers = $teachers ? strip_tags( $teachers ) : '';
		$teachers = $teachers ? ' · ' . $teachers: '';

		$data->events[ $index ]->title           = $data->events[ $index ]->title . $teachers;
		$data->events[ $index ]->className       = skydog_get_category_classes( $event->post_id );
		$data->events[ $index ]->textColor       = 'var(--event-color, inherit)';
		$data->events[ $index ]->backgroundColor = 'var(--event-background-color, inherit)';
		$data->events[ $index ]->borderColor     = 'var(--event-border-color, inherit)';
	}

	static $first = true;

	if ( $first ) {
		// Add category toggles.
		$categories = get_terms(
			[

				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => true,
			]
		);

		if ( $categories && ! is_wp_error( $categories ) ) {
			$css_array = [];

			foreach ( $categories as $term ) {
				$css_array[] = sprintf( '.fooevents_calendar[data-category="%s"] .fc-event:not(.%s), .fooevents_calendar[data-category="%s"] .fc-list-item:not(.%s)', $term->slug, $term->slug, $term->slug, $term->slug );
			}

			echo '<style>';
				echo implode( ',', $css_array ) . '{';
					echo 'display: none;';
				echo '}';
			echo '</style>';

			echo '<p class="skydog-calendar-toggles">';
				foreach ( $categories as $term ) {
					printf( '<button class="skydog-calendar-toggle button button-small" data-category="%s">%s</button>', $term->slug, $term->name );
				}
				printf( '<button class="skydog-calendar-toggle skydog-calendar-toggle-clear" data-category="" disabled="true">%s</button>', __( 'Clear', 'skydog-foo-programs' ) );
			echo '</p>';
		}

		$first = false;
	}

	$local_args['json_events'] = addslashes( json_encode( $data ) );
	?>
	<div id='<?php echo esc_attr( $calendar_id ); ?>' class="fooevents_calendar" style="clear:both;"></div>
	<script>
	(function($) {
		var localObj = '<?php echo $local_args['json_events']; ?>';
		var settings = JSON.parse( localObj );
		if( $( '#'+settings.id ).length ) {
			settings.eventRender = function(event, element) {
				// Decode HTML entities for the title.
				var decodedTitle = $('<div>').html(event.title).text(); // Decodes &amp; to &.
				element.find('.fc-title').text(decodedTitle); // Set the decoded title.
			};

			jQuery( '#'+settings.id ).fullCalendar( settings );
		}

		$( '.skydog-calendar-toggles' ).on( 'click', '.skydog-calendar-toggle', function() {
			$toggle = $(this);
			$clear  = $( '.skydog-calendar-toggle-clear' );

			// Remove all active classes.
			$( '.skydog-calendar-toggle' ).removeClass( 'skydog-calendar-toggle-active' );

			// Set category attribute.
			$( '.fooevents_calendar' ).attr( 'data-category', $toggle.attr( 'data-category' ) );

			// If clicking clear, toggle disabled.
			if ( $toggle.hasClass( 'skydog-calendar-toggle-clear' ) ) {
				$clear.attr( 'disabled', 'disabled' );
			}
			// Clicking button.
			else {
				// Add active class.
				$toggle.addClass( 'skydog-calendar-toggle-active' );

				// Make sure clear button is not disabled.
				if ( $clear.attr( 'disabled' ) ) {
					$clear.removeAttr( 'disabled' );

				}
			}
		});
	})(jQuery);
	</script>
	<?php
}

/**
 * Displays a list of events via Foo shortcode/template.
 *
 * @param object[] $calendar_id The calendar ID from Foo.
 *
 * @return void
 */
function skydog_do_events_list( $events ) {
	if ( empty( $events ) ) {
		printf( '<p class="program-list-none">%s</p>', esc_attr( 'No upcoming events.', 'skydog-foo-programs' ) );
		return;
	}

	echo '<ul class="skydog-program-list">';
		$indexes = [];
		$count   = 1;
		$total   = 5; // Total number to show. Hard-coded because we manually remove expired events below.
		$ordered = [];
		$current = current_time( 'timestamp' );

		foreach ( $events as $event ) {
			// Bail if we have enough.
			if ( $count > $total ) {
				break;
			}

			// Skip if not data we want.
			if ( ! is_array( $event ) ) {
				continue;
			}

			// Make sure index is set.
			$indexes[ $event['post_id'] ] = isset( $indexes[ $event['post_id'] ] ) ? $indexes[ $event['post_id'] ] : 0;

			// Get upcoming occurrence.
			$object = skydog_get_date_time_object( $event['post_id'], $indexes[ $event['post_id'] ] );

			// Bail if a past event.
			if ( ! $object ) {
				continue;
			}

			// Get timestamp from upcoming occurence.
			$timestamp = $object->getTimestamp();

			// Skip if no timestamp.
			if ( ! $timestamp ) {
				continue;
			}

			// Add to ordered array.
			$ordered[ $timestamp ] = $event;

			// Increment count and index.
			$count++;
			$indexes[ $event['post_id'] ]++;
		}

		// Order by key.
		ksort( $ordered );

		// Reset indexes.
		$indexes = [];

		foreach ( $ordered as $event ) {
			// Make sure index is set.
			$indexes[ $event['post_id'] ] = isset( $indexes[ $event['post_id'] ] ) ? $indexes[ $event['post_id'] ] : 0;

			// $thumbnail = get_the_post_thumbnail_url( $event['post_id'] );

			echo '<li class="skydog-program-list-item">';
				printf( '<h3 class="skydog-program-list-title"><a href="%s">%s</a></h3>', $event['url'], esc_html( $event['title'] ) );

				// if ( ! empty( $thumbnail ) ) {
				// 	printf( '<img src="%s" class="skydog-program-list-image"/>', esc_attr( $thumbnail ) );
				// }

				// if ( ! empty( $event['desc'] ) ) {
				// 	printf( '<p class="skydog-program-list-desc">%s</p>', wp_kses_post( $event['desc'] ) );
				// }

				printf( '<p class="program-list-datetime">%s</p>', skydog_get_date_time_details( $event['post_id'], $indexes[ $event['post_id'] ] ) );
				printf( '<p class="program-list-teacher">%s</p>', skydog_get_the_term_list( 'teacher', $event['post_id'] ) );

				// if ( ! empty( $event['in_stock'] ) && 'yes' === $event['in_stock'] ) {
				// 	printf( '<p class="program-list-button"><a class="button button-alt button-small" href="%s" rel="nofollow">%s</a></p>', esc_attr( $event['url'] ), __( 'View', 'skydog-foo-programs' ) );
				// }
			echo '</li>';

			// Increment index.
			$indexes[ $event['post_id'] ]++;
		}
	echo '</ul>';
}
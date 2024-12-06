<?php

/**
 * Run these function late, making sure Woo is loaded.
 *
 * @return void
 */
add_action( 'after_setup_theme', function() {
	/**
	 * Removes result count from shop loop.
	 *
	 * @return void
	 */
	remove_action( 'woocommerce_before_shop_loop' , 'woocommerce_result_count', 20 );

	/**
	 * Removes sort select box from shop loop.
	 *
	 * @return void
	 */
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

	/**
	 * Removes links from title in shop loop.
	 * This allows other links (teachers) below it on the shop.
	 *
	 * @return void
	 */
	remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );

	/**
	 * Removes prices from shop loop.
	 */
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );

	/**
	 * Remove product images from archive template.
	 *
	 * @return void
	 */
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
});

/**
 * Load custom JS.
 *
 * @param array $scripts The existing scripts config.
 *
 * @return array
 */
add_filter( 'mai_scripts_config', function( $scripts ) {
	$scripts['archive-product'] = [
		'src'       => SKYDOG_FOO_PROGRAMS_PLUGIN_URL . '/assets/js/archive-product.js',
		'condition' => function () {
			return skydog_is_program_archive();
		},
	];

	return $scripts;
});

/**
 * Add custom archive body class.
 *
 * @param array The existing body classes.
 *
 * @return array Modified classes.
 */
add_filter( 'body_class', function( $classes ) {
	if ( skydog_is_program_archive() ) {
		$classes[] = 'program-archive';
	}

	return $classes;
});

/**
 * Remove default loop for teachers.
 * Without this, the archive was showing posts as well as the WC Shop loop.
 *
 * @param bool $remove
 *
 * @return bool
 */
add_filter( 'mai_remove_entries', function( $remove ) {
	if ( ! is_tax( 'teacher' ) ) {
		return $remove;
	}

	return true;
});

/**
 * Handles hide/show of expired events.
 * Using 'hide':
 *   Product pages 404.
 *   Removes from `[fooevents_events_list]` shortcode.
 *   Removes from `[fooevents_calendar]` shortcode.
 *   Does not hide/expire recurring events.
 *
 * Using 'disable':
 *   Shows product pages but disables add-to-cart.
 *   Shows in `[fooevents_events_list]` shortcode.
 *   Shows in `[fooevents_calendar]` shortcode.
 *
 * @param string $value The existing value. Either 'disable' or 'hide';
 *
 * @return string
 */
add_filter( 'option_globalWooCommerceEventsExpireOption', function( $value ) {
	if ( ! is_admin() && skydog_is_program_archive() ) {
		$value = 'hide';
	}

	return $value;
});

/**
 * Does all the List.js, table, and program info magic.
 *
 * @return void
 */
add_action( 'woocommerce_before_shop_loop', function() {
	$is_program_cat = is_tax( 'product_cat' );
	$is_teacher     = is_tax( 'teacher' );
 	if ( $is_program_cat || $is_teacher ) {
		$term    = get_queried_object();
		$heading = $is_teacher ? __( 'Programs', 'skydog-foo-programs' ) : $term->name;
		printf( '<h2 class="program-archive-heading">%s %s</h2>', __( 'Upcoming', 'skydog-foo-programs' ), $heading );
	}

	global $wp_query;

	$teachers = $categories = $types = [];

	if ( $wp_query->posts ) {
		foreach ( $wp_query->posts as $post ) {
			$the_teachers   = get_the_terms( $post, 'teacher' );
			$the_categories = get_the_terms( $post, 'product_cat' );
			$the_types      = get_the_terms( $post, 'product_tag' );

			if ( $the_teachers && ! is_wp_error( $the_teachers ) ) {
				$the_teachers   = wp_list_pluck( $the_teachers, 'name' );
				$teachers       = array_unique( array_merge( $teachers, $the_teachers ) );
			}

			if ( $the_categories && ! is_wp_error( $the_categories ) ) {
				$the_categories = wp_list_pluck( $the_categories, 'name' );
				$categories     = array_unique( array_merge( $categories, $the_categories ) );
			}

			if ( $the_types && ! is_wp_error( $the_types ) ) {
				$the_types      = wp_list_pluck( $the_types, 'name' );
				$types          = array_unique( array_merge( $types, $the_types ) );
			}
		}
	}

	echo '<ul class="program-filters">';
		// Search.
		echo '<li class="program-filter">';
			printf( '<label for="search"><strong>%s:</strong></label>', __( 'Instant Search', 'skydog-foo-programs' ) );
			printf( '<input name="search" type="search" data-insensitive="false" class="search" placeholder="%s">', __( 'Search by keyword..', 'skydog-foo-programs' ) );
		echo '</li>';

		// Teachers.
		if ( ! is_tax( 'teacher' ) ) {
			echo '<li class="program-filter">';
				printf( '<label for="teachers"><strong>%s:</strong></label>', __( 'Teachers', 'skydog-foo-programs' ) );
				echo '<select name="teachers">';
					printf( '<option value="">%s</option>', __( 'All Teachers', 'skydog-foo-programs' ) );
					foreach( $teachers as $teacher ) {
						printf( '<option value="%s">%s</option>', esc_attr( $teacher ), esc_html( $teacher ) );
					}
				echo '</select>';
			echo '</li>';
		}

		// Categories.
		if ( ! is_tax( 'product_cat' ) ) {
			echo '<li class="program-filter">';
				printf( '<label for="categories"><strong>%s:</strong></label>', __( 'Categories', 'skydog-foo-programs' ) );
				echo '<select name="categories">';
					printf( '<option value="">%s</option>', __( 'All Categories', 'skydog-foo-programs' ) );
					foreach( $categories as $category ) {
						printf( '<option value="%s">%s</option>', esc_attr( $category ), esc_html( $category ) );
					}
				echo '</select>';
			echo '</li>';
		}

		// Types.
		if ( ! is_tax( 'product_tag' ) ) {
			echo '<li class="program-filter">';
				printf( '<label for="types"><strong>%s:</strong></label>', __( 'Types', 'skydog-foo-programs' ) );
				echo '<select name="types">';
					printf( '<option value="">%s</option>', __( 'All Types', 'skydog-foo-programs' ) );
					foreach( $types as $type ) {
						printf( '<option value="%s">%s</option>', esc_attr( $type ), esc_html( $type ) );
					}
				echo '</select>';
			echo '</li>';
		}
	echo '</ul>';

	$icon = mai_get_icon(
		[
			'icon'    => 'caret-right',
			'style'   => 'solid',
			'display' => 'inline-flex',
			'size'    => '0.85em',
		]
	);

	echo '<ul class="program-sort">';
		printf( '<li><button class="sort" data-sort="program_title">%s%s</button></li>', __( 'Program', 'skydog-foo-programs' ), $icon );
		if ( ! is_tax( 'teacher' ) ) {
			printf( '<li><button class="sort" data-sort="program_teacher">%s%s</button></li>', __( 'Teacher', 'skydog-foo-programs' ), $icon );
		}
		if ( ! is_tax( 'product_cat' ) ) {
			printf( '<li><button class="sort" data-sort="program_category">%s%s</button></li>', __( 'Category', 'skydog-foo-programs' ), $icon );
		}
		if ( ! is_tax( 'product_tag' ) ) {
			printf( '<li><button class="sort" data-sort="program_type">%s%s</button></li>', __( 'Type', 'skydog-foo-programs' ), $icon );
		}
		printf( '<li><button class="sort" data-sort="program_date" data-default-order="desc">%s%s</button></li>', __( 'Date/Time', 'skydog-foo-programs' ), $icon );
	echo '</ul>';
});

add_action( 'woocommerce_before_shop_loop_item', function() {
	// Remove add to cart button.
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart');
}, 10 );

add_action( 'woocommerce_shop_loop_item_title', function() {
	echo '<div class="program-title">';
		printf( '<a class="program-title-link program_title" href="%s">', get_the_permalink() );
}, 2 );

add_action( 'woocommerce_shop_loop_item_title', function() {
		echo '</a>'; // .program-title
	echo '</div>'; // .program-title

	/**
	 * Program teachers.
	 ********************/
	if ( ! is_tax( 'teacher' ) ) {
		echo '<div class="program-teacher program_teacher">';
			echo skydog_get_term_list( 'teacher' );
		echo '</div>';
	}

	/**
	 * Program Category.
	 ********************/
	if ( ! is_tax( 'product_cat' ) ) {
		echo '<div class="program-category program_category">';
			echo skydog_get_term_list( 'product_cat' );
		echo '</div>';
	}


	/**
	 * Program types.
	 ********************/
	if ( ! is_tax( 'product_tag' ) ) {
		echo '<div class="program-type program_type">';
			echo skydog_get_term_list( 'product_tag' );
		echo '</div>';
	}

	/**
	 * Program Start Date.
	 ********************/
	echo '<div class="program-datetime program_date">';
		echo skydog_get_date_time_details( get_the_ID() );
	echo '</div>';

}, 15 );

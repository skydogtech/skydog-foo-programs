<?php

/**
 * Plugin Name:     Sky Dog Foo Programs
 * Plugin URI:      https://skydogtech.com
 * Description:     Customizing Programs from Foo Events via WooCommerce Products
 * Version:         0.1.4
 *
 * Author:          Sky Dog Tech
 * Author URI:      https://skydogtech.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Must be at the top of the file.
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Main SkyDog_Foo_Programs_Plugin Class.
 *
 * @since 0.1.0
 */
final class SkyDog_Foo_Programs_Plugin {

	/**
	 * @var   SkyDog_Foo_Programs_Plugin The one true SkyDog_Foo_Programs_Plugin
	 * @since 0.1.0
	 */
	private static $instance;

	/**
	 * Main SkyDog_Foo_Programs_Plugin Instance.
	 *
	 * Insures that only one instance of SkyDog_Foo_Programs_Plugin exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   0.1.0
	 * @static  var array $instance
	 * @uses    SkyDog_Foo_Programs_Plugin::setup_constants() Setup the constants needed.
	 * @uses    SkyDog_Foo_Programs_Plugin::includes() Include the required files.
	 * @uses    SkyDog_Foo_Programs_Plugin::hooks() Activate, deactivate, etc.
	 * @see     SkyDog_Foo_Programs_Plugin()
	 * @return  object | SkyDog_Foo_Programs_Plugin The one true SkyDog_Foo_Programs_Plugin
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup.
			self::$instance = new SkyDog_Foo_Programs_Plugin;
			// Methods.
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->hooks();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'skydog-foo-programs' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   0.1.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'skydog-foo-programs' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function setup_constants() {
		// Plugin version.
		if ( ! defined( 'SKYDOG_FOO_PROGRAMS_VERSION' ) ) {
			define( 'SKYDOG_FOO_PROGRAMS_VERSION', '0.1.4' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'SKYDOG_FOO_PROGRAMS_PLUGIN_DIR' ) ) {
			define( 'SKYDOG_FOO_PROGRAMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Includes Path.
		if ( ! defined( 'SKYDOG_FOO_PROGRAMS_INCLUDES_DIR' ) ) {
			define( 'SKYDOG_FOO_PROGRAMS_INCLUDES_DIR', SKYDOG_FOO_PROGRAMS_PLUGIN_DIR . 'includes/' );
		}

		// Plugin Folder URL.
		if ( ! defined( 'SKYDOG_FOO_PROGRAMS_PLUGIN_URL' ) ) {
			define( 'SKYDOG_FOO_PROGRAMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'SKYDOG_FOO_PROGRAMS_PLUGIN_FILE' ) ) {
			define( 'SKYDOG_FOO_PROGRAMS_PLUGIN_FILE', __FILE__ );
		}

		// Plugin Base Name
		if ( ! defined( 'SKYDOG_FOO_PROGRAMS_BASENAME' ) ) {
			define( 'SKYDOG_FOO_PROGRAMS_BASENAME', dirname( plugin_basename( __FILE__ ) ) );
		}
	}

	/**
	 * Include required files.
	 *
	 * @access  private
	 * @since   0.1.0
	 * @return  void
	 */
	private function includes() {
		// Include vendor libraries.
		require_once __DIR__ . '/vendor/autoload.php';
		// Includes.
		foreach ( glob( SKYDOG_FOO_PROGRAMS_INCLUDES_DIR . '*.php' ) as $file ) { include $file; }
	}

	/**
	 * Run the hooks.
	 *
	 * @since   0.1.0
	 * @return  void
	 */
	public function hooks() {
		add_action( 'plugins_loaded', [ $this, 'updater' ] );
		add_action( 'init',           [ $this, 'register_content_types' ] );

		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
	}

	/**
	 * Setup the updater.
	 *
	 * composer require yahnis-elsts/plugin-update-checker
	 *
	 * @since 0.1.0
	 *
	 * @uses https://github.com/YahnisElsts/plugin-update-checker/
	 *
	 * @return void
	 */
	public function updater() {
		// Bail if plugin updater is not loaded.
		if ( ! class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
			return;
		}

		// Setup the updater.
		$updater = PucFactory::buildUpdateChecker( 'https://github.com/skydogtech/skydog-foo-programs/', __FILE__, 'skydog-foo-programs' );

		// Maybe set github api token.
		if ( defined( 'SKYDOG_GITHUB_API_TOKEN' ) ) {
			$updater->setAuthentication( SKYDOG_GITHUB_API_TOKEN );
		}
	}

	/**
	 * Register content types.
	 *
	 * @return  void
	 */
	public function register_content_types() {
		$labels = [
			'name'                       => _x( 'Teachers', 'Teacher General Name', 'skydog-foo-programs' ),
			'singular_name'              => _x( 'Teacher', 'Teacher Singular Name', 'skydog-foo-programs' ),
			'menu_name'                  => __( 'Teachers', 'skydog-foo-programs' ),
			'all_items'                  => __( 'All Items', 'skydog-foo-programs' ),
			'parent_item'                => __( 'Parent Item', 'skydog-foo-programs' ),
			'parent_item_colon'          => __( 'Parent Item:', 'skydog-foo-programs' ),
			'new_item_name'              => __( 'New Item Name', 'skydog-foo-programs' ),
			'add_new_item'               => __( 'Add New Item', 'skydog-foo-programs' ),
			'edit_item'                  => __( 'Edit Item', 'skydog-foo-programs' ),
			'update_item'                => __( 'Update Item', 'skydog-foo-programs' ),
			'view_item'                  => __( 'View Item', 'skydog-foo-programs' ),
			'separate_items_with_commas' => __( 'Separate items with commas', 'skydog-foo-programs' ),
			'add_or_remove_items'        => __( 'Add or remove items', 'skydog-foo-programs' ),
			'choose_from_most_used'      => __( 'Choose from the most used', 'skydog-foo-programs' ),
			'popular_items'              => __( 'Popular Items', 'skydog-foo-programs' ),
			'search_items'               => __( 'Search Items', 'skydog-foo-programs' ),
			'not_found'                  => __( 'Not Found', 'skydog-foo-programs' ),
		];
		$args = [
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_in_rest'               => true,
			'show_tagcloud'              => true,
			'rewrite'                    => [ 'slug' => 'teachers', 'with_front' => false ],
		];

		register_taxonomy( 'teacher', [ 'product', 'post', 'cue_playlist' ], $args );

		// Allow HTML in term descriptions.
		foreach ( array( 'pre_term_description' ) as $filter ) {
			remove_filter( $filter, 'wp_filter_kses' );
		}

		foreach ( array( 'term_description' ) as $filter ) {
			remove_filter( $filter, 'wp_kses_data' );
		}
	}

	/**
	 * Plugin activation.
	 *
	 * @return void
	 */
	public function activate() {
		$this->register_content_types();
		flush_rewrite_rules();
	}
}

/**
 * The main function for that returns SkyDog_Foo_Programs_Plugin
 *
 * The main function responsible for returning the one true SkyDog_Foo_Programs_Plugin
 * Instance to functions everywhere.
 *
 * @since 0.1.0
 *
 * @return object|SkyDog_Foo_Programs_Plugin The one true SkyDog_Foo_Programs_Plugin Instance.
 */
function skydog_foo_programs_plugin() {
	return SkyDog_Foo_Programs_Plugin::instance();
}

// Get SkyDog_Foo_Programs_Plugin Running.
skydog_foo_programs_plugin();

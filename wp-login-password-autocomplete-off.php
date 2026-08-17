<?php
/**
 * Plugin Name:     WP Login Password Autocomplete Off
 * Plugin URI:      https://github.com/wildoperation/WP-Login-Password-Autocomplete-Off
 * Description:     Forces autocomplete="off" onto the password field of the WordPress login screen. Resolves security scanner findings without modifying WordPress core.
 * Version:         1.0.0
 * Author:          Wild Operation
 * Author URI:      https://wildoperation.com
 * License:         GPLv3
 * License URI:     https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:     disable-login-password-autocomplete
 *
 * @package WordPress
 * @subpackage Disable Login Password Autocomplete
 * @since 1.0.0
 * @version 1.0.0
 */

/* Abort! */
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WODLPA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WODLPA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WODLPA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Load
 */
require WODLPA_PLUGIN_DIR . 'vendor/autoload.php';

/**
 * Initialize; plugins_loaded
 */
add_action(
	'plugins_loaded',
	function () {
		/**
		 * Initiate classes and their hooks.
		 */
		$classes = array(
			'WODLPA\Login',
		);

		foreach ( $classes as $class ) {
			$instance = new $class();

			if ( method_exists( $instance, 'hooks' ) ) {
				$instance->hooks();
			}
		}
	},
	10
);

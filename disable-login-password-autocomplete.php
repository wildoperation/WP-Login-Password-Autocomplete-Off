<?php
/**
 * Plugin Name:     Disable Login Password Autocomplete
 * Plugin URI:      https://github.com/wildoperation/Disable-Login-Password-Autocomplete-WordPress-Plugin
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
 * Review request framework
 */
add_action(
	'admin_init',
	function () {
		new WODLPA\Vendor\WOWPRB\WPPluginReviewBug(
			__FILE__,
			'disable-login-password-autocomplete',
			array(
				'intro'            => __( 'Your Disable Login Password Autocomplete reviews are invaluable to us and help us maintain a free version of this plugin. We appreciate your support!', 'disable-login-password-autocomplete' ),
				'rate_link_text'   => __( 'Leave ★★★★★ rating', 'disable-login-password-autocomplete' ),
				'need_help_text'   => __( 'I need help', 'disable-login-password-autocomplete' ),
				'remind_link_text' => __( 'Remind me later', 'disable-login-password-autocomplete' ),
				'nobug_link_text'  => __( 'Don\'t ask again', 'disable-login-password-autocomplete' ),
			),
			array(
				'need_help_url' => WODLPA\Plugin::support_url(),
			)
		);
	},
	1
);

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

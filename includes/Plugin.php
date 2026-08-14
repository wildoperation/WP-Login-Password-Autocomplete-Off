<?php
namespace WODLPA;

/**
 * Misc data used throughout this plugin.
 */
class Plugin {

	/**
	 * The plugin version.
	 *
	 * @return string
	 */
	public static function version() {
		return '1.0.0';
	}

	/**
	 * The plugin title.
	 *
	 * @return string
	 */
	public static function title() {
		return __( 'Disable Login Password Autocomplete', 'disable-login-password-autocomplete' );
	}

	/**
	 * The menu title.
	 *
	 * @return string
	 */
	public static function menu_title() {
		return self::title();
	}

	/**
	 * The plugin namespace.
	 *
	 * @return string
	 */
	public static function ns() {
		return 'wodlpa';
	}

	/**
	 * The required capability for managing this plugin.
	 *
	 * @return string
	 */
	public static function capability() {
		return 'manage_options';
	}

	/**
	 * The URL to the plugin support page.
	 *
	 * @return string
	 */
	public static function support_url() {
		return 'https://wordpress.org/support/plugin/disable-login-password-autocomplete/';
	}
}

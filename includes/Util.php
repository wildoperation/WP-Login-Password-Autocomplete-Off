<?php
namespace WODLPA;

/**
 * Misc helper functions used throughout this plugin.
 */
class Util {

	/**
	 * Create a prefixed string for use throughout plugin to avoid conflicts.
	 *
	 * @param string $str The string to prefix.
	 * @param string $sep The seperator.
	 * @param string $ns The prefix.
	 *
	 * @return string
	 */
	public static function ns( $str, $sep = '_', $ns = null ) {
		if ( ! $ns ) {
			$ns = Plugin::ns();
		}

		return $ns . $sep . $str;
	}

	/**
	 * Converts a string into the correct bool.
	 *
	 * @param mixed $value Any string or bool.
	 *
	 * @return bool
	 */
	public static function truthy( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );

			return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
		}

		return (bool) $value;
	}
}

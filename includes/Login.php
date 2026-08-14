<?php
namespace WODLPA;

/**
 * Rewrites the markup of the WordPress login screen (wp-login.php) so that
 * password fields are served with autocomplete="off".
 *
 * WordPress core hardcodes autocomplete="current-password" on the login password
 * field and there is no filter to change it, so the only way to alter the markup
 * that is actually sent to the browser is to buffer the login screen output and
 * rewrite the attribute before it is flushed.
 *
 * This is done in PHP rather than JavaScript on purpose. A JavaScript solution
 * mutates the DOM after the response has already been delivered, which means the
 * served HTML still contains autocomplete="current-password" and a scanner that
 * inspects the raw response will continue to report the finding.
 *
 * @see https://core.trac.wordpress.org/ticket/41136
 */
class Login {

	/**
	 * Whether our output buffer was successfully opened.
	 *
	 * @var bool
	 */
	private $buffering = false;

	/**
	 * Create hooks.
	 *
	 * The login_init hook fires in wp-login.php before any markup is generated,
	 * which makes it the earliest safe place to open a buffer around the login screen.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'login_init', array( $this, 'start_buffer' ), 0 );
	}

	/**
	 * Whether this plugin should do anything at all.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		/**
		 * Filters whether the login screen markup should be rewritten.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $enabled Default true.
		 */
		return Util::truthy( apply_filters( 'wodlpa_enabled', true ) );
	}

	/**
	 * Whether password inputs should be rewritten.
	 *
	 * @return bool
	 */
	public static function target_password_inputs() {
		/**
		 * Filters whether autocomplete="off" is forced onto password inputs.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $enabled Default true.
		 */
		return Util::truthy( apply_filters( 'wodlpa_target_password_inputs', true ) );
	}

	/**
	 * Whether the login form tags should be rewritten.
	 *
	 * Some scanners check the enclosing <form> tag rather than (or in addition to)
	 * the password input, so this is enabled by default.
	 *
	 * @return bool
	 */
	public static function target_forms() {
		/**
		 * Filters whether autocomplete="off" is forced onto login form tags.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $enabled Default true.
		 */
		return Util::truthy( apply_filters( 'wodlpa_target_forms', true ) );
	}

	/**
	 * Whether username inputs should be rewritten.
	 *
	 * Disabled by default. Core sends autocomplete="username" on these fields and
	 * scanners generally only flag the password field.
	 *
	 * @return bool
	 */
	public static function target_username_inputs() {
		/**
		 * Filters whether autocomplete="off" is forced onto username inputs.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $enabled Default false.
		 */
		return Util::truthy( apply_filters( 'wodlpa_target_username_inputs', false ) );
	}

	/**
	 * Open an output buffer around the login screen.
	 *
	 * The buffer is intentionally never closed by this plugin. PHP flushes it at the
	 * end of the request -- by way of WordPress' own wp_ob_end_flush_all() on shutdown --
	 * which invokes our callback with the complete response. Closing it ourselves would
	 * risk unbalancing buffers opened by other plugins.
	 *
	 * @return void
	 */
	public function start_buffer() {
		if ( $this->buffering || ! self::is_enabled() ) {
			return;
		}

		if ( wp_doing_ajax() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
			return;
		}

		$this->buffering = ob_start( array( $this, 'filter_login_html' ) );
	}

	/**
	 * Output buffer callback.
	 *
	 * PHP also passes a bitmask of PHP_OUTPUT_HANDLER_* constants as a second
	 * argument. We rewrite the same way regardless of phase, so it is not accepted.
	 *
	 * @param string $html The buffered login screen markup.
	 *
	 * @return string
	 */
	public function filter_login_html( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}

		return self::disable_autocomplete( $html );
	}

	/**
	 * Force autocomplete="off" onto the password fields (and optionally the forms
	 * and username fields) within a block of HTML.
	 *
	 * Public and static so that themes or other plugins can reuse it against their
	 * own login markup.
	 *
	 * @param string $html The HTML to rewrite.
	 *
	 * @return string
	 */
	public static function disable_autocomplete( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return $html;
		}

		$do_inputs    = self::target_password_inputs();
		$do_usernames = self::target_username_inputs();
		$do_forms     = self::target_forms();

		if ( ( $do_inputs || $do_usernames ) && stripos( $html, '<input' ) !== false ) {
			$updated = preg_replace_callback(
				'#<input\b[^>]*>#i',
				function ( $matches ) use ( $do_inputs, $do_usernames ) {
					$tag = $matches[0];

					if ( $do_inputs && self::tag_has_attribute_value( $tag, 'type', 'password' ) ) {
						return self::set_attribute( $tag, 'autocomplete', 'off' );
					}

					if ( $do_usernames && self::is_username_input( $tag ) ) {
						return self::set_attribute( $tag, 'autocomplete', 'off' );
					}

					return $tag;
				},
				$html
			);

			/**
			 * Guard against PCRE failure.
			 *
			 * Null is returned if PCRE fails, for example when the backtrack limit is
			 * hit. Never hand that back: a blank login screen is a far worse outcome
			 * than an unmodified autocomplete attribute.
			 */
			if ( null !== $updated ) {
				$html = $updated;
			}
		}

		if ( $do_forms && stripos( $html, '<form' ) !== false ) {
			$updated = preg_replace_callback(
				'#<form\b[^>]*>#i',
				function ( $matches ) {
					return self::set_attribute( $matches[0], 'autocomplete', 'off' );
				},
				$html
			);

			if ( null !== $updated ) {
				$html = $updated;
			}
		}

		/**
		 * Filters the rewritten login screen markup.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html The rewritten markup.
		 */
		return apply_filters( 'wodlpa_login_html', $html );
	}

	/**
	 * Whether a tag looks like a username/login field.
	 *
	 * @param string $tag A single HTML tag.
	 *
	 * @return bool
	 */
	private static function is_username_input( $tag ) {
		if ( self::tag_has_attribute_value( $tag, 'autocomplete', 'username' ) ) {
			return true;
		}

		return self::tag_has_attribute_value( $tag, 'id', 'user_login' );
	}

	/**
	 * Test whether a tag carries an attribute with an exact value.
	 *
	 * Handles single quoted, double quoted, and unquoted attribute values.
	 *
	 * @param string $tag       A single HTML tag.
	 * @param string $attribute The attribute name.
	 * @param string $value     The value to compare against, case insensitive.
	 *
	 * @return bool
	 */
	private static function tag_has_attribute_value( $tag, $attribute, $value ) {
		$found = self::get_attribute( $tag, $attribute );

		if ( null === $found ) {
			return false;
		}

		return strtolower( trim( $found ) ) === strtolower( $value );
	}

	/**
	 * Read an attribute value from a single HTML tag.
	 *
	 * @param string $tag       A single HTML tag.
	 * @param string $attribute The attribute name.
	 *
	 * @return null|string Null when the attribute is not present.
	 */
	private static function get_attribute( $tag, $attribute ) {
		$attribute = preg_quote( $attribute, '#' );

		if ( preg_match( '#\s' . $attribute . '\s*=\s*(["\'])(.*?)\1#is', $tag, $matches ) ) {
			return $matches[2];
		}

		if ( preg_match( '#\s' . $attribute . '\s*=\s*([^\s"\'=<>`]+)#is', $tag, $matches ) ) {
			return $matches[1];
		}

		return null;
	}

	/**
	 * Set an attribute on a single HTML tag, replacing any existing value.
	 *
	 * @param string $tag       A single HTML tag, including the angle brackets.
	 * @param string $attribute The attribute name.
	 * @param string $value     The attribute value.
	 *
	 * @return string
	 */
	private static function set_attribute( $tag, $attribute, $value ) {
		$replacement = $attribute . '="' . $value . '"';
		$pattern     = preg_quote( $attribute, '#' );
		$count       = 0;

		/**
		 * Replace a quoted value.
		 */
		$updated = preg_replace( '#\s' . $pattern . '\s*=\s*(["\'])(.*?)\1#is', ' ' . $replacement, $tag, 1, $count );

		if ( $count > 0 ) {
			return $updated;
		}

		/**
		 * Replace an unquoted value.
		 */
		$updated = preg_replace( '#\s' . $pattern . '\s*=\s*[^\s"\'=<>`]+#is', ' ' . $replacement, $tag, 1, $count );

		if ( $count > 0 ) {
			return $updated;
		}

		/**
		 * The attribute is absent, so append it before the closing bracket.
		 */
		$inner        = substr( $tag, 0, -1 );
		$self_closing = false;

		if ( substr( $inner, -1 ) === '/' ) {
			$self_closing = true;
			$inner        = substr( $inner, 0, -1 );
		}

		return rtrim( $inner ) . ' ' . $replacement . ( $self_closing ? ' /' : '' ) . '>';
	}
}

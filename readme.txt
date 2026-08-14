=== Disable Login Password Autocomplete ===
Contributors: wildoperation, timstl
Tags: login, password, autocomplete, security, hardening
Requires at least: 6.2
Tested up to: 7.1
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Serve the WordPress login screen with autocomplete="off" on the password field, to satisfy security scanners, without modifying core.

== Description ==

WordPress core outputs the login password field on `wp-login.php` with `autocomplete="current-password"`. Security scanners commonly flag this as a low severity finding ("password field permits autocomplete") and ask for `autocomplete="off"` instead.

`wp-login.php` is part of WordPress core and there is no filter for that markup, so the attribute cannot be changed by a plugin in the usual way. This plugin buffers the login screen output and rewrites the attribute in PHP before the response is sent, so the HTML that leaves the server already contains `autocomplete="off"`.

By default the plugin:

* Sets `autocomplete="off"` on every `<input type="password">` on the login screen. This covers the login form, the password reset form, and the interim (session expired) login.
* Sets `autocomplete="off"` on the `<form>` tags of the login screen, because some scanners check the form tag rather than the input.

It does not touch the username field by default, does not change anything on the front end of your site, and does not run on the admin profile screens.

= Why PHP and not JavaScript? =

JavaScript solutions change the attribute in the DOM after the page has loaded. The HTML that was actually served still contains `autocomplete="current-password"`, so a scanner that reads the raw response will keep reporting the finding. The PHP approach changes the response itself, works with JavaScript disabled, and leaves nothing to fix up after render.

= A note on what this does and does not do =

Chrome, Firefox, Safari, and Edge deliberately ignore `autocomplete="off"` on password fields, and have done so for over a decade. The browser vendors concluded that blocking password managers pushes people toward weaker, reused, and hand typed passwords. This plugin will therefore satisfy the scanner and any compliance checklist that requires the attribute, but you should not expect it to stop a browser or password manager from offering to save the password.

= Filters =

`wodlpa_enabled` (bool, default `true`)
Master switch. Return `false` to disable all rewriting.

`wodlpa_target_password_inputs` (bool, default `true`)
Whether `autocomplete="off"` is forced onto password inputs.

`wodlpa_target_forms` (bool, default `true`)
Whether `autocomplete="off"` is forced onto the login screen form tags.

`wodlpa_target_username_inputs` (bool, default `false`)
Whether `autocomplete="off"` is forced onto the username field. Enable this if your scanner also flags the username field.

`wodlpa_login_html` (string)
The full rewritten login screen markup, filtered last.

Example:

`add_filter( 'wodlpa_target_username_inputs', '__return_true' );`

== Frequently Asked Questions ==

= Does this cover login forms in my theme? =

No. This plugin only rewrites `wp-login.php`. Front end login forms rendered by `wp_login_form()` or by a page builder are not affected. The rewriting logic is available as `\WODLPA\Login::disable_autocomplete( $html )` if you want to apply it to your own markup.

= Will my password manager stop saving my password? =

Probably not. See the note in the description: modern browsers intentionally ignore `autocomplete="off"` on password fields.

= Does this slow down my site? =

No. The output buffer is only opened on `wp-login.php`, so no front end or admin request is affected.

= How do I verify it worked? =

Request the login page and look at the raw HTML, for example:

`curl -s https://example.com/wp-login.php | grep user_pass`

The `input` tag should contain `autocomplete="off"`.

== Changelog ==

= 1.0.0 =
* Initial version

<?php

/*
* Add a 'per_page' option to the Screen Options tab in a List Table.
*
* To use this class, create the object early (at the latest, hook 'wp_loaded'),
* and then call '->load()' in the late hook "load-{$page_hook}".
*
* Using add_screen_option(), the option 'per_page' gets special treatment in
* 'wp-admin/includes/screen.php', and so does not need a rendering function.
*
* Update processing is done by set_screen_options() in 'wp-admin/includes/misc.php'.
*
*/
class Ltsoe_Screen_Options_Per_Page {

	private $_unique_key;		// Name in the wpdb->usermeta table.
	private $_label;
	private $_default_value;


	/*
	* Set up before WP does set_screen_options() in 'wp-admin/admin.php'.
	*
	* Call 'new' for the class early, in the plugin global scope,
	* or hooked to any any early action: 'plugins_loaded', ..., 'init', 'wp_loaded'.
	* These hooks are too late: 'admin_menu', 'admin_init', "load-{$hook}".
	*
	* Note the globals $plugin_page, $page_hook are not yet set.
	* Could create the class object only as needed by checking $_GET['page'],
	* which is done below anyway for the update validator.
	*
	* Example:
	*	$foo = new Ltsoe_Screen_Options_Per_Page(
	*		'mytest_books_per_page',
	*		'Books per page',
	*		10
	*	);
	*
	*	string	$unique_key			Name in the wpdb->usermeta table.
	*	string	$label
	*	int		$default_value
	*   string  $menu_slug			Menu slug used in add_menu_page() or add_submenu_page().
	*/
	public function __construct( $unique_key, $label, $default_value, $menu_slug ) {

		if (
			is_string( $unique_key ) && $unique_key &&
			is_string( $label ) && $label &&
			is_int( $default_value )
		) {

			$this->_unique_key = $unique_key;
			$this->_label = $label;
			$this->_default_value = $default_value;

			// Validator for saving the options.
			if ( ! empty( $_GET[ 'page' ] ) && $menu_slug === $_GET[ 'page' ] ) {
				add_filter( 'set-screen-option', array( $this,
					'filter__set_screen_option' ), 10, 3
				);
			}

		} else {
			if ( WP_DEBUG ) wp_die( 'CMSO: bad construct' );
		}

	}


	/*
	* Set up after WP does set_current_screen() in 'wp-admin/admin.php'.
	*
	* Call '->load()' in a callback using add_action( "load-{$hook}", callback ),
	* where $hook = add_submenu_page(...).
	* Note, $hook is the same as the global $page_hook.
	*
	*/
	public function load() {
		if ( get_current_screen() ) {

			add_screen_option(
				'per_page',								// Special 'option' in screen.php.
				array(
					'option' => $this->_unique_key,		// Yes, 'option' is confusing.
					'label' => $this->_label,
					'default' => $this->_default_value,
				)
			);

		} else {
			if (WP_DEBUG) wp_die('CMSO: no screen');
		}
	}


	/*
	* Validate Screen Option on update. Called early, then page redirected.
	* See set_screen_options() in 'wp-admin/misc.php'.
	*
	* Note, only set a hook for this function if on the right page (use $_GET[ 'page' ]),
	* to avoid conflicts with other plugins.
	*
	*/
	public function filter__set_screen_option( $status, $option, $value ) {
		if ( $option == $this->_unique_key ) {
			// This class owns the option.
			$value = (int) $value;
			if ( ! ( $value > 0 ) ) {
				$value = $this->_default_value;
			}
			return $value;
		}
		return $status;	// = false
	}
}

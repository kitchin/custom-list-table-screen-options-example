<?php

/*
* Add multiple options to the Screen Options tab in a List Table.
* The user_meta_name should *not* have one of the standard names: 'per_page', 'layout_columns'.
*
* To use this class, create the object early (at the latest, hook 'wp_load'),
* and then call '->load()' in the late hook "load-{$page_hook}".
*
* This class carries its own private data, instead of using add_screen_option().
*
* This class uses get_user_meta(), not get_user_option(),  Note, 'wp-admin/includes/screen.php' uses a mix,
* and 'wp-admin/includes/misc.php' uses update_user_meta(). Networkers can figure that out.
*
* Update processing is still done by set_screen_options() in 'wp-admin/includes/misc.php'.
*
*/
class Ltsoe_Screen_Options_Multiple {

	private $_user_meta_name;			// Name in the wpdb->usermeta table.
	private $_user_meta_options;		// list( option => list( label, default ), ... ).
	private $_user_meta_values;			// list( option => value, ... ).

	/*
	* Set up before WP does set_screen_options() in 'wp-admin/admin.php'.
	* Call 'new' in a callback using add_action( 'wp_loaded', callback ).
	*
	* (Any early action would work: 'plugins_loaded', ..., 'init', 'wp_loaded',
	* but not 'admin_menu', 'admin_init'.)
	*
	* Note the globals $plugin_page, $page_hook are not yet set.
	* Could create the class object only as needed by checking $_GET['page'],
	* which is done below anyway for the update validator.
	*
	* Example:
	*	$foo = new Ltsoe_Screen_Options_Multiple(
	*		'mytest_books',
	*		array(
	*			'books_per_page' => array(
	*				 'label' => 'Books per page',
	*				 'default' => 10,
	*			),
	*			'book_details' => array(
	*				 'label' => 'Show book details',
	*				 'default' => 1,
	*			),
	*		)
	*	);
	*
	*	string	$user_meta_name		Name in the wpdb->usermeta table.
	*	array 	$user_meta_options	list( option => list( label, default ), ... ).
	*   string  $menu_slug			Menu slug used in add_menu_page() or add_submenu_page().
	*/
	public function __construct( $user_meta_name, $user_meta_options, $menu_slug ) {

		if (
			$user = wp_get_current_user() and
			is_string( $user_meta_name ) && $user_meta_name &&
			is_array( $user_meta_options ) && $user_meta_options
		) {

			$this->_user_meta_name = $user_meta_name;
			$this->_user_meta_options = $user_meta_options;
			$this->_user_meta_values = get_user_meta( $user->ID, $this->_user_meta_name, true );
			if ( ! is_array( $this->_user_meta_values ) ) {
				$this->_user_meta_values = array();
			}
			
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
	* Since this class does not use add_screen_option(), the only reason to wait until
	* the "load-{$hook}" action is to prevent adding settings to the wrong pages.
	* If checking $_GET['page'] before creating the object, could call ->load() right away.
	* (Or this code could be moved into __construct() in that case.)
	*
	*/
	public function load() {
		if ( $this->_user_meta_name ) {

			add_filter( 'screen_options_show_screen', '__return_true' );
			// Instead of: add_screen_option( $this->_option, $this->_args );

			// Render to screen options content.
			add_filter( 'screen_settings', array( $this,
				'filter__screen_settings' )		// , 10, 2	// The 2nd parmeter, $screen, is not needed.
			);

		} else {
			if ( WP_DEBUG ) wp_die( 'CMSO: bad load' );
		}
	}


	/*
	* Render to Screen Options string.
	*/
	public function filter__screen_settings( $screen_settings ) { // , $screen ) {
		$out = '';

		foreach ( $this->_user_meta_options as $option => $args ) {

			$label = @$args[ 'label' ] or $label = $option;
			$default = @$args[ 'default' ];
			$type = (string)@$args[ 'type' ];	// future use

			switch ( $type ) {
			default:
				$value = (int) @$this->_user_meta_values[ $option ];
				if ( ! ( $value > 0 ) ) {
					$value = $default;
				}
				$out .= sprintf(
					'<label for="%1$s">%2$s</label>
					<input id="%1$s" name="wp_screen_options[value][%1$s]" value="%3$s"
						type="number"
						class="screen-per-page"
					/>',
					esc_attr( $option),
					$label,
					esc_attr( $value )
					//	step="1" min="1" max="999" maxlength="3"
				);
			}
		}

		if ( $out ) {
			$screen_settings .= sprintf( '
				<div class="screen-options">
				<input type="hidden" name="wp_screen_options[option]" value="%s" />
				%s%s</div>',
				$this->_user_meta_name,
				$out,
				get_submit_button( __( 'Apply' ), 'button', 'screen-options-apply', false )
			);
		}

		return $screen_settings;
	}


	/*
	* Validate Screen Option on update. Called early, then page redirected.
	* See set_screen_options() in 'wp-admin/misc.php'.
	*
	* Note, only set a hook for this function if on the right page (use $_GET[ 'page' ]),
	* to avoid conflicts with other plugins.
	*
	*/
	public function filter__set_screen_option( $status, $option, $values ) {
		if ( $option == $this->_user_meta_name ) {
			// This class owns the option.
			if ( is_array( $values ) ) {
				foreach ( $values as $option => $raw_value ) {
					$value = false;
					if ( $args = @$this->_user_meta_options[ $option ] ) {

						$default = @$args[ 'default' ];
						$type = (string)@$args[ 'type' ];	// future use

						switch ( $type ) {
						default:
							$value = (int) $raw_value;
							if ( ! ( $value > 0 ) ) {
								$value = $default;
							}
							break;
						}
					} else {
						if ( WP_DEBUG ) wp_die("fsso: unknown option='$option'");
					}
					if ( $value !== false ) {
						$this->_user_meta_values[ $option ] = $value;
					}
				}
				return $this->_user_meta_values;
			} else {
				if ( WP_DEBUG ) wp_die('fsso: no input');
			}
		}
		return $status;	// = false
	}
}

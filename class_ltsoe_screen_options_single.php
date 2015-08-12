<?php

/*
* Add a single custom option to the Screen Options tab in a List Table.
* The option should *not* have one of the standard names: 'per_page', 'layout_columns'.
*
* To use this class, create the object early (at the latest, hook 'wp_load'),
* and then call '->load()' in the late hook "load-{$page_hook}".
*
* This class does not handle 'per_page', which should be done using add_screen_option().
* This class carries its own private data, instead of using add_screen_option().
*
* This class uses get_user_option(), not get_user_meta(). Note, 'wp-admin/includes/screen.php' uses a mix,
* and 'wp-admin/includes/misc.php' uses update_user_meta(). Networkers can figure that out.
*
* Update processing is still done by set_screen_options() in 'wp-admin/includes/misc.php'.
*
*/
class Ltsoe_Screen_Options_Single {

	private $_option;		// Name in the wpdb->usermeta table.
	private $_args;			// list( label, default ).


	/*
	* Set up before WP does set_screen_options() in 'wp-admin/admin.php'.
	*
	* Call 'new' for the class early, in the plugin global scope,
	* or hooked to any any early action: 'plugins_loaded', ..., 'init', 'wp_loaded'.
	* These hooks are too late: 'admin_menu', 'admin_init', "load-{$hook}".
	*
	* Note the globals $plugin_page, $page_hook are not yet set.
	* Creating the class object only as needed would require parsing $GET['page'] directly in the callback.
	*
	* Example:
	*	$foo = new Ltsoe_Screen_Options_Single(
	*		'mytest_books_per_page',
	*		array(
	*			'label' => 'Books per page',
	*			'default' => 10,
	*		)
	*	);
	*
	*	string	$option		Name in the wpdb->usermeta table.
	*	array 	$args		list( label, default ).
	*/
	public function __construct( $option, $args ) {

		if (
			is_string( $option ) && $option &&
			is_array( $args ) && $args
		) {

			$this->_option = $option;
			$this->_args = $args;

			// Validator for saving the option.
			add_filter( 'set-screen-option', array( $this,
				'filter__set_screen_option' ), 10, 3
			);

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
	* An alternative would be to parse $_GET['page'] when before creating the object, and then call ->load() right away.
	* (Or this code could be moved into __construct(), if it parsed $_GET['page'].)
	*
	*/
	public function load() {
		if ( get_current_screen() ) {

			add_filter( 'screen_options_show_screen', '__return_true' );
			// Instead of: add_screen_option( $this->_option, $this->_args );

			// Render to screen options content.
			add_filter( 'screen_settings', array( $this,
				'filter__screen_settings' )		// , 10, 2	// The 2nd parmeter, $screen, is not needed.
			);

		} else {
			if (WP_DEBUG) wp_die('CMSO: no screen');
		}
	}


	/*
	* Render to Screen Options string.
	*/
	public function filter__screen_settings( $screen_settings ) { // , $screen ) {

		/////////////////////////////////////////////////////////////////////////////////////////////
		// How it would work using data in $screen, via set_screen_option().
		// Note, local data would still be needed for the validator, see filter__set_screen_option().
		/////////////////////////////////////////////////////////////////////////////////////////////
		// 	if ( $screen and $options = $screen->get_options() ) {
		// 		foreach ( $options as $option => $args ) {					// Single iteration.
		// 			if ( $option == $this->_option ) {
		// 				$label = $args[ 'lable' ]							// Screen data.
		// 				$default = $args[ 'default' ];						// Screen data.
		/////////////////////////////////////////////////////////////////////////////////////////////

		$label = $this->_args[ 'label' ];			// Private local data.
		$default = $this->_args[ 'default' ];		// Private local data.

		$value = (int) get_user_option( $this->_option );
		if ( ! ( $value > 0 ) ) {
			$value = $default;
		}
		$out = sprintf(
			'<label for="%1$s">%2$s</label>
			<input type="hidden" name="wp_screen_options[option]" value="%1$s" />
			<input id="%1$s" name="wp_screen_options[value]" value="%3$s"
				type="number"
				class="screen-per-page"
				step="1" min="1" max="999" maxlength="3"
			/>',
			esc_attr( $this->_option ),
			$label,
			esc_attr( $value )
		);

		$screen_settings .= sprintf( '
			<div class="screen-options">%s%s</div>',
			$out,
			get_submit_button( __( 'Apply' ), 'button', 'screen-options-apply', false )
		);

		return $screen_settings;
	}


	/*
	* Validate Screen Option on update.
	* Called early, then page redirected.
	* See set_screen_options() in 'wp-admin/misc.php'.
	*
	* May be called with a screen option this class does not own.
	* Pass through $value!
	*
	*/
	public function filter__set_screen_option( $status, $option, $value ) {
		if ( $option == $this->_option ) {
			// This class owns the option.
			$value = (int) $value;
			if ( ! ( $value > 0 ) ) {
				$value = $this->_args[ 'default' ];
			}
		} else {
			// Only for testing, need to pass through:
			// if ( WP_DEBUG ) wp_die('xx/fsso: not my job');	// xx comment out this line after testing
		}

		return $value;
	}
}

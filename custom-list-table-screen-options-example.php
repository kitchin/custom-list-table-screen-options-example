<?php
/*
Plugin Name: Custom List Table Screen Options Example
Plugin URI: https://github.com/kitchin/custom-list-table-screen-options-example
Description: Sample code for showing the Screen Options tab. Requires the plugin Custom List Table Example (ver. 1.3 works, WP 4.2.4). Adds subpages to the menu item List Table Example. See source for details.
Version: 1.3.1
Author: Kitchin
License: GPL2
*/

/*
* Readme, 2015/08/11.
*
* See the included classes for an explanation of the hook timing involved in WP Screen Options.
*
* This plugin shows four examples:
* 	1. Using hooks at the right time, WP shows Screen Options for columns automatically.
*	2. Add 'per_page' to the Screen Options. WP has automatic code for this as well.
*	3. Add a single custom option to the Screen Options. WP has less code for this.
*	4. Add multiple custom options to the Screen Options. Not much harder.
*
* This plugin saves options but does not use them.
* For example, per_page will NOT change the rows displayed per page.
*
*/

/*
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

global $ltsoe_ListTables;
$ltsoe_ListTables = array();

global $ltsoe_screen_options;
$ltsoe_screen_options = array();


if ( is_admin() ) {
	add_action( 'plugins_loaded', 'ltsoe_action__plugins_loaded' );
	add_action( 'admin_menu', 'ltsoe_action__admin_menu', 20 );
}


/*
* Create Screen Options handler objects early so updates will get processed.
* See classes for more details.
*/
function ltsoe_action__plugins_loaded() {
	global $ltsoe_screen_options;

	if ( ! class_exists( 'TT_Example_List_Table' ) ) {
		// Missing plugin Custom List Table Example?
		return;
	}
	

	// 1. Using hooks at the right time, WP shows Screen Options for columns automatically.
	$ltsoe_screen_options[ 'columns_only' ] = '';

	// 2. Add 'per_page' to the Screen Options. WP has automatic code for this as well.
	require_once( dirname(__FILE__) . '/class_ltsoe_screen_options_per_page.php' );
	$menu_slug = 'ltsoe_tt_list_test_per_page';
	$ltsoe_screen_options[ 'per_page' ] = new Ltsoe_Screen_Options_Per_Page(
		'ltsoe_books_per_page',
		'Books per page',
		10,
		$menu_slug
	);

	// 3. Add a single custom option to the Screen Options. WP has less code for this.
	require_once( dirname(__FILE__) . '/class_ltsoe_screen_options_single.php' );
	$menu_slug = 'ltsoe_tt_list_test_single';
	$ltsoe_screen_options[ 'single' ] = new Ltsoe_Screen_Options_Single(
		'ltsoe_books_single',
		array(
			'label' => 'Books single',
			'default' => 10,
		),
		$menu_slug
	);

	// 4. Add multiple custom options to the Screen Options. Not much harder.
	require_once( dirname(__FILE__) . '/class_ltsoe_screen_options_multiple.php' );
	$menu_slug = 'ltsoe_tt_list_test_multiple';
	$ltsoe_screen_options[ 'multiple' ] = new Ltsoe_Screen_Options_Multiple(
		'ltsoe_books_multiple',
		array(
			'books_per_page' => array(
			 'label' => 'Books multiple',
				 'default' => 10,
			),
			'book_details' => array(
				 'label' => 'Show book details',
				 'default' => 1,
			),
		),
		$menu_slug
	);
}


/*
* Add submenu pages to the main menu item for plugin Custom List Table Example.
* Add a late hook specific to each new page.
*/
function ltsoe_action__admin_menu() {
	global $ltsoe_screen_options;
	if ( ! class_exists( 'TT_Example_List_Table' ) ) {
		if ( WP_DEBUG ) print "<p style='text-align: center;'>Custom List Table Screen Options Example: missing plugin Custom List Table Example?</p>\n";
		return;
	}
	foreach ( $ltsoe_screen_options as $key => $ltsoe_screen_option ) {
		$hook = add_submenu_page(
			'tt_list_test',							// parent_slug	// plugin Custom List Table Example
			"Test $key option",						// page_title
			"Test $key option",						// menu_title
			'activate_plugins',						// capability
			"ltsoe_tt_list_test_$key",				// menu_slug
			'ltsoe_tt_render_list_page'				// callback
		);
		add_action( "load-{$hook}", 'ltsoe_action__load_hook' );
	}
}


/*
* Set up screen options before the page loads.
* Also must create the List Table object by now for the Screen Options to appear.
*
* Only fires if one of this plugin's pages is loading.
* Detects which page by taking a substring of $plugin_page.
*
* See classes for details on why using a late hook.
*/
function ltsoe_action__load_hook() {
	global $plugin_page;	// WP global
	global $ltsoe_screen_options;
	global $ltsoe_testListTables;

	// $plugin page example: ltsoe_tt_list_test_per_page
	$key = substr( $plugin_page, strlen( 'ltsoe_tt_list_test_' ) );

	if ( get_current_screen() ) {

		if ( $ltsoe_screen_options[ $key ] ) {
			// $key is 'per_page', 'single', 'multiple':
			//	The class can now do its work to prepare the screen.
			$ltsoe_screen_options[ $key ]->load();
		}
		// else
			// $key is 'columns_only':
			// Creating the List Table object here suffices to show the Screen Options tab,
			// with automatic columns checkboxes.

		$ltsoe_testListTables[ $key ] = new TT_Example_List_Table();

	}
}


/*
* Render a page for this plugin.
* Detects which page by taking a substring of $plugin_page.
* Last callback to execute.
*
* Slight variation on the function tt_render_list_page() in Custom List Table Example.
*/
function ltsoe_tt_render_list_page() {
	global $plugin_page;	// WP global
	global $ltsoe_testListTables;

	// $plugin page example: ltsoe_tt_list_test_per_page
	$key = substr( $plugin_page, strlen( 'ltsoe_tt_list_test_' ) );
	$testListTable = $ltsoe_testListTables[ $key ];

    //Create an instance of our package class...
    // $testListTable = new TT_Example_List_Table();
    //Fetch, prepare, sort, and filter our data...
    $testListTable->prepare_items();

    ?>
    <div class="wrap">

        <div id="icon-users" class="icon32"><br/></div>
        <h2>List Table Test + Screen Options, <?php echo $key; ?></h2>

        <div style="background:#ECECEC;border:1px solid #CCC;padding:0 10px;margin-top:5px;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;">
            <p>This page demonstrates the use of the <tt><a href="http://codex.wordpress.org/Class_Reference/WP_List_Table" target="_blank" style="text-decoration:none;">WP_List_Table</a></tt> class in plugins.</p>
            <p>For a detailed explanation of using the <tt><a href="http://codex.wordpress.org/Class_Reference/WP_List_Table" target="_blank" style="text-decoration:none;">WP_List_Table</a></tt>
            class in your own plugins, you can view this file <a href="<?php echo admin_url( 'plugin-editor.php?plugin='.plugin_basename(__FILE__) ); ?>" style="text-decoration:none;">in the Plugin Editor</a> or simply open <tt style="color:gray;"><?php echo __FILE__ ?></tt> in the PHP editor of your choice.</p>
            <p>Additional class details are available on the <a href="http://codex.wordpress.org/Class_Reference/WP_List_Table" target="_blank" style="text-decoration:none;">WordPress Codex</a>.</p>
        </div>

        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="movies-filter" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <!-- Now we can render the completed list table -->
            <?php
				// $testListTable->display();
				$testListTable->display();
			?>
        </form>

    </div>
    <?php
}

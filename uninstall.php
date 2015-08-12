<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

delete_metadata( 'user', 0, 'ltsoe_books_per_page', '', true );
delete_metadata( 'user', 0, 'ltsoe_books_single', '', true );
delete_metadata( 'user', 0, 'ltsoe_books_multiple', '', true );

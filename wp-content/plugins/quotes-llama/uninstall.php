<?php

// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

// If options still exist.
if ( get_option( 'quotes-llama-settings' ) ) {
	delete_option( "quotes-llama-settings" );
	unregister_setting( 'quotes-llama-settings', 'quotes-llama-settings' );
}

?>
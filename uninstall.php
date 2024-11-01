<?php
/**
 * Uninstalls the options for this plugin when an uninstall has been requested
 * from the WordPress admin
 *
 * @package plugin-update-ignore
 * @subpackage uninstall
 * @since 1.0
 */

// If uninstall/delete not called from WordPress then exit
if( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit ();

// Delete option from options table
delete_option( 'plugin_update_ignore' );
delete_option( 'pui_params' );
?>

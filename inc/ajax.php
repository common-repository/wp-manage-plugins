<?php
/**
 * Performs required Ajax operations and hooks
 *
 * Moved to it's own file since this is only needed during Ajax calls
 *
 * @author Matt Martz <matt@sivel.net>
 * @package plugin-update-ignore
 * @subpackage uninstall
 * @since 1.0
 */
class PluginUpdateIgnoreAjax extends PluginUpdateIgnore {

	/**
	 * PHP4 style constructor.
	 *
	 * Calls the below PHP5 style constructor.
	 *
	 * @author Matt Martz <matt@sivel.net>
	 * @since 1.0
	 * @return none
	 */
	function PluginUpdateIgnoreAjax() {
		$this->__construct();
	}

	/**
	 * PHP5 style contructor
	 *
	 * Hooks into all of the necessary WordPress actions and filters needed
	 * for this plugin to function
	 *
	 * @author Matt Martz <matt@sivel.net>
	 * @since 1.0
	 * @return none
	 */
	function __construct() {
		PluginUpdateIgnore::__construct();
		add_action('wp_ajax_pluginupdateignoreswitch', array(&$this, 'ignore_unignore'));
		add_action('wp_ajax_pluginupdateignoreupdaterow', array(&$this, 'update_wp_plugin_update_row'));
	}

	/**
	 * Returns a JSON representation of a value
	 *
	 * Uses the JSON class included with tinymce if json_encode is not present
	 *
	 * @author Matt Martz <matt@sivel.net>
	 * @since 1.0
	 * @param mixed $value value to retrieve JSON representation of
	 * @return string JSON representation of value
	 */
	function json_encode($value) {
		if ( function_exists('json_encode') ) {
			return json_encode($value);
		} else {
			include(ABSPATH . WPINC . '/js/tinymce/plugins/spellchecker/classes/utils/JSON.php');
			$json = new Moxiecode_JSON();
			return $json->encode($value);
		}
	}

	/**
	 * Switch between ignore and unignore when requested
	 *
	 * @author Matt Martz <matt@sivel.net>
	 * @since 1.0
	 * @return none
	 */
	function ignore_unignore() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce']) ) {
			$plugin = $_GET['plugin'];
			if ( isset($this->ignores[$plugin]) ) {
				unset($this->ignores[$plugin]);
				$status = 0;
			} else {
				$this->ignores[$plugin] = true;
				$status = 1;
			}
			update_option('plugin_update_ignore', $this->ignores);
			//////////// Maybe add some sort of check and return 0 on failure?
			// If we didn't get here by JS then redirect back to plugins.php, if _wp_http_referer is set then this was not an Ajax call
			if ( !empty($_REQUEST['_wp_http_referer']) )
				wp_redirect($_REQUEST['_wp_http_referer']);
			else
				echo $this->json_encode(array('response' => 1, 'status' => $status, 'plugin' => $plugin));
		}
		die();
	}

	/**
	 * Retrieve the output from wp_plugin_update_row on ajax call
	 *
	 * @author Matt Martz <matt@sivel.net>
	 * @since 1.0
	 * @return none
	 */
	function update_wp_plugin_update_row() {
		if ( current_user_can('update_plugins') && wp_verify_nonce($_GET['_wpnonce']) ) {
			$plugin = $_GET['plugin'];
			include(dirname(__FILE__) . '/admin.php');
			$PluginUpdateIgnoreAdmin = new PluginUpdateIgnoreAdmin();
			$PluginUpdateIgnoreAdmin->wp_plugin_update_row($plugin, get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin));
		}
		die();
	}

}
?>

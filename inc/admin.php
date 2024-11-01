<?php
/**
 * Performs necessary hooks to get things going in the admin
 *
 * @author Matt Martz <matt@sivel.net>
 * @author Brian Messenlehner <brian@webdevstudios.com>
 * @package plugin-update-ignore
 * @subpackage admin
 * @since 1.0
 */

$arrPluginCount = array();
 
class PluginUpdateIgnoreAdmin extends PluginUpdateIgnore {

	/**
	 * PHP4 style constructor.
	 *
	 * Calls the below PHP5 style constructor.
	 *
	 * @author Matt Martz <matt@sivel.net>
	 * @since 1.0
	 * @return none
	 */
	function PluginUpdateIgnoreAdmin() {
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
		add_action('admin_menu', array(&$this, 'add_options_page')) ;
		register_activation_hook($this->plugin_file, array(&$this, 'activation'));
		load_plugin_textdomain('plugin-update-ignore', false, $this->plugin_dir_basename . '/localization');
	}

	/**
	 * Action hook callback for activation
	 *
	 * Initializes the plugin for first time use
	 *
	 * @author Matt Martz <matt@sivel.net>
	 * @since 1.0
	 * @return none
	 */
	function activation() {
		if ( ! is_array($this->ignores) )
			add_option('plugin_update_ignore', array());
	}

	/**
	 * Action hook callback to filter the plugin action links
	 *
	 * @author Matt Martz <matt@sivel.net>
	 * @since 1.0
	 * @return none
	 */
	function add_options_page() {
		if ( current_user_can('update_plugins') ) {
			
			//BRM exit plugin pages and hide plugins menu from the sidebar
			$options_arr = get_option('pui_params');
			$pui_lock = $options_arr["pui_lock"];
			$pui_locked_user = $options_arr["pui_locked_user"];
			global $current_user;
			get_currentuserinfo();
			$userid=$current_user->ID;
			if ($pui_lock == "on" && $pui_locked_user!=$userid){ 
				if (strrpos($_SERVER["REQUEST_URI"],"plugins.php")>0 || strrpos($_SERVER["REQUEST_URI"],"plugin-install.php")>0 || strrpos($_SERVER["REQUEST_URI"],"plugin-editor.php")>0) { 
					echo '<div id="message" class="error"><p>You do not have access to manage plugins.</p></div>';
					exit();
				} ?>
            	<style>
                   #menu-plugins{
                        display:none;
                    }
                </style>
			<?php }
			$pui_email_alert = $options_arr["pui_email_alert"];
			if ($pui_email_alert == "on"){
				if (strrpos($_SERVER["REQUEST_URI"],"plugins.php?action=")>0 || strrpos($_SERVER["REQUEST_URI"],"admin-ajax.php?action=")>0 ){
					$action=$_GET['action'];
					$plugin=$_GET['plugin'];
					$username=$current_user->display_name;
					$subject="Plugin Update Alert: ".$plugin; 
					$message="\n User: ".$username."\n Action: ".$action."\n Plugin: ".$plugin."\n DateTime: ".date('l jS \of F Y h:i:s A') ;
					$admin_email = get_option('admin_email');
					wp_mail($admin_email, $subject, $message);
				}
			}
			// Add the call to create a new plugin submenu here
			add_filter("plugin_action_links" ,array(&$this, 'filter_plugin_actions'), 10, 2);
		}
	}

	/**
	 * Action hook callback to populate update message show in below each plugin
	 * requiring an update on plugins.php
	 *
	 * @author Matt Martz <matt@sivel.net>
	 * @since 1.0
	 * @param string $file plugin_basename of the current plugin the action was called for
	 * @param array $plugin_data array of plugin information of the current plugin the action was called for
	 * @return none
	 */
	function wp_plugin_update_row($file, $plugin_data) {
		
		global $arrPluginCount;

		//load plugin options
		$options_arr = get_option('pui_params');
		$pui_display_msg = $options_arr["pui_display_msg"];
		
		$current = get_transient('update_plugins');
		if ( !isset($current->response[ $file ]) )
			return false;

		if ( isset($this->ignores[$file]) ) {
			$r = $current->response[ $file ];
			$plugins_allowedtags = array('a' => array('href' => array(),'title' => array()),'abbr' => array('title' => array()),'acronym' => array('title' => array()),'code' => array(),'em' => array(),'strong' => array());
			$plugin_name = wp_kses( $plugin_data['Name'], $plugins_allowedtags );

			$details_url = admin_url('plugin-install.php?tab=plugin-information&plugin=' . $r->slug . '&TB_iframe=true&width=600&height=800');
			
			$arrPluginCount[] = $plugin_name;

			//check if option is set to show update notices
			If ($pui_display_msg != "on") {
				echo '<tr class="plugin-update-tr"><td colspan="3" class="plugin-update"><div class="update-message-ignore">';
				printf( __('The ability to update this plugin by automatic means has been disabled by the site administrator.  However, it is reported that there is a new version of %1$s available.  <a href="%2$s" class="thickbox" title="%3$s">View version %4$s Details</a>.', 'plugin-update-ignore'), $plugin_name, esc_url($details_url), esc_attr($plugin_name), $r->new_version );
			}

			do_action( "in_plugin_update_message-$file", $plugin_data, $r );

			echo '</div></td></tr>';
			
		} else {
			// If we are using WP < 2.9 we are replacing the after_plugin_row action for all plugins, so call the built in WP function, to keep from showing our custom message.
			wp_plugin_update_row($file, $plugin_data);
		}
	}

	/**
	 * Filter hook callback to insert and modify plugin action links
	 *
	 * @author Matt Martz <matt@sivel.net>
	 * @since 1.0
	 * @param array $links array of current action links to be filtered
	 * @param string $file plugin_basename of the current plugin the filter was called for
	 * @return array array of plugin action links
	 */
	function filter_plugin_actions($links, $file) {
		global $wp_version;
		// Remove the core update message action callback and use a custom one if this plugin is ignoring updates
		if ( isset($this->ignores[$file]) ) {
			if ( version_compare('2.9', preg_replace('/[a-z-]+/i', '', $wp_version), '<=') ) {
				remove_action("after_plugin_row_$file", 'wp_plugin_update_row', 10, 2);
				add_action("after_plugin_row_$file", array(&$this, 'wp_plugin_update_row'), 10, 2);
			} else {
				remove_action('after_plugin_row', 'wp_plugin_update_row', 10, 2);
				add_action('after_plugin_row', array(&$this, 'wp_plugin_update_row'), 10, 2);
			}

		}
		//print_r ($links);
		$current = get_transient('update_plugins');
		$update_class = isset($current->response[ $file ]) ? 'update' : 'noupdate';
		$class = str_replace(array('/','.'), '-', $file);
		$linktext = isset($this->ignores[$file]) ? __('Un-Ignore Updates') : __('Ignore Updates');
		// The href here is only good for non JS calls, when we add an AJAX call we need to not include _wp_http_referer,
		// this is so we can degrade to nicely if JS isn't available
		$links[] = "<img class='{$class}-spin hidden' src='" . admin_url('images/wpspin_light.gif') . "' alt='" . __('Loading...', 'ajax-plugin-helper') . "' /><a class='plugin-update-ignore {$class}-switch {$update_class}' href='" . admin_url('admin-ajax.php?action=pluginupdateignoreswitch&plugin=' . $file . '&_wpnonce=' . wp_create_nonce() . "&_wp_http_referer=" . urlencode(stripslashes($_SERVER['REQUEST_URI']))) . "' rel='$file'>$linktext</a>";
		return $links;
	}

}

add_action('admin_footer', 'wds_plugin_count_add_js', 0);
function wds_plugin_count_add_js() {
    global $arrPluginCount;
    $update_plugins = get_transient( 'update_plugins' );
    $update_count = 0;
    if ( !empty($update_plugins->response) ) {
      $update_count = count( $update_plugins->response );
    }
	//BRM loop through $update_plugins pull out keys strip to 1st/ match against new array count_arr2 built from keys of count_arr  striped to 1st /
	$count_arr = get_option('plugin_update_ignore');
	foreach ($count_arr as $key => $value) {
		$length=strpos($key,"/");
		$result=substr($key,$start,$length);
		$count_arr2[]=$result;
	}
	foreach ($update_plugins as $v) {
    	$i=$i+1;
		$sub_arr=$v;
		if($i==3){
			foreach ($v as $key => $value) {
				$length=strpos($key,"/");
				$result=substr($key,$start,$length);
				if (in_array($result, $count_arr2)) {
					$updates_4_locked=$updates_4_locked+1;
				}
			}
		}
	}
	$locked_count=$updates_4_locked;
    $total_update_count = $update_count - $locked_count;
    $js = array();
    if ($total_update_count <= 0) {
      $js[] = "\$('.plugin-count').remove();";
    } else if ($total_update_count > 0) {
      $js[] = "\$('.plugin-count').html(" . $total_update_count . ");";
    }
    if ( !empty($js) ) {
      $js = implode("\n", $js);
      echo '<script type="text/javascript">jQuery(document).ready(function($) { {'.$js.'} });</script>';
    }
}
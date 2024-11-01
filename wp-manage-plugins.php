<?php
/**
 * Ignore Selected Plugins For Upgrade
 *
 * Adds advanced plugin management options to WordPress
 *
 * @author Matt Martz <matt@sivel.net>
 * @author Brad Williams <brad@webdevstudios.com>
 * @author Brian Messenlehner <brian@webdevstudios.com>
 * @author Scott Basgaard <scott@webdevstudios.com>
 * @version 1.0
 * @package wp-manage-plugins
 */
/*
Plugin Name: WP-Manage-Plugins
Plugin URI: http://webdevstudios.com/support/wordpress-plugins/wp-manage-plugins/
Description: Ignore selected plugins from upgrade, hide & lock plugins page and settings page, email alert admin when plugins added/activated/deactivated/deleted
Author: Matt Martz, Brad Williams, Brian Messenlehner, Scott Basgaard
Author URI: http://webdevstudios.com/support/wordpress-plugins/
Version: 1.0

		Copyright (c) 2009 Matt Martz (http://sivel.net)
		Plugin Ignore is released under the GNU General Public License (GPL)
		http://www.gnu.org/licenses/gpl-2.0.txt
*/

class PluginUpdateIgnore {

	/**
	 * String holding the full file system path of the main plugin file for
	 * use in the other included files
	 *
	 * @since 1.0
	 * @var string
	 */
	var $plugin_file;

	/**
	 * String holding the plugin basename of the main plugin file for
	 * use in the other included files
	 *
	 * @since 1.0
	 * @var string
	 */
	var $plugin_file_basename;

	/**
	 * String holding the plugin basename of the directory containing
	 * the main plugin file for use in the other included files
	 *
	 * @since 1.0
	 * @var string
	 */
	var $plugin_dir_basename;

	/**
	 * Array holding the plugins which are being ignored for updates
	 *
	 * @since 1.0
	 * @var array
	 */
	var $ignores;

	/**
	 * PHP4 style constructor.
	 *
	 * Calls the below PHP5 style constructor.
	 *
	 * @author Matt Martz <matt@sivel.net>
	 * @since 1.0
	 * @return none
	 */
	function PluginUpdateIgnore() {
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
		$this->plugin_file = __FILE__;
		$this->plugin_file_basename = plugin_basename(__FILE__);
		$this->plugin_dir_basename = dirname(__FILE__);
		$this->ignores = get_option('plugin_update_ignore');
	}
}

/**
 * Hook into init so we don't perform any operations too soon, and check
 * that we are in the admin so that we are only ever even loading the
 * majority of this file in the admin
 *
 * @author Matt Martz <matt@sivel.net>
 * @since 1.0
 */
add_action('init', 'plugin_update_ignore_init');
function plugin_update_ignore_init() {
	if ( is_admin() ) {
		global $pagenow;
		if ( defined('DOING_AJAX') && DOING_AJAX === true ) {
			include('inc/ajax.php');
			$PluginUpdateIgnoreAjax = new PluginUpdateIgnoreAjax();
		} else {
			include('inc/admin.php');
			$PluginUpdateIgnoreAdmin = new PluginUpdateIgnoreAdmin();
			if ( isset($pagenow) && $pagenow == 'plugins.php' ) {
				include('inc/jscss.php');
				$ajaxPluginHelperJsCss = new PluginUpdateIgnoreJsCss();
			}
		}
	}
}

//hook for adding admin menu
add_action('admin_menu', 'pui_menu');
function pui_menu() {
  	//check if menu is hidden
	$options_arr = get_option('pui_params');
	global $current_user;
    get_currentuserinfo();
	$userid=$current_user->ID;
	$pui_hidden_user = $options_arr["pui_hidden_user"];
	if ($pui_hidden_user==0 || $pui_hidden_user==$userid){
  		add_options_page('WP Manage Plugins Options', 'WP Manage Plugins', 8, __FILE__, 'pui_options');
	}
}

function pui_update_options() {
	check_admin_referer('pui_check');

	global $current_user;
    get_currentuserinfo();
	$userid=$current_user->ID;
	$pui_hidden_user=0;
	if($_POST['pui_hide']=="on"){
		$pui_hidden_user=$userid;
	}
	$pui_locked_user=0;
	if($_POST['pui_lock']=="on"){
		$pui_locked_user=$userid;
		$pui_hidden_user=$userid;
	}

	//create array for storing option values
	$pui_settings_arr=array(
		"pui_display_msg"=>esc_attr($_POST['pui_display_msg']),
		"pui_lock"=>esc_attr($_POST['pui_lock']),
		"pui_locked_user"=>esc_attr($pui_locked_user),
		"pui_hide"=>esc_attr($_POST['pui_hide']),
		"pui_hidden_user"=>esc_attr($pui_hidden_user),
		"pui_email_alert"=>esc_attr($_POST['pui_email_alert']),
		);

	//save array as option
	update_option('pui_params', $pui_settings_arr);
}

function pui_options() {
	global $current_user;

	$options_arr = get_option('pui_params');

	//check if locked is on and if the user that locked it matches user logged in
    get_currentuserinfo();
	$userid=$current_user->ID;
	$pui_lock = $options_arr["pui_lock"];
	$pui_locked_user = $options_arr["pui_locked_user"];
	$pui_hide = $options_arr["pui_hide"];
	$pui_hidden_user = $options_arr["pui_hidden_user"];
	if ($pui_lock == "on" && $pui_locked_user!=$userid || $pui_hide == "on" && $pui_hidden_user!=$userid){
  		echo '<div id="message" class="error"><p>Sorry but you to not have access to manage plugins.</p></div>';
	}else{

		if ( isset($_POST['update_pui_options'])
			&& $_POST['update_pui_options']
			)
		{
			pui_update_options();

			echo "<div class=\"updated\">\n"
				. "<p>"
					. "<strong>"
					. __('Settings saved.')
					. "</strong>"
				. "</p>\n"
				. "</div>\n";
		}

		$options_arr = get_option('pui_params');
		$pui_display_msg = $options_arr["pui_display_msg"];
		If ($pui_display_msg == "on") {
			$pui_display_msg = "CHECKED";
		}
		$pui_lock = $options_arr["pui_lock"];
		if ($pui_lock == "on") {
			$pui_lock = "CHECKED";
		}
		$pui_hide = $options_arr["pui_hide"];
		if ($pui_hide == "on") {
			$pui_hide = "CHECKED";
		}
		$pui_email_alert = $options_arr["pui_email_alert"];
		if ($pui_email_alert == "on") {
			$pui_email_alert = "CHECKED";
		}

		echo '<div class="wrap">';
		echo '<h2>' . __('WP Manage Plugins Settings') . '</h2>';
		echo '<form method="post" action="">';
		echo '<table>';
			if ( function_exists('wp_nonce_field') ) wp_nonce_field('pui_check');
			echo '<input type="hidden" name="update_pui_options" value="1">';
			echo '<tr>';
			echo '<td><input type="checkbox" name="pui_display_msg" ' .$pui_display_msg .'></td><td colspan="2">Hide update notices on ignored plugins?</td>';
			echo '</tr>';
			//BRM ?>
			<tr>
				<td><input type="checkbox" name="pui_lock" <?php echo $pui_lock; ?>></td><td>Lock plugins page and this page from all users except me?</td>
			</tr>
			<tr>
				<td><input type="checkbox" name="pui_hide" <?php echo $pui_hide; ?>></td><td>Hide this page from all users except me?</td>
			</tr>
			<tr>
				<td><input type="checkbox" name="pui_email_alert" <?php echo $pui_email_alert; ?>></td><td>Send email alerts to the site admin if someone adds/updates/deletes any plugins?</td>
			</tr>
			<tr>
				<td colspan="3"><input type="submit" name="pui_save" value="Save Settings"></td>
			</tr>
		<?php
		echo '</table>';
		echo '</form>';
		echo 'Follow us on Twitter!  <a href="http://twitter.com/sivel">Matt Martz</a> | <a href="http://twitter.com/williamsba">Brad Williams</a> | <a href="http://twitter.com/bmess">Brian Messenlehner</a> | <a href="http://twitter.com/scottbasgaard">Scott Basgaard</a>';
		echo '</div>';
	}
}
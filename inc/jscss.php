<?php
/**
 * All of the JS and CSS hooks and code
 *
 * Moved to it's own file for better organization
 *
 * @author Matt Martz <matt@sivel.net>
 * @package plugin-update-ignore
 * @subpackage uninstall
 * @since 1.0
 */
class PluginUpdateIgnoreJsCss extends PluginUpdateIgnore {

	/**
	 * PHP4 style constructor.
	 *
	 * Calls the below PHP5 style constructor.
	 *
	 * @author Matt Martz <matt@sivel.net>
	 * @since 1.0
	 * @return none
	 */
	function PluginUpdateIgnoreJsCss() {
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
		add_action('admin_head-plugins.php', array(&$this, 'admin_jquery'));
		add_action('admin_head-plugins.php', array(&$this, 'admin_css'));
		add_action('admin_footer-plugins.php', array(&$this, 'admin_js'));
	}

	/**
	 * Enqueue jQuery so this plugin can use it
	 *
	 * @author Matt Martz <matt@sivel.net>
	 * @since 1.0
	 * @return none
	 */
	function admin_jquery() {
		wp_enqueue_script('jquery');
	}

	/**
	 * Output CSS required by this plugin to head
	 *
	 * @todo should we move this to a separate file and use wp_enqueue_style?
	 *
	 * @author Matt Martz <matt@sivel.net>
	 * @since 1.0
	 * @return none
	 */
	function admin_css() {
?>
<style type="text/css">
	.plugin-update-tr .update-message-ignore {
		margin: 5px;
		padding: 3px 5px;
		border-width: 1px;
		border-style: solid;
		-moz-border-radius: 5px;
		-khtml-border-radius: 5px;
		-webkit-border-radius: 5px;
		border-radius: 5px;
		background-color: #ffebe8;
		border-color: #c00;
		font-weight: bold;
	}
	.error a {
		color: #c00;
	}
</style>
<?php
	}

	/**
	 * Echo the JS required for this plugin to work
	 *
	 * @author Matt Martz <matt@sivel.net>
	 * @since 1.0
	 * @return none
	 */
	function admin_js() {
?>
<script type="text/javascript">
/* <![CDATA[ */
	(function($) {
		$.fn.quadParent = function() {
			return this.parent().parent().parent().parent();
		}
		$.fn.triParent = function() {
			return this.parent().parent().parent();
		}
	})(jQuery);
	var pluginSwitch = function(data) {
		var baseclass = '.' + data.plugin.replace(/\//g,'-').replace(/\./g,'-');
		jQuery(baseclass + '-spin').addClass('hidden');
		if (data.response == 1) {
			if (data.status == 1) {
				jQuery(baseclass + '-switch').text('<?php _e('Un-Ignore Updates'); ?>');
			} else {
				jQuery(baseclass + '-switch').text('<?php _e('Ignore Updates'); ?>');
			}
			if (jQuery(baseclass + '-switch').hasClass('update')) {
				jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', {action: 'pluginupdateignoreupdaterow', plugin: data.plugin, _wpnonce: '<?php echo wp_create_nonce(); ?>'}, 
					function(data) {
						jQuery(jQuery(baseclass + '-switch').quadParent().next()).replaceWith(data);
					}, 
				'text');
			}
		}
		jQuery(baseclass + '-switch').removeClass('hidden');
	}
	jQuery(document).ready(function() {
		jQuery('.plugin-update-ignore').click(function() {
			var plugin = jQuery(this).attr('rel');
			var baseclass = '.' + plugin.replace(/\//g,'-').replace(/\./g,'-');
			jQuery(baseclass + '-spin').removeClass('hidden');
			jQuery(baseclass + '-switch').addClass('hidden');
			jQuery.get('<?php echo admin_url('admin-ajax.php'); ?>', {action: 'pluginupdateignoreswitch', plugin: plugin, _wpnonce: '<?php echo wp_create_nonce(); ?>'}, pluginSwitch, 'json');
			return false;			
		});
	});
/* ]]> */
</script>
<?php
	}

}
?>

<?php
/*
License: GPLv3
License URI: http://surniaulula.com/wp-content/plugins/nextgen-facebook/license/gpl.txt
Copyright 2012-2013 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'Sorry, you cannot call this webpage directly.' );

if ( ! class_exists( 'ngfbSettingsAdvanced' ) && class_exists( 'ngfbAdmin' ) ) {

	class ngfbSettingsAdvanced extends ngfbAdmin {

		protected $ngfb;
		protected $menu_id;
		protected $menu_name;
		protected $pagehook;

		// executed by ngfbSettingsAdvancedPro() as well
		public function __construct( &$ngfb_plugin, $id, $name ) {
			$this->ngfb =& $ngfb_plugin;
			$this->ngfb->debug->mark();
			$this->menu_id = $id;
			$this->menu_name = $name;
		}

		protected function add_meta_boxes() {
			// add_meta_box( $id, $title, $callback, $post_type, $context, $priority, $callback_args );
			add_meta_box( $this->pagehook . '_plugin', 'Plugin Settings', array( &$this, 'show_metabox_plugin' ), $this->pagehook, 'normal' );
			add_meta_box( $this->pagehook . '_contact', 'Contact Methods', array( &$this, 'show_metabox_contact' ), $this->pagehook, 'normal' );
		}

		public function show_metabox_plugin() {
			$show_tabs = array( 
				'activation' => 'Activate and Update',
				'content' => 'Content and Filters',
				'cache' => 'File and Object Cache',
				'shorten' => 'URL Shortening',
				'rewrite' => 'URL Rewrite',
			);
			$tab_rows = array();
			foreach ( $show_tabs as $key => $title )
				$tab_rows[$key] = $this->get_rows( $key );
			$this->ngfb->util->do_tabs( 'plugin', $show_tabs, $tab_rows );
		}

		public function show_metabox_contact() {
			$show_tabs = array( 
				'custom' => 'Custom Contacts',
				'builtin' => 'Built-In Contacts',
			);
			$tab_rows = array();
			foreach ( $show_tabs as $key => $title )
				$tab_rows[$key] = $this->get_rows( $key );
			$this->ngfb->util->do_tabs( 'cm', $show_tabs, $tab_rows );
		}

		protected function get_pre_activation() {
			return array(
				$this->ngfb->util->th( 'Authentication ID', 'highlight', null, '
				After purchasing of the Pro version, an email will be sent to you with an Authentication ID and installation instructions.
				Enter your unique Authentication ID here, and after saving the changes, an update for \'' . $this->ngfb->fullname . '\' 
				will appear on the <a href="' . get_admin_url( null, 'update-core.php' ) . '">WordPress Updates</a> page. 
				Update the \'' . $this->ngfb->fullname . '\' plugin to download and activate the new Pro version.' ) .
				'<td class="blank">' . $this->ngfb->admin->form->get_input( 'ngfb_pro_tid' ) . '</td>',
			);
		}

		protected function get_more_content() {
			$add_to_checkboxes = '';
			foreach ( get_post_types( array( 'show_ui' => true, 'public' => true ), 'objects' ) as $post_type )
				$add_to_checkboxes .= '<p>' . $this->ngfb->admin->form->get_hidden( 'ngfb_add_to_'.$post_type->name ) . 
					$this->ngfb->admin->form->get_fake_checkbox( $this->ngfb->options['ngfb_add_to_'.$post_type->name] ) . ' ' . 
					$post_type->label . '</p>';

			return array(
				'<td colspan="2" align="center">' . $this->ngfb->msg->get( 'pro_feature' ) . '</td>',

				$this->ngfb->util->th( 'Show Custom Settings on', null, null, 
				'The Custom Settings metabox, which allows you to enter custom Open Graph values (among other options), 
				is available on the Posts, Pages, Media, and most custom post type admin pages by default. 
				If your theme (or another plugin) supports additional custom post types, and you would like to 
				<em>exclude</em> the Custom Settings metabox from these admin pages, uncheck the appropriate options here.' ) . 
				'<td class="blank">' . $add_to_checkboxes . '</td>',
			);
		}

		protected function get_more_cache() {
			return array(
				'<td colspan="2" align="center">' . $this->ngfb->msg->get( 'pro_feature' ) . '</td>',

				$this->ngfb->util->th( 'File Cache Expiry', 'highlight', null, 
				$this->ngfb->fullname . ' can save social sharing images and JavaScript to a cache folder, 
				providing URLs to these cached files instead of the originals. 
				A value of \'0\' hours (the default) disables this feature. 
				If your hosting infrastructure performs reasonably well, this option can improve page load times significantly.
				All social sharing images and javascripts will be cached, except for the Facebook JavaScript SDK, which does not work correctly when cached. 
				The cached files are served from the ' . NGFB_CACHEURL . ' folder.' ) .
				'<td class="blank">' . $this->ngfb->admin->form->get_hidden( 'ngfb_file_cache_hrs' ) . 
					$this->ngfb->options['ngfb_file_cache_hrs'] . ' Hours</td>',

				$this->ngfb->util->th( 'Verify SSL Certificates', null, null, 
				'Enable verification of peer SSL certificates when fetching content to be cached using HTTPS. 
				The PHP \'curl\' function will use the ' .  NGFB_CURL_CAINFO . ' certificate file by default. 
				You may want define the NGFB_CURL_CAINFO constant in your wp-config.php file to use an 
				alternate certificate file (see the constants.txt file in the plugin folder for additional information).' ) . 
				'<td class="blank">' . $this->ngfb->admin->form->get_hidden( 'ngfb_verify_certs' ) . 
					$this->ngfb->admin->form->get_fake_checkbox( $this->ngfb->options['ngfb_verify_certs'] ) . '</td>',
			);
		}

		protected function get_more_rewrite() {
			return array(
				'<td colspan="2" align="center">' . $this->ngfb->msg->get( 'pro_feature' ) . '</td>',

				$this->ngfb->util->th( 'Static Content URL(s)', 'highlight', null, 
				'Rewrite image URLs in the Open Graph meta tags, encoded image URLs shared by social buttons (Pinterest and Tumblr), 
				and cached social media files. Leave this option blank to disable the rewriting feature (default is disabled).
				Wildcarding and multiple CDN hostnames are supported -- see the 
				<a href="http://wordpress.org/plugins/nextgen-facebook/other_notes/" target="_blank">Other Notes</a> for 
				more information and examples.' ) .
				'<td class="blank">' .  $this->ngfb->admin->form->get_hidden( 'ngfb_cdn_urls' ) . 
					$this->ngfb->options['ngfb_cdn_urls'] . '</td>',

				$this->ngfb->util->th( 'Include Folders', null, null, '
				A comma delimited list of patterns to match. These patterns must be present in the URL for the rewrite to take place 
				(the default value is "<em>wp-content, wp-includes</em>").') .
				'<td class="blank">' .  $this->ngfb->admin->form->get_hidden( 'ngfb_cdn_folders' ) . 
					$this->ngfb->options['ngfb_cdn_folders'] . '</td>',

				$this->ngfb->util->th( 'Exclude Patterns', null, null,
				'A comma delimited list of patterns to match. If these patterns are found in the URL, the rewrite will be skipped (the default value is blank).
				If you are caching social website images and JavaScript (see <em>File Cache Expiry</em> option above), 
				the URLs to this cached content will be rewritten as well. To exclude the ' . $this->ngfb->fullname . ' cache folder 
				from being rewritten, use \'<em>/nextgen-facebook/cache/</em>\' as a value here.' ) .
				'<td class="blank">' .  $this->ngfb->admin->form->get_hidden( 'ngfb_cdn_excl' ) . 
					$this->ngfb->options['ngfb_cdn_excl'] . '</td>',

				$this->ngfb->util->th( 'Not when Using HTTPS', null, null, 
				'Skip rewriting URLs when using HTTPS (useful if your CDN provider does not offer HTTPS, for example).' ) .
				'<td class="blank">' .  $this->ngfb->admin->form->get_hidden( 'ngfb_cdn_not_https' ) . 
					$this->ngfb->admin->form->get_fake_checkbox( $this->ngfb->options['ngfb_cdn_not_https'] ) . '</td>',

				$this->ngfb->util->th( 'www is Optional', null, null, 
				'The www hostname prefix (if any) in the WordPress site URL is optional (default is checked).' ) .
				'<td class="blank">' .  $this->ngfb->admin->form->get_hidden( 'ngfb_cdn_www_opt' ) . 
					$this->ngfb->admin->form->get_fake_checkbox( $this->ngfb->options['ngfb_cdn_www_opt'] ) . '</td>',
			);
		}

		protected function get_rows( $id ) {
			$ret = array();
			switch ( $id ) {

				case 'custom' :
					if ( $this->ngfb->is_avail['aop'] == false )
						$ret[] = '<td colspan="4" align="center">'.$this->ngfb->msg->get( 'pro_feature' ).'</td>';

					$ret[] = '<td></td>' .
					$this->ngfb->util->th( 'Show', 'left checkbox' ) .
					$this->ngfb->util->th( 'Field Name', 'left medium', null,
					'You should not modify the contact field names unless you have a specific reason to do so.
					As an example, to match the contact field name of a theme or other plugin, you might change \'gplus\' to \'googleplus\'.
					If you change the Facebook or Google+ field names, please make sure to update the Open Graph 
					<em>Author Profile URL</em> and Google <em>Author Link URL</em> options on the ' .
					$this->ngfb->util->get_admin_url( 'general', 'General Settings' ) . ' page.' ) .
					$this->ngfb->util->th( 'User Profile Label', 'left wide' );

					$social_prefix = $this->ngfb->social_prefix;
					ksort( $social_prefix );
					foreach ( $social_prefix as $id => $opt_prefix ) {
						$cm_opt = 'ngfb_cm_'.$opt_prefix.'_';
						$th_val = empty( $this->ngfb->website_libs[$id] ) ? ucfirst( $id ) : $this->ngfb->website_libs[$id];
						$th_val = $th_val == 'GooglePlus' ? 'Google+' : $th_val;
						// not all social websites have a contact method field
						if ( array_key_exists( $cm_opt.'enabled', $this->ngfb->options ) ) {
							if ( $this->ngfb->is_avail['aop'] == true ) {
								$ret[] = $this->ngfb->util->th( $th_val ) .
								'<td class="checkbox">' . $this->ngfb->admin->form->get_checkbox( $cm_opt.'enabled' ) . '</td>' .
								'<td>' . $this->ngfb->admin->form->get_input( $cm_opt.'name' ) . '</td>' .
								'<td>' . $this->ngfb->admin->form->get_input( $cm_opt.'label' ) . '</td>';
							} else {
								$ret[] = $this->ngfb->util->th( $th_val ) .
								'<td class="blank checkbox">' . $this->ngfb->admin->form->get_hidden( $cm_opt.'enabled' ) . 
									$this->ngfb->admin->form->get_fake_checkbox( $this->ngfb->options[$cm_opt.'enabled'] ) . '</td>' .
								'<td class="blank">' . $this->ngfb->admin->form->get_hidden( $cm_opt.'name' ) .
									$this->ngfb->options[$cm_opt.'name'] . '</td>' .
								'<td class="blank">' . $this->ngfb->admin->form->get_hidden( $cm_opt.'label' ) .
									$this->ngfb->options[$cm_opt.'label'] . '</td>';
							}
						}
					
					}
					break;

				case 'builtin' :
					if ( $this->ngfb->is_avail['aop'] == false )
						$ret[] = '<td colspan="4" align="center">'.$this->ngfb->msg->get( 'pro_feature' ).'</td>';

					$ret[] = '<td></td>' .
					$this->ngfb->util->th( 'Show', 'left checkbox' ) .
					$this->ngfb->util->th( 'Field Name', 'left medium', null, 
					'The built-in WordPress contact field names cannot be changed.' ) .
					$this->ngfb->util->th( 'User Profile Label', 'left wide' );

					$wp_contacts = $this->ngfb->wp_contacts;
					ksort( $wp_contacts );
					foreach ( $wp_contacts as $id => $th_val ) {
						$cm_opt = 'wp_cm_'.$id.'_';
						if ( array_key_exists( $cm_opt.'enabled', $this->ngfb->options ) ) {
							if ( $this->ngfb->is_avail['aop'] == true ) {
								$ret[] = $this->ngfb->util->th( $th_val ) .
								'<td class="checkbox">' . $this->ngfb->admin->form->get_checkbox( $cm_opt.'enabled' ) . '</td>' .
								'<td>' . $this->ngfb->admin->form->get_fake_input( $id ) . '</td>' .
								'<td>' . $this->ngfb->admin->form->get_input( $cm_opt.'label' ) . '</td>';
							} else {
								$ret[] = $this->ngfb->util->th( $th_val ) .
								'<td class="blank checkbox">' . $this->ngfb->admin->form->get_hidden( $cm_opt.'enabled' ) . 
									$this->ngfb->admin->form->get_fake_checkbox( $this->ngfb->options[$cm_opt.'enabled'] ) . '</td>' .
								'<td>' . $this->ngfb->admin->form->get_fake_input( $id ) . '</td>' .
								'<td class="blank">' . $this->ngfb->admin->form->get_hidden( $cm_opt.'label' ) .
									$this->ngfb->options[$cm_opt.'label'] . '</td>';
							}
						}
					}
					break;

				case 'activation':

					$ret = array_merge( $ret, $this->get_pre_activation() );

					$ret[] = $this->ngfb->util->th( 'Preserve Settings on Uninstall', 'highlight', null, 
					'Check this option if you would like to preserve all ' . $this->ngfb->fullname . 
					' settings when you <em>uninstall</em> the plugin (default is unchecked).' ) . 
					'<td>' . $this->ngfb->admin->form->get_checkbox( 'ngfb_preserve' ) . '</td>';

					$ret[] = $this->ngfb->util->th( 'Reset Settings on Activate', null, null, 
					'Check this option if you would like to reset the ' . $this->ngfb->fullname . 
					' settings to their default values when you <em>deactivate</em>, and then 
					<em>re-activate</em> the plugin (default is unchecked).' ) .  
					'<td>' . $this->ngfb->admin->form->get_checkbox( 'ngfb_reset' ) . '</td>';

					$ret[] = $this->ngfb->util->th( 'Add Hidden Debug Info', null, null, 
					'Include hidden debug information with the Open Graph meta tags (default is unchecked).' ) . 
					'<td>' . $this->ngfb->admin->form->get_checkbox( 'ngfb_debug' ) . '</td>';

					break;

				case 'content':

					$ret[] = $this->ngfb->util->th( 'Enable Shortcode(s)', 'highlight', null, 
					'Enable the ' . $this->ngfb->fullname . ' content shortcode(s) (default is unchecked).' ) .
					'<td>' . $this->ngfb->admin->form->get_checkbox( 'ngfb_enable_shortcode' ) . '</td>';

					$ret[] =  $this->ngfb->util->th( 'Ignore Small Images', 'highlight', null, 
					$this->ngfb->fullname . ' will attempt to include images from img html tags it finds in the content.
					The img html tags must have a width and height attribute, and their size must be equal to or larger than the 
					<em>Image Dimensions</em> you\'ve chosen (on the General Settings page). 
					You can uncheck this option to include smaller images from the content, 
					or refer to the <a href="http://wordpress.org/extend/plugins/nextgen-facebook/faq/">FAQ</a> 
					for additional solutions.' ) . 
					'<td>' . $this->ngfb->admin->form->get_checkbox( 'ngfb_skip_small_img' ) . '</td>';

					$ret[] = $this->ngfb->util->th( 'Apply Content Filters', null, null, 
					'Apply the standard WordPress filters to render the content (default is checked).
					This renders all shortcodes, and allows ' . $this->ngfb->fullname . ' to detect images and 
					embedded videos that may be provided by these.' ) . 
					'<td>' . $this->ngfb->admin->form->get_checkbox( 'ngfb_filter_content' ) . '</td>';

					$ret[] = $this->ngfb->util->th( 'Apply Excerpt Filters', null, null, 
					'Apply the standard WordPress filters to render the excerpt (default is unchecked).
					Check this option if you use shortcodes in your excerpt, for example.' ) . 
					'<td>' . $this->ngfb->admin->form->get_checkbox( 'ngfb_filter_excerpt' ) . '</td>';

					$ret = array_merge( $ret, $this->get_more_content() );

					break;

				case 'cache':

					$ret[] = $this->ngfb->util->th( 'Object Cache Expiry', null, null, 
					$this->ngfb->fullname . ' saves the rendered (filtered) content to a non-presistant cache (wp_cache), 
					and the completed Open Graph meta tags and social buttons to a persistant (transient) cache. 
					The default is ' . $this->ngfb->opt->defaults['ngfb_object_cache_exp'] . ' seconds, and the minimum value is 
					1 second (such a low value is not recommended).' ) .
					'<td nowrap>' . $this->ngfb->admin->form->get_input( 'ngfb_object_cache_exp', 'short' ) . ' Seconds</td>';

					$ret = array_merge( $ret, $this->get_more_cache() );

					break;

				case 'shorten':

					$ret[] = $this->ngfb->util->th( 'Goo.gl Simple API Access Key', null, null, 
					'The "Google URL Shortener API Key" for this website. If you don\'t already have one, visit Google\'s 
					<a href="https://developers.google.com/url-shortener/v1/getting_started#APIKey" target="_blank">acquiring 
					and using an API Key</a> documentation, and follow the directions to acquire your <em>Simple API Access Key</em>.' ) .
					'<td>' . $this->ngfb->admin->form->get_input( 'ngfb_googl_api_key', 'wide' ) . '</td>';

					$ret[] = $this->ngfb->util->th( 'Bit.ly Username', null, null, 
					'The Bit.ly username for the following API key. If you don\'t already have one, see 
					<a href="https://bitly.com/a/your_api_key" target="_blank">Your Bit.ly API Key</a>.' ) .
					'<td>' . $this->ngfb->admin->form->get_input( 'ngfb_bitly_login' ) . '</td>';

					$ret[] = $this->ngfb->util->th( 'Bit.ly API Key', null, null, 
					'The Bit.ly API key for this website. If you don\'t already have one, see 
					<a href="https://bitly.com/a/your_api_key" target="_blank">Your Bit.ly API Key</a>.' ) .
					'<td>' . $this->ngfb->admin->form->get_input( 'ngfb_bitly_api_key', 'wide' ) . '</td>';

					break;

				case 'rewrite':

					$ret = array_merge( $ret, $this->get_more_rewrite() );

					break;
			}
			return $ret;
		}

	}
}

?>

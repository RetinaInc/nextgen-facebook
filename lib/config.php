<?php
/*
License: GPLv3
License URI: http://surniaulula.com/wp-content/plugins/nextgen-facebook/license/gpl.txt
Copyright 2013 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'ngfbPluginConfig' ) ) {

	class ngfbPluginConfig {

		private static $cf = array(
			'version' => '6.14.0',			// plugin version
			'lca' => 'ngfb',			// lowercase acronym
			'uca' => 'NGFB',			// uppercase acronym
			'slug' => 'nextgen-facebook',
			'menu' => 'Open Graph+',		// menu item label
			'full' => 'NGFB Open Graph+',		// full plugin name
			'full_pro' => 'NGFB Open Graph+ Pro',
			'update_hours' => 12,			// check for pro updates
			'lib' => array(				// libraries
				'setting' => array (
					'general' => 'General',
					'advanced' => 'Advanced',
					'contact' => 'Contact Methods',
					'social' => 'Social Sharing',
					'style' => 'Social Style',
					'about' => 'About',
				),
				'network_setting' => array(
					'network' => 'Network',
				),
				'website' => array(
					'facebook' => 'Facebook', 
					'gplus' => 'GooglePlus',
					'twitter' => 'Twitter',
					'linkedin' => 'LinkedIn',
					'managewp' => 'ManageWP',
					'pinterest' => 'Pinterest',
					'stumbleupon' => 'StumbleUpon',
					'tumblr' => 'Tumblr',
					'youtube' => 'YouTube',
					'skype' => 'Skype',
				),
				'shortcode' => array(
					'ngfb' => 'Ngfb',
				),
				'widget' => array(
					'social' => 'SocialSharing',
				),
				'ecom' => array(
					'woocommerce' => 'WooCommerce',
					'marketpress' => 'MarketPress',
					'wpecommerce' => 'WPeCommerce',
				),
				'seo' => array(
					'aioseop' => 'AllinOneSEOPack',
					'seou' => 'SEOUltimate',
					'wpseo' => 'WordPressSEO',
				),
			),
			'opt' => array(				// options
				'pre' => array(
					'facebook' => 'fb', 
					'gplus' => 'gp',
					'twitter' => 'twitter',
					'linkedin' => 'linkedin',
					'managewp' => 'managewp',
					'pinterest' => 'pin',
					'stumbleupon' => 'stumble',
					'tumblr' => 'tumblr',
					'youtube' => 'yt',
					'skype' => 'skype',
				),
			),
			'wp' => array(				// wordpress
				'min_version' => '3.0',		// minimum wordpress version
				'contact' => array(
					'aim' => 'AIM',
					'jabber' => 'Jabber / Google Talk',
					'yim' => 'Yahoo IM',
				),
			),
			'css' => array(				// stylesheets
				'social' => 'Buttons Style',
				'excerpt' => 'Excerpt Style',
				'content' => 'Content Style',
				'shortcode' => 'Shortcode Style',
				'widget' => 'Widget Style',
			),
			'url' => array(
				'feed' => 'http://feed.surniaulula.com/category/application/wordpress/wp-plugins/ngfb/feed/',
				'readme' => 'http://plugins.svn.wordpress.org/nextgen-facebook/trunk/readme.txt',
				'purchase' => 'http://plugin.surniaulula.com/extend/plugins/nextgen-facebook/',
				'faq' => 'http://wordpress.org/plugins/nextgen-facebook/faq/',
				'notes' => 'http://wordpress.org/plugins/nextgen-facebook/other_notes/',
				'changelog' => 'http://wordpress.org/plugins/nextgen-facebook/changelog/',
				'support' => 'http://wordpress.org/support/plugin/nextgen-facebook',
				'pro_faq' => 'http://faq.nextgen-facebook.surniaulula.com/',
				'pro_notes' => 'http://notes.nextgen-facebook.surniaulula.com/',
				'pro_support' => 'http://support.nextgen-facebook.surniaulula.com/',
				'pro_request' => 'http://request.nextgen-facebook.surniaulula.com/',
				'pro_update' => 'http://update.surniaulula.com/extend/plugins/nextgen-facebook/update/',
			),
			'img' => array(				// images
				'min_width' => 200,
				'min_height' => 200,
				'follow' => array(
					'size' => 32,
					'src' => array(
						'facebook.png' => 'https://www.facebook.com/pages/Surnia-Ulula/200643823401977',
						'gplus.png' => 'https://plus.google.com/u/2/103457833348046432604/posts',
						'linkedin.png' => 'https://www.linkedin.com/in/jsmoriss',
						'twitter.png' => 'https://twitter.com/surniaululacom',
						'youtube.png' => 'https://www.youtube.com/user/SurniaUlulaCom',
						'feed.png' => 'http://feed.surniaulula.com/category/application/wordpress/wp-plugins/ngfb/feed/',
					),
				),
			),
		);

		public static function get_config( $idx = '' ) { 
			if ( ! empty( $idx ) ) {
				if ( array_key_exists( $idx, self::$cf ) )
					return self::$cf[$idx];
				else return false;
			} else return self::$cf;
		}

		public static function set_constants( $plugin_filepath ) { 

			$lca = self::$cf['lca'];
			$uca = self::$cf['uca'];

			define( $uca.'_FILEPATH', $plugin_filepath );
			define( $uca.'_PLUGINDIR', trailingslashit( plugin_dir_path( $plugin_filepath ) ) );	// since wp 1.2.0 
			define( $uca.'_PLUGINBASE', plugin_basename( $plugin_filepath ) );			// since wp 1.5
			define( $uca.'_TEXTDOM', self::$cf['slug'] );
			define( $uca.'_URLPATH', trailingslashit( plugins_url( '', $plugin_filepath ) ) );
			define( $uca.'_NONCE', md5( constant( $uca.'_PLUGINDIR' ).'-'.self::$cf['version'] ) );
			define( 'AUTOMATTIC_README_MARKDOWN', constant( $uca.'_PLUGINDIR' ).'lib/ext/markdown.php' );

			/*
			 * Allow some constants to be pre-defined in wp-config.php
			 */

			// *_RESET			true|false
			// *_WP_DEBUG			true|false
			// *_HTML_DEBUG			true|false
			// *_OPEN_GRAPH_DISABLE		true|false
			// *_MIN_IMG_SIZE_DISABLE	true|false
			// *_OBJECT_CACHE_DISABLE	true|false
			// *_TRANSIENT_CACHE_DISABLE	true|false
			// *_FILE_CACHE_DISABLE		true|false
			// *_CURL_DISABLE		true|false
			// *_CURL_PROXY			http://hostname:port/
			// *_CURL_PROXYUSERPWD		user:password
			// *_WISTIA_API_PWD		password

			if ( defined( $uca.'_DEBUG' ) && 
				! defined( $uca.'_HTML_DEBUG' ) )
					define( $uca.'_HTML_DEBUG', constant( $uca.'_DEBUG' ) );

			if ( ! defined( $uca.'_CACHEDIR' ) )
				define( $uca.'_CACHEDIR', constant( $uca.'_PLUGINDIR' ).'cache/' );

			if ( ! defined( $uca.'_CACHEURL' ) )
				define( $uca.'_CACHEURL', constant( $uca.'_URLPATH' ).'cache/' );

			if ( ! defined( $uca.'_OPTIONS_NAME' ) )
				define( $uca.'_OPTIONS_NAME', $lca.'_options' );

			if ( ! defined( $uca.'_SITE_OPTIONS_NAME' ) )
				define( $uca.'_SITE_OPTIONS_NAME', $lca.'_site_options' );

			if ( ! defined( $uca.'_META_NAME' ) )
				define( $uca.'_META_NAME', '_'.$lca.'_meta' );

			if ( ! defined( $uca.'_MENU_PRIORITY' ) )
				define( $uca.'_MENU_PRIORITY', '99.10' );

			if ( ! defined( $uca.'_INIT_PRIORITY' ) )
				define( $uca.'_INIT_PRIORITY', 12 );

			if ( ! defined( $uca.'_HEAD_PRIORITY' ) )
				define( $uca.'_HEAD_PRIORITY', 10 );

			if ( ! defined( $uca.'_SOCIAL_PRIORITY' ) )
				define( $uca.'_SOCIAL_PRIORITY', 100 );
			
			if ( ! defined( $uca.'_FOOTER_PRIORITY' ) )
				define( $uca.'_FOOTER_PRIORITY', 100 );
			
			if ( ! defined( $uca.'_OG_SIZE_NAME' ) )
				define( $uca.'_OG_SIZE_NAME', $lca.'-open-graph' );

			if ( ! defined( $uca.'_MIN_DESC_LEN' ) )
				define( $uca.'_MIN_DESC_LEN', 156 );

			if ( ! defined( $uca.'_MAX_IMG_OG' ) )
				define( $uca.'_MAX_IMG_OG', 20 );

			if ( ! defined( $uca.'_MAX_VID_OG' ) )
				define( $uca.'_MAX_VID_OG', 20 );

			if ( ! defined( $uca.'_MAX_CACHE_HRS' ) )
				define( $uca.'_MAX_CACHE_HRS', 24 );

			if ( ! defined( $uca.'_DEBUG_FILE_EXP' ) )
				define( $uca.'_DEBUG_FILE_EXP', 300 );

			if ( ! defined( $uca.'_CURL_USERAGENT' ) )
				define( $uca.'_CURL_USERAGENT', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:18.0) Gecko/20100101 Firefox/18.0' );

			if ( ! defined( $uca.'_CURL_CAINFO' ) )
				define( $uca.'_CURL_CAINFO', constant( $uca.'_PLUGINDIR' ).'share/curl/cacert.pem' );

		}

		public static function require_libs() {
			
			$plugin_dir = constant( self::$cf['uca'].'_PLUGINDIR' );

			require_once ( $plugin_dir.'lib/debug.php' );
			require_once ( $plugin_dir.'lib/check.php' );
			require_once ( $plugin_dir.'lib/util.php' );
			require_once ( $plugin_dir.'lib/notices.php' );
			require_once ( $plugin_dir.'lib/options.php' );
			require_once ( $plugin_dir.'lib/user.php' );
			require_once ( $plugin_dir.'lib/media.php' );
			require_once ( $plugin_dir.'lib/webpage.php' );
			require_once ( $plugin_dir.'lib/postmeta.php' );
			require_once ( $plugin_dir.'lib/social.php' );
			require_once ( $plugin_dir.'lib/style.php' );
			require_once ( $plugin_dir.'lib/script.php' );
			require_once ( $plugin_dir.'lib/cache.php' );
			require_once ( $plugin_dir.'lib/update.php' );

			if ( is_admin() ) {
				require_once ( $plugin_dir.'lib/messages.php' );
				require_once ( $plugin_dir.'lib/admin.php' );

				// settings classes extend lib/admin.php and objects are created by lib/admin.php
				foreach ( self::$cf['lib']['setting'] as $id => $name )
					require_once ( $plugin_dir.'lib/settings/'.$id.'.php' );
				unset ( $id, $name );

				if ( is_multisite() ) {
					foreach ( self::$cf['lib']['network_setting'] as $id => $name )
						require_once ( $plugin_dir.'lib/settings/'.$id.'.php' );
					unset ( $id, $name );
				}

				require_once ( $plugin_dir.'lib/form.php' );
				require_once ( $plugin_dir.'lib/ext/parse-readme.php' );

			} else {

				require_once ( $plugin_dir.'lib/head.php' );
				require_once ( $plugin_dir.'lib/opengraph.php' );
				require_once ( $plugin_dir.'lib/tags.php' );
				require_once ( $plugin_dir.'lib/functions.php' );

				foreach ( self::$cf['lib']['shortcode'] as $id => $name )
					require_once ( $plugin_dir.'lib/shortcodes/'.$id.'.php' );
				unset ( $id, $name );
			}

			// website classes extend both lib/social.php and lib/settings/social.php
			foreach ( self::$cf['lib']['website'] as $id => $name )
				if ( file_exists( $plugin_dir.'lib/websites/'.$id.'.php' ) )
					require_once ( $plugin_dir.'lib/websites/'.$id.'.php' );
			unset ( $id, $name );

			// widgets are added to wp when library file is loaded
			// no need to create the class object later on
			foreach ( self::$cf['lib']['widget'] as $id => $name )
				if ( file_exists( $plugin_dir.'lib/widgets/'.$id.'.php' ) )
					require_once ( $plugin_dir.'lib/widgets/'.$id.'.php' );
			unset ( $id, $name );

			// additional classes are loaded and extended by the addon construct
			if ( file_exists( $plugin_dir.'lib/pro/addon.php' ) )
				require_once ( $plugin_dir.'lib/pro/addon.php' );

		}

	}
}

?>

<?php
/*
License: GPLv3
License URI: http://surniaulula.com/wp-content/plugins/nextgen-facebook/license/gpl.txt
Copyright 2012-2013 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'Sorry, you cannot call this webpage directly.' );

if ( ! class_exists( 'ngfbCheck' ) ) {

	class ngfbCheck {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			if ( is_object( $this->p->debug ) && 
				method_exists( $this->p->debug, 'mark' ) )
					$this->p->debug->mark();
		}

		public function wp_version() {
			global $wp_version;
			if ( version_compare( $wp_version, $this->p->min_wp_version, '<' ) ) {
				deactivate_plugins( NGFB_PLUGINBASE );
				error_log( NGFB_PLUGINBASE.' requires WordPress '.$this->p->min_wp_version.' or higher ('.$wp_version.' reported).' );
				wp_die( '<p>'. sprintf( __( 'The %1$s plugin cannot be activated - it requires WordPress %2$s or higher.' ), 
					$this->p->fullname, $this->p->min_wp_version ) .'</p>' );
			}
		}

		// used before any class objects are created, so keep in main class
		public function available( $is_avail = array() ) {

			// ngfb pro
			$is_avail['aop'] = class_exists( $this->p->acronym.'AddOnPro' ) ? true : false;

			// available since php v4.0.6+
			$is_avail['mbdecnum'] = function_exists( 'mb_decode_numericentity' ) ? true : false;

			// php curl
			$is_avail['curl'] = function_exists( 'curl_init' ) ? true : false;

			// post thumbnail feature is supported by wp theme // since wp 2.9.0
			$is_avail['postthumb'] = function_exists( 'has_post_thumbnail' ) ? true : false;

			// nextgen gallery plugin
			// use in combination with $this->p->ngg_version
			$is_avail['ngg'] = class_exists( 'nggdb' ) || class_exists( 'C_NextGEN_Bootstrap' ) ? true : false;

			/*
			 * Supported SEO Plugins
			 */
			$is_avail['any_seo'] = false;	// by default, define any_seo value as false
			foreach ( $this->p->seo_libs as $id => $name ) {
				$func_name = '';
				$class_name = '';
				switch ( $id ) {
					case 'aioseop':	$class_name = 'All_in_One_SEO_Pack'; break;
					case 'seou':	$class_name = 'SEO_Ultimate'; break;
					case 'wpseo':	$func_name = 'wpseo_init'; break;
				}
				if ( ! empty( $func_name ) && function_exists( $func_name ) ) 
					$is_avail['any_seo'] = $is_avail[$id] = true;
				elseif ( ! empty( $class_name ) && class_exists( $class_name ) ) 
					$is_avail['any_seo'] = $is_avail[$id] = true;
				else $is_avail[$id] = false;
			}
			unset ( $id, $name );

			/*
			 * Supported eCommerce Plugins
			 */
			foreach ( $this->p->ecom_libs as $id => $name ) {
				$func_name = '';
				$class_name = '';
				switch ( $id ) {
					case 'woocommerce':	$class_name = 'Woocommerce'; break;
					case 'marketpress':	$class_name = 'MarketPress'; break;
					case 'wpecommerce':	$class_name = 'WP_eCommerce'; break;
				}
				if ( ! empty( $func_name ) && function_exists( $func_name ) ) 
					$is_avail['any_ecom'] = $is_avail[$id] = true;
				elseif ( ! empty( $class_name ) && class_exists( $class_name ) ) 
					$is_avail['any_ecom'] = $is_avail[$id] = true;
				else $is_avail[$id] = false;
			}
			unset ( $id, $name );

			return $is_avail;
		}

		public function conflicts() {

			$conflict_log_prefix =  __( 'plugin conflict detected', NGFB_TEXTDOM ) . ' - ';
			$conflict_err_prefix =  __( 'Plugin conflict detected', NGFB_TEXTDOM ) . ' -- ';

			// PHP
			if ( $this->p->is_avail['mbdecnum'] !== true ) {
				$this->p->debug->log( 'mb_decode_numericentity() function missing (required to decode UTF8 entities)' );
				$this->p->notices->err( 
					sprintf( __( 'The <code><a href="%s" target="_blank">mb_decode_numericentity()</a></code> function (available since PHP v4.0.6) is missing.', NGFB_TEXTDOM ),
						__( 'http://php.net/manual/en/function.mb-decode-numericentity.php', NGFB_TEXTDOM ) ).' '.
					__( 'This function is required to decode UTF8 entities.', NGFB_TEXTDOM ).' '.
					__( 'Please update your PHP installation (install \'php-mbstring\' on most Linux distros).', NGFB_TEXTDOM ) );
			}

			// Yoast WordPress SEO
			if ( $this->p->is_avail['wpseo'] == true ) {
				$wpseo_social = get_option( 'wpseo_social' );
				if ( ! empty( $wpseo_social['opengraph'] ) ) {
					$this->p->debug->log( $conflict_log_prefix.'wpseo opengraph meta data option is enabled' );
					$this->p->notices->err( $conflict_err_prefix.
						sprintf( __( 'Please uncheck the \'<em>Open Graph meta data</em>\' Facebook option in the <a href="%s">Yoast WordPress SEO plugin Social settings</a>.', NGFB_TEXTDOM ), 
							get_admin_url( null, 'admin.php?page=wpseo_social' ) ) );
				}
				if ( ! empty( $this->p->options['tc_enable'] ) && ! empty( $wpseo_social['twitter'] ) ) {
					$this->p->debug->log( $conflict_log_prefix.'wpseo twitter meta data option is enabled' );
					$this->p->notices->err( $conflict_err_prefix.
						sprintf( __( 'Please uncheck the \'<em>Twitter Card meta data</em>\' Twitter option in the <a href="%s">Yoast WordPress SEO plugin Social settings</a>.', NGFB_TEXTDOM ), 
							get_admin_url( null, 'admin.php?page=wpseo_social' ) ) );
				}

				if ( ! empty( $this->p->options['link_publisher_url'] ) && ! empty( $wpseo_social['plus-publisher'] ) ) {
					$this->p->debug->log( $conflict_log_prefix.'wpseo google plus publisher option is defined' );
					$this->p->notices->err( $conflict_err_prefix.
						sprintf( __( 'Please remove the \'<em>Google Publisher Page</em>\' value entered in the <a href="%s">Yoast WordPress SEO plugin Social settings</a>.', NGFB_TEXTDOM ), 
							get_admin_url( null, 'admin.php?page=wpseo_social' ) ) );
				}
			}

			// SEO Ultimate
			if ( $this->p->is_avail['seou'] == true ) {
				$seo_ultimate = get_option( 'seo_ultimate' );
				if ( ! empty( $seo_ultimate['modules'] ) && is_array( $seo_ultimate['modules'] ) ) {
					if ( array_key_exists( 'opengraph', $seo_ultimate['modules'] ) && $seo_ultimate['modules']['opengraph'] !== -10 ) {
						$this->p->debug->log( $conflict_log_prefix.'seo ultimate opengraph module is enabled' );
						$this->p->notices->err( $conflict_err_prefix.
							sprintf( __( 'Please disable the \'<em>Open Graph Integrator</em>\' module in the <a href="%s">SEO Ultimate plugin Module Manager</a>.', NGFB_TEXTDOM ), 
								get_admin_url( null, 'admin.php?page=seo' ) ) );
					}
				}
			}

			// All in One SEO Pack
			if ( $this->p->is_avail['aioseop'] == true ) {
				$aioseop_options = get_option( 'aioseop_options' );
				if ( array_key_exists( 'aiosp_google_disable_profile', $aioseop_options ) && empty( $aioseop_options['aiosp_google_disable_profile'] ) ) {
					$this->p->debug->log( $conflict_log_prefix.'aioseop google plus profile is enabled' );
					$this->p->notices->err( $conflict_err_prefix.
						sprintf( __( 'Please check the \'<em>Disable Google Plus Profile</em>\' option in the <a href="%s">All in One SEO Pack Plugin Options</a>.', NGFB_TEXTDOM ), 
							get_admin_url( null, 'admin.php?page=all-in-one-seo-pack/aioseop_class.php' ) ) );
				}
			}

			// WooCommerce ShareYourCart Extension
			if ( class_exists( 'ShareYourCartWooCommerce' ) ) {
				$woo_share_settings = get_option( 'woocommerce_shareyourcart_settings' );
				if ( ! empty( $woo_share_settings['enabled'] ) ) {
					$this->p->debug->log( $conflict_log_prefix.'woocommerce shareyourcart extension is enabled' );
					$this->p->notices->err( $conflict_err_prefix.
						__( 'The WooCommerce ShareYourCart Extension does not provide an option to turn off its Open Graph meta tags.', NGFB_TEXTDOM ).' '.
						sprintf( __( 'Please disable the extension on the <a href="%s">ShareYourCart Integration Tab</a>.', NGFB_TEXTDOM ), 
							get_admin_url( null, 'admin.php?page=woocommerce&tab=integration&section=shareyourcart' ) ) );
				}
			}

			// Wordbooker
			if ( function_exists( 'wordbooker_og_tags' ) ) {
				$wordbooker_settings = get_option( 'wordbooker_settings' );
				if ( empty( $wordbooker_settings['wordbooker_fb_disable_og'] ) ) {
					$this->p->debug->log( $conflict_log_prefix.'wordbooker opengraph is enabled' );
					$this->p->notices->err( $conflict_err_prefix.
						sprintf( __( 'Please check the \'<em>Disable in-line production of OpenGraph Tags</em>\' option on the <a href="%s">Wordbooker Options Page</a>.', NGFB_TEXTDOM ), 
							get_admin_url( null, 'options-general.php?page=wordbooker' ) ) );
				}
			}

			// Facebook
  			if ( class_exists( 'Facebook_Loader' ) ) {
                                $this->p->debug->log( $conflict_log_prefix.'facebook plugin is active' );
                                $this->p->notices->err( $conflict_err_prefix. 
					sprintf( __( 'Please <a href="%s">deactivate the Facebook plugin</a> to prevent duplicate Open Graph meta tags in your webpage headers.', NGFB_TEXTDOM ), 
						get_admin_url( null, 'plugins.php' ) ) );
                        }

			// AddThis Social Bookmarking Widget
			if ( defined( 'ADDTHIS_INIT' ) && ADDTHIS_INIT && 
				( ! empty( $this->p->options['plugin_filter_content'] ) || ! empty( $this->p->options['plugin_filter_excerpt'] ) ) ) {

				$this->p->debug->log( $conflict_log_prefix.'addthis has broken excerpt / content filters' );
				$this->p->notices->err( $conflict_err_prefix. 
					__( 'The AddThis Social Bookmarking Widget has incorrectly coded content and excerpt filters.', NGFB_TEXTDOM ).' '.
					sprintf( __( 'Please uncheck the \'<em>Apply Content and Excerpt Filters</em>\' options on the <a href="%s">%s Advanced settings page</a>.', NGFB_TEXTDOM ),  
						$this->p->util->get_admin_url( 'advanced' ), $this->p->fullname ) ).' '.
					__( 'Disabling content filters will prevent shortcodes from being expanded, which may lead to incorrect / incomplete description meta tags.', NGFB_TEXTDOM );
			}

		}

		public function pro_active() {
			if ( $this->p->is_avail['aop'] == true && 
				! empty( $this->p->options['plugin_pro_tid'] ) && 
					empty( $this->p->update_error ) )
						return true;
			return false;
		}

	}

}

?>
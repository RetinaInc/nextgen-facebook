<?php
/*
License: GPLv3
License URI: http://surniaulula.com/wp-content/plugins/nextgen-facebook/license/gpl.txt
Copyright 2012-2013 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'Sorry, you cannot call this webpage directly.' );

if ( ! class_exists( 'ngfbSettingsTwitter' ) && class_exists( 'ngfbSettingsSocialSharing' ) ) {

	class ngfbSettingsTwitter extends ngfbSettingsSocialSharing {

		public $lang = array(
			'en'	=> 'English',
			'fr'	=> 'French',
			'de'	=> 'German',
			'it'	=> 'Italian',
			'es'	=> 'Spanish',
			'ko'	=> 'Korean',
			'ja'	=> 'Japanese',
		);

		protected $ngfb;

		public function __construct( &$ngfb_plugin ) {
			$this->ngfb =& $ngfb_plugin;
			$this->ngfb->debug->mark();
		}

		public function get_rows() {
			$ret = array();
			
			$ret[] = $this->ngfb->util->th( 'Add Button to', 'short' ) . '<td>' . 
			$this->ngfb->admin->form->get_checkbox( 'twitter_on_the_content' ) . ' the Content and / or ' . 
			$this->ngfb->admin->form->get_checkbox( 'twitter_on_the_excerpt' ) . ' the Excerpt Text</td>';

			$ret[] = $this->ngfb->util->th( 'Preferred Order', 'short' ) . '<td>' . 
			$this->ngfb->admin->form->get_select( 'twitter_order', range( 1, count( $this->ngfb->social_prefix ) ), 'short' ) . '</td>';

			$ret[] = $this->ngfb->util->th( 'JavaScript in', 'short' ) . '<td>' . 
			$this->ngfb->admin->form->get_select( 'twitter_js_loc', $this->js_locations ) . '</td>';

			$ret[] = $this->ngfb->util->th( 'Language', 'short' ) . '<td>' . 
			$this->ngfb->admin->form->get_select( 'twitter_lang', $this->lang ) . '</td>';

			$ret[] = $this->ngfb->util->th( 'Count Position', 'short' ) . '<td>' . 
			$this->ngfb->admin->form->get_select( 'twitter_count', 
				array( 
					'none' => '',
					'horizontal' => 'Horizontal',
					'vertical' => 'Vertical',
				) 
			) . '</td>';

			$ret[] = $this->ngfb->util->th( 'Button Size', 'short' ) . '<td>' . 
			$this->ngfb->admin->form->get_select( 'twitter_size', 
				array( 
					'medium' => 'Medium',
					'large' => 'Large',
				)
			) . '</td>';

			$ret[] = $this->ngfb->util->th( 'Tweet Text', 'short' ) . '<td>' . 
			$this->ngfb->admin->form->get_select( 'twitter_caption', $this->captions ) . '</td>';

			$ret[] = $this->ngfb->util->th( 'Text Length', 'short' ) . '<td>' . 
			$this->ngfb->admin->form->get_input( 'twitter_cap_len', 'short' ) . ' Characters or less</td>';

			if ( $this->ngfb->is_avail['aop'] == true )
				$ret[] = $this->ngfb->util->th( 'Add via @username', 'short', null, 
				'Append the Website\'s @username (entered on the ' .
				$this->ngfb->util->get_admin_url( 'general', 'General settings page\'s' ) . ' Twitter tab) to the Tweet.
				The Website @username will also be recommended for following after the Post / Page is shared.' ) . 
				'<td>' . $this->ngfb->admin->form->get_checkbox( 'twitter_via' ) . '</td>';
			else
				$ret[] = $this->ngfb->util->th( 'Add via @username', 'short', null,
				'Append the Website\'s @username (entered on the ' .
				$this->ngfb->util->get_admin_url( 'general', 'General settings page\'s' ) . ' Twitter tab) to the Tweet.
				The Website @username will also be recommended for following after the Post / Page is shared.' ) . 
				'<td class="blank"></td>';

			$ret[] = $this->ngfb->util->th( 'Recommend Author', 'short', null, 
			'Recommend following the Author\'s Twitter @username (from their profile) after sharing. 
			If the \'<em>Add via @username</em>\' option (above) is also checked, the Website\'s @username will be suggested first.' ) . 
			'<td>' . $this->ngfb->admin->form->get_checkbox( 'twitter_rel_author' ) . '</td>';

			$ret[] = $this->ngfb->util->th( 'Do Not Track', 'short', null,
			'Disable tracking for Twitter\'s tailored suggestions and tailored ads.' ) . 
			'<td>' . $this->ngfb->admin->form->get_checkbox( 'twitter_dnt' ) . '</td>';

			$ret[] = $this->ngfb->util->th( 'Shorten URLs', 'short', null, '
				Don\'t forget to enter your <em>Goo.gl Simple API Access Key</em> value on the ' . 
				$this->ngfb->util->get_admin_url( 'advanced', 'Advanced settings page' ) . '.' ) .
				'<td>' . $this->ngfb->admin->form->get_checkbox( 'twitter_shorten' ) . '</td>';

			return $ret;
		}

	}
}

if ( ! class_exists( 'ngfbSocialTwitter' ) && class_exists( 'ngfbSocial' ) ) {

	class ngfbSocialTwitter {

		protected $ngfb;

		public function __construct( &$ngfb_plugin ) {
			$this->ngfb =& $ngfb_plugin;
			$this->ngfb->debug->mark();
		}

		public function get_html( $atts = array() ) {
			global $post; 
			$html = '';
			$prot = empty( $_SERVER['HTTPS'] ) ? 'http://' : 'https://';
			$use_post = empty( $atts['is_widget'] ) || is_singular() ? true : false;
			if ( empty( $atts['url'] ) ) 
				$atts['url'] = $this->ngfb->util->get_sharing_url( 'notrack', null, $use_post );

			$long_url = $atts['url'];
			$atts['url'] = $this->ngfb->util->get_short_url( $atts['url'], $this->ngfb->options['twitter_shorten'] );

			if ( ! empty( $atts['tweet'] ) ) 
				$atts['caption'] = $atts['tweet'];
			if ( empty( $atts['caption'] ) && $use_post == true ) 
				$atts['caption'] = $this->ngfb->meta->get_options( $post->ID, 'twitter_desc' );
			if ( empty( $atts['caption'] ) ) {
				$cap_len = $this->ngfb->util->tweet_max_len( $atts['url'] );
				$atts['caption'] = $this->ngfb->webpage->get_caption( $this->ngfb->options['twitter_caption'], $cap_len, $use_post );
			}
			if ( empty( $atts['lang'] ) ) 
				$atts['lang'] = empty( $this->ngfb->options['twitter_lang'] ) ? 'en' : $this->ngfb->options['twitter_lang'];
			if ( empty( $atts['via'] ) && ! empty( $this->ngfb->options['twitter_via'] ) )
				$atts['via'] = preg_replace( '/^@/', '', $this->ngfb->options['tc_site'] );
			if ( empty( $atts['related'] ) && ! empty( $this->ngfb->options['twitter_rel_author'] ) && $use_post == true )
				$atts['related'] = preg_replace( '/^@/', '', get_the_author_meta( NGFB_TWITTER_FIELD_ID, $post->author ) );
			if ( ! array_key_exists( 'dnt', $atts ) ) 
				$atts['dnt'] = $this->ngfb->options['twitter_dnt'] ? 'true' : 'false';

			$html = '
				<!-- Twitter Button -->
				<!-- url = ' . $long_url . ' -->
				<div ' . $this->ngfb->social->get_css( 'twitter', $atts ) . '>
					<a href="' . $prot . 'twitter.com/share" 
						class="twitter-share-button"
						lang="'. $atts['lang'] . '"
						data-url="' . $atts['url'] . '" 
						data-counturl="' . $long_url . '" 
						data-text="' . $atts['caption'] . '" 
						data-via="' . $atts['via'] . '" 
						data-related="' . $atts['related'] . '" 
						data-hashtags="' . $atts['hashtags'] . '" 
						data-count="' . $this->ngfb->options['twitter_count'] . '" 
						data-size="' . $this->ngfb->options['twitter_size'] . '" 
						data-dnt="' . $atts['dnt'] . '">Tweet</a>
				</div>' . "\n";
			$this->ngfb->debug->log( 'returning html (' . strlen( $html ) . ' chars)' );
			return $html;
		}
		
		public function get_js( $pos = 'id' ) {
			$prot = empty( $_SERVER['HTTPS'] ) ? 'http://' : 'https://';
			return '<script type="text/javascript" id="twitter-script-' . $pos . '">
				ngfb_header_js( "twitter-script-' . $pos . '", "' . $this->ngfb->util->get_cache_url( $prot . 'platform.twitter.com/widgets.js' ) . '" );
			</script>' . "\n";
		}

	}

}
?>

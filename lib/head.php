<?php
/*
License: GPLv3
License URI: http://surniaulula.com/wp-content/plugins/nextgen-facebook/license/gpl.txt
Copyright 2012-2013 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'Sorry, you cannot call this webpage directly.' );

if ( ! class_exists( 'ngfbHead' ) ) {

	class ngfbHead {

		public $og;

		private $ngfb;

		public function __construct( &$ngfb_plugin ) {
			$this->ngfb =& $ngfb_plugin;
			$this->ngfb->debug->mark();
			$this->og = new ngfbOpenGraph( $ngfb_plugin );

			add_action( 'wp_head', array( &$this, 'add_header' ), NGFB_HEAD_PRIORITY );
		}

		// called by WP wp_head action
		public function add_header() {
			if ( $this->ngfb->debug->is_on() ) {
				$defined_constants = get_defined_constants( true );
				$this->ngfb->debug->show_html( $this->ngfb->util->preg_grep_keys( '/^NGFB_/', $defined_constants['user'] ), 'NGFB Constants' );
				$this->ngfb->debug->show_html( $this->ngfb->options, 'NGFB Settings' );
				$this->ngfb->debug->show_html( $this->ngfb->is_avail, 'Available Features' );

				$this->ngfb->debug->log( 'is_archive() = ' . ( is_archive() ? 'true' : 'false' ) );
				$this->ngfb->debug->log( 'is_attachment() = ' . ( is_attachment() ? 'true' : 'false' ) );
				$this->ngfb->debug->log( 'is_category() = ' . ( is_category() ? 'true' : 'false' ) );
				$this->ngfb->debug->log( 'is_home() = ' . ( is_home() ? 'true' : 'false' ) );
				$this->ngfb->debug->log( 'is_search() = ' . ( is_search() ? 'true' : 'false' ) );
				$this->ngfb->debug->log( 'is_singular() = ' . ( is_singular() ? 'true' : 'false' ) );
			}
			$this->html( $this->og->get() );
			$this->ngfb->debug->show_html( null, 'Debug Log' );
		}

		// called from the work/header.php template
		public function html( &$arr = array() ) {
			global $post;
			$author_url = '';
		
			echo "\n<!-- ", $this->ngfb->fullname, " meta tags BEGIN -->\n";

			// show the array structure before the html block
			$this->ngfb->debug->show_html( print_r( $arr, true ), 'Open Graph Array' );
			$this->ngfb->debug->show_html( print_r( $this->ngfb->util->get_urls_found(), true ), 'URLs Found' );

			echo '<meta name="generator" content="', $this->ngfb->fullname, ' ', $this->ngfb->version, '" />', "\n";

			// echo the publisher link
			if ( ! empty( $arr['link:publisher'] ) )
				echo '<link rel="publisher" href="', $arr['link:publisher'], '" />', "\n";
			elseif ( $this->ngfb->options['link_publisher_url'] )
				echo '<link rel="publisher" href="', $this->ngfb->options['link_publisher_url'], '" />', "\n";

			// echo the author link
			if ( ! empty( $arr['link:author'] ) ) {
				echo '<link rel="author" href="', $arr['link:author'], '" />', "\n";
			} else {
				if ( ! empty( $post ) && $post->post_author )
					$author_url = $this->ngfb->user->get_author_url( $post->post_author, 
						$this->ngfb->options['link_author_field'] );

				elseif ( ! empty( $this->ngfb->options['og_def_author_id'] ) )
					$author_url = $this->ngfb->user->get_author_url( $this->ngfb->options['og_def_author_id'], 
						$this->ngfb->options['link_author_field'] );

				if ( $author_url ) echo '<link rel="author" href="', $author_url, '" />', "\n";
			}

			// echo the description meta
			if ( ! empty( $arr['og:description'] ) && ! empty( $this->ngfb->options['inc_description'] ) )
				echo '<meta name="description" content="', $arr['og:description'], '" />', "\n";

			// show the multi-dimensional array as html
			ksort( $arr );
			foreach ( $arr as $d_name => $d_val ) {						// first-dimension array (associative)
				if ( is_array( $d_val ) ) {
					foreach ( $d_val as $dd_num => $dd_val ) {			// second-dimension array
						if ( $this->ngfb->util->is_assoc( $dd_val ) ) {
							ksort( $dd_val );
							foreach ( $dd_val as $ddd_name => $ddd_val ) {	// third-dimension array (associative)
								echo $this->get_meta_html( $ddd_name, $ddd_val, $d_name . ':' . ( $dd_num + 1 ) );
							}
							unset ( $ddd_name, $ddd_val );
						} else echo $this->get_meta_html( $d_name, $dd_val, $d_name . ':' . ( $dd_num + 1 ) );
					}
					unset ( $dd_num, $dd_val );
				} else echo $this->get_meta_html( $d_name, $d_val );
			}
			unset ( $d_name, $d_val );

			echo "<!-- ", $this->ngfb->fullname, " meta tags END -->\n";
		}

		private function get_meta_html( $name, $val = '', $cmt = '' ) {
			$meta_html = '';
			if ( ! empty( $this->ngfb->options['inc_'.$name] ) && 
				( ! empty( $val ) || 
					( ! empty( $this->ngfb->options['og_empty_tags'] ) && preg_match( '/^og:/', $name ) ) ) ) {

				$charset = get_bloginfo( 'charset' );

				$val = htmlentities( $this->ngfb->util->cleanup_html_tags( $this->ngfb->util->decode( $val ) ), 
					ENT_QUOTES, $charset, false );

				if ( $cmt ) $meta_html .= "<!-- $cmt -->";
				$meta_html .= '<meta property="' . $name . '" content="' . $val . '" />' . "\n";
			}
			return $meta_html;
		}

	}

}
?>

<?php
/*
License: GPLv3
License URI: http://surniaulula.com/wp-content/plugins/nextgen-facebook/license/gpl.txt
Copyright 2012-2013 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'NgfbOpengraph' ) ) {

	class NgfbOpengraph {

		private $p;

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->debug->mark();

			add_filter( 'language_attributes', array( &$this, 'add_doctype' ) );
		}
	
		public function add_doctype( $doctype ) {
			return $doctype.' xmlns:og="http://ogp.me/ns" xmlns:fb="http://ogp.me/ns/fb"';
		}

		public function get( $post_id = false ) {
			if ( ( defined( 'DISABLE_NGFB_OPEN_GRAPH' ) && DISABLE_NGFB_OPEN_GRAPH ) || 
				( defined( 'NGFB_OPEN_GRAPH_DISABLE' ) && NGFB_OPEN_GRAPH_DISABLE ) ||
				! empty( $_SERVER['NGFB_OPEN_GRAPH_DISABLE'] ) ) {

				$this->p->debug->log( 'open graph is disabled' );
				return array();
			}

			$source_id = $this->p->util->get_source_id( 'opengraph' );
			$sharing_url = $this->p->util->get_sharing_url( false, true, $source_id );
			if ( defined( 'NGFB_TRANSIENT_CACHE_DISABLE' ) && NGFB_TRANSIENT_CACHE_DISABLE )
				$this->p->debug->log( 'transient cache is disabled' );
			else {
				$cache_salt = __METHOD__.'(lang:'.get_locale().'_sharing_url:'.$sharing_url.')';
				$cache_id = $this->p->cf['lca'].'_'.md5( $cache_salt );
				$cache_type = 'object cache';
				$this->p->debug->log( $cache_type.': og array transient salt '.$cache_salt );
				$og = get_transient( $cache_id );
				if ( is_array( $og ) ) {
					$this->p->debug->log( $cache_type.': og array retrieved from transient '.$cache_id );
					return $og;
				}
			}

			if ( ( $obj = $this->p->util->get_the_object( $post_id ) ) === false ) {
				$this->p->debug->log( 'exiting early: invalid object type' );
				return array();
			}

			$post_id = empty( $obj->ID ) ? 0 : $obj->ID;
			$post_type = '';
			$has_video_image = '';
			$og_max = $this->get_max_nums( $post_id );
			$og = apply_filters( $this->p->cf['lca'].'_og_seed', array() );
			$lang = empty( $this->p->options['fb_lang'] ) ? 'en-US' : $this->p->options['fb_lang'];
			$lang = apply_filters( $this->p->cf['lca'].'_lang', $lang, $this->p->util->get_lang( 'facebook' ) );

			if ( ! array_key_exists( 'fb:admins', $og ) )
				$og['fb:admins'] = $this->p->options['fb_admins'];

			if ( ! array_key_exists( 'fb:app_id', $og ) )
				$og['fb:app_id'] = $this->p->options['fb_app_id'];

			if ( ! array_key_exists( 'og:locale', $og ) )
				$og['og:locale'] = $lang;

			if ( ! array_key_exists( 'og:site_name', $og ) ) {
				if ( ! empty( $this->p->options['og_site_name'] ) )
					$og['og:site_name'] = $this->p->options['og_site_name'];
				else $og['og:site_name'] = get_bloginfo( 'name', 'display' );
			}

			if ( ! array_key_exists( 'og:url', $og ) )
				$og['og:url'] = $sharing_url;

			if ( ! array_key_exists( 'og:title', $og ) )
				$og['og:title'] = $this->p->webpage->get_title( $this->p->options['og_title_len'], '...' );

			if ( ! array_key_exists( 'og:description', $og ) )
				$og['og:description'] = $this->p->webpage->get_description( $this->p->options['og_desc_len'], '...' );

			if ( ! array_key_exists( 'og:type', $og ) ) {
				// singular posts/pages are articles by default
				// check post_type for exceptions (like product pages)
				if ( is_singular() ) {
					if ( ! empty( $obj->post_type ) )
						$post_type = $obj->post_type;
					switch ( $post_type ) {
						case 'product' :
							$og['og:type'] = 'product';
							break;
						default :
							$og['og:type'] = 'article';
							break;
					}
				// check for default author info on indexes and searches
				} elseif ( ( ! is_singular() && ! is_search() && ! empty( $this->p->options['og_def_author_on_index'] ) && ! empty( $this->p->options['og_def_author_id'] ) )
					|| ( is_search() && ! empty( $this->p->options['og_def_author_on_search'] ) && ! empty( $this->p->options['og_def_author_id'] ) ) ) {
	
					$og['og:type'] = "article";
					if ( ! array_key_exists( 'article:author', $og ) )
						$og['article:author'] = $this->p->user->get_author_url( $this->p->options['og_def_author_id'], 
							$this->p->options['og_author_field'] );

				// default for everything else is 'website'
				} else $og['og:type'] = 'website';
			}

			// if the page is an article, then define the other article meta tags
			if ( array_key_exists( 'og:type', $og ) && $og['og:type'] == 'article' ) {
				if ( is_singular() && ! array_key_exists( 'article:author', $og ) ) {
					if ( ! empty( $obj->post_author ) )
						$og['article:author'] = $this->p->user->get_author_url( $obj->post_author, 
							$this->p->options['og_author_field'] );
					elseif ( ! empty( $this->p->options['og_def_author_id'] ) )
						$og['article:author'] = $this->p->user->get_author_url( $this->p->options['og_def_author_id'], 
							$this->p->options['og_author_field'] );
				}
				if ( ! array_key_exists( 'article:publisher', $og ) )
					$og['article:publisher'] = $this->p->options['og_publisher_url'];

				if ( ! array_key_exists( 'article:tag', $og ) )
					$og['article:tag'] = $this->p->webpage->get_tags( $post_id );

				if ( ! array_key_exists( 'article:section', $og ) )
					$og['article:section'] = $this->p->webpage->get_section( $post_id );

				if ( ! array_key_exists( 'article:published_time', $og ) )
					$og['article:published_time'] = get_the_date('c');

				if ( ! array_key_exists( 'article:modified_time', $og ) )
					$og['article:modified_time'] = get_the_modified_date('c');
			}

			// get all videos
			// check first, to add video preview images
			if ( ! array_key_exists( 'og:video', $og ) ) {
				if ( $og_max['og_vid_max'] > 0 ) {
					$og['og:video'] = $this->get_all_videos( $og_max['og_vid_max'], $post_id );
					if ( is_array( $og['og:video'] ) ) {
						foreach ( $og['og:video'] as $val ) {
							if ( is_array( $val ) && ! empty( $val['og:image'] ) ) {
								$this->p->debug->log( 'og:image found in og:video array (no default image required)' );
								$has_video_image = 1;
							}
						}
						unset ( $vid );
					}
				} else $this->p->debug->log( 'videos disabled: maximum videos = 0' );
			}

			// get all images
			if ( ! array_key_exists( 'og:image', $og ) ) {
				if ( $og_max['og_img_max'] > 0 ) {
					$og['og:image'] = $this->get_all_images( $og_max['og_img_max'], 
						constant( $this->p->cf['uca'].'_OG_SIZE_NAME' ), $post_id );
					// if we didn't find any images, then use the default image
					if ( empty( $og['og:image'] ) && empty( $has_video_image ) )
						$og['og:image'] = $this->p->media->get_default_image( $og_max['og_img_max'], NGFB_OG_SIZE_NAME );
				} else $this->p->debug->log( 'images disabled: maximum images = 0' );
			}

			// run filter before saving to transient cache
			$og = apply_filters( $this->p->cf['lca'].'_og', $og );
			if ( ! defined( 'NGFB_TRANSIENT_CACHE_DISABLE' ) || ! NGFB_TRANSIENT_CACHE_DISABLE ) {
				set_transient( $cache_id, $og, $this->p->cache->object_expire );
				$this->p->debug->log( $cache_type.': og array saved to transient '.$cache_id.' ('.$this->p->cache->object_expire.' seconds)');
			}
			return $og;
		}

		public function get_all_videos( $num = 0, $post_id, $check_dupes = true ) {
			$this->p->debug->args( array( 'num' => $num, 'post_id' => $post_id, 'check_dupes' => $check_dupes ) );

			$og_ret = array();
			if ( ! empty( $post_id ) ) {	// post id should be > 0 for post meta
				$num_remains = $this->p->media->num_remains( $og_ret, $num );
				$og_ret = array_merge( $og_ret, 
					$this->p->media->get_meta_video( $num_remains, $post_id, $check_dupes ) );
			}

			// if we haven't reached the limit of images yet, keep going
			if ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
				$num_remains = $this->p->media->num_remains( $og_ret, $num );
				$og_ret = array_merge( $og_ret, 
					$this->p->media->get_content_videos( $num_remains, $post_id, $check_dupes ) );
			}
			$this->p->util->slice_max( $og_ret, $num );
			return $og_ret;
		}

		public function get_all_images( $num = 0, $size_name = 'thumbnail', $post_id, $check_dupes = true ) {
			$this->p->debug->args( array( 'num' => $num, 'size_name' => $size_name, 'post_id' => $post_id, 'check_dupes' => $check_dupes ) );

			$og_ret = array();
			// check for an attachment page
			if ( ! empty( $post_id ) && is_attachment( $post_id ) ) {	// post id should be > 0 for attachment pages
				$og_image = array();
				$num_remains = $this->p->media->num_remains( $og_ret, $num );
				$og_image = $this->p->media->get_attachment_image( $num_remains, $size_name, $post_id, $check_dupes );

				// if an attachment is not an image, then use the default image instead
				if ( empty( $og_ret ) ) {
					$num_remains = $this->p->media->num_remains( $og_ret, $num );
					$og_ret = array_merge( $og_ret, 
						$this->p->media->get_default_image( $num_remains, $size_name, $check_dupes ) );
				} else $og_ret = array_merge( $og_ret, $og_image );

				return $og_ret;
			}

			// check for attachment page without an image, or index-type pages with og_def_img_on_index enabled to force a default image
			if ( ( ! empty( $this->p->options['og_def_img_on_index'] ) && ( is_home() || is_archive() ) && ! is_author() ) ||
				( ! empty( $this->p->options['og_def_img_on_search'] ) && is_search() ) ) {

				$num_remains = $this->p->media->num_remains( $og_ret, $num );
				$og_ret = array_merge( $og_ret, 
					$this->p->media->get_default_image( $num_remains, $size_name, $check_dupes ) );
				return $og_ret;	// stop here and return the image array
			}

			// check for custom meta, featured, or attached image(s)
			if ( ! empty( $post_id ) ) {	// post id should be > 0
				$num_remains = $this->p->media->num_remains( $og_ret, $num );
				$og_ret = array_merge( $og_ret, 
					$this->p->media->get_post_images( $num_remains, $size_name, $post_id, $check_dupes ) );

				// keep going to find more images
				// the featured / attached image(s) will be listed first in the open graph meta property tags
				// and duplicates will be filtered out
			}

			// check for ngg shortcodes and query vars
			if ( $this->p->is_avail['ngg'] == true && ! $this->p->util->is_maxed( $og_ret, $num ) ) {
				$ngg_query_og_ret = array();
				$num_remains = $this->p->media->num_remains( $og_ret, $num );
				if ( version_compare( $this->p->ngg_version, '2.0.0', '<' ) ) {
					$ngg_query_og_ret = $this->p->media->ngg->get_query_images( $num_remains, $size_name, $check_dupes );
				}
				// if we found images in the query, skip content shortcodes
				if ( count( $ngg_query_og_ret ) > 0 ) {
					$this->p->debug->log( count( $ngg_query_og_ret ).' image(s) returned - skipping additional shortcode images' );
					$og_ret = array_merge( $og_ret, $ngg_query_og_ret );
				// if no query images were found, continue with ngg shortcodes in content
				} elseif ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
					$num_remains = $this->p->media->num_remains( $og_ret, $num );
					$og_ret = array_merge( $og_ret, 
						$this->p->media->ngg->get_shortcode_images( $num_remains, $size_name, $check_dupes ) );
				}
			}

			// if we haven't reached the limit of images yet, keep going and check the content text
			if ( ! $this->p->util->is_maxed( $og_ret, $num ) ) {
				$num_remains = $this->p->media->num_remains( $og_ret, $num );
				$og_ret = array_merge( $og_ret, 
					$this->p->media->get_content_images( $num_remains, $size_name, $post_id, $check_dupes ) );
			}

			$this->p->util->slice_max( $og_ret, $num );
			return $og_ret;
		}

		public function get_max_nums( $post_id ) {
			$og_max = array();
			foreach ( array( 'og_vid_max', 'og_img_max' ) as $max_name ) {
				$num_meta = false;
				if ( ! empty( $post_id ) )
					$num_meta = $this->p->meta->get_options( $post_id, $max_name );
				if ( $num_meta !== false ) {
					$og_max[$max_name] = $num_meta;
					$this->p->debug->log( 'found custom meta '.$max_name.' = '.$num_meta );
				} else $og_max[$max_name] = $this->p->options[$max_name];
			}
			unset ( $max_name );
			return $og_max;
		}
	}
}
?>

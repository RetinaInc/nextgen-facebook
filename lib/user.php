<?php
/*
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.txt
Copyright 2012-2014 - Jean-Sebastien Morisset - http://surniaulula.com/
*/

if ( ! defined( 'ABSPATH' ) ) 
	die( 'These aren\'t the droids you\'re looking for...' );

if ( ! class_exists( 'NgfbUser' ) ) {

	class NgfbUser {

		protected $p;
		protected $form;
		protected $header_tags = array();
		protected $post_info = array();
		protected $user_id = 0;
		protected $author = '';

		public function __construct( &$plugin ) {
			$this->p =& $plugin;
			$this->p->debug->mark();
			$this->add_actions();
		}

		protected function add_actions() {
			add_filter( 'user_contactmethods', array( &$this, 'add_contact_methods' ), 20, 1 );

			// everything bellow is for the admin interface

			if ( ! is_admin() )
				return;

			if ( $this->p->is_avail['opengraph'] )
				add_action( 'admin_head', array( &$this, 'set_header_tags' ) );

			add_action( 'admin_init', array( &$this, 'add_metaboxes' ) );
			add_action( 'show_user_profile', array( &$this, 'show_metabox' ) );
			add_action( 'edit_user_profile', array( &$this, 'show_metabox' ) );
			add_action( 'edit_user_profile_update', array( &$this, 'sanitize_contact_methods' ) );
			add_action( 'edit_user_profile_update', array( &$this, 'save_options' ) );
			add_action( 'personal_options_update', array( &$this, 'sanitize_contact_methods' ) ); 
			add_action( 'personal_options_update', array( &$this, 'save_options' ) ); 

			$this->p->util->add_plugin_filters( $this, array( 
				'title' => 1,
				'description_seed' => 1,
				'sharing_url' => 1,
			) );
		}

		public function filter_title( $title ) {
			return $this->get_author_info( 'title', $title );
		}

		public function filter_description_seed( $desc ) {
			return $this->get_author_info( 'desc' );
		}

		public function filter_sharing_url( $url ) {
			return $this->get_author_info( 'url' );
		}

		private function get_author_info( $return, $value = '' ) {
			$screen = get_current_screen();
			$page = $screen->id;
			switch ( $page ) {
				case 'user-edit':
				case 'profile':
					$user_id = empty( $_GET['user_id'] ) ? get_current_user_id() : $_GET['user_id'];
					$author = get_userdata( $user_id );
					switch ( $return ) {
						case 'title':
							$separator = html_entity_decode( $this->p->options['og_title_sep'], ENT_QUOTES, get_bloginfo( 'charset' ) );
							return $author->display_name.' '.$separator.' '.$value;
							break;
						case 'desc':
						case 'description':
							return empty( $author->description ) ? sprintf( 'Authored by %s', $author->display_name ) : $author->description;
							break;
						case 'url':
							return get_author_posts_url( $user_id );
							break;
					}
					break;
			}
			return $value;
		}

		public function add_metaboxes() {
			add_meta_box( NGFB_META_NAME, 'Social Settings', array( &$this, 'show_usermeta' ), 'user', 'normal', 'high' );
		}

		public function set_header_tags() {
			$screen = get_current_screen();
			$page = $screen->id;
			if ( $this->p->is_avail['opengraph'] && empty( $this->header_tags ) ) {
				switch ( $page ) {
					case 'user-edit':
					case 'profile':
						$this->header_tags = $this->p->head->get_header_array( false );
						$this->p->debug->show_html( null, 'debug log' );
						foreach ( $this->header_tags as $tag ) {
							if ( isset ( $tag[3] ) && $tag[3] === 'og:type' ) {
								$this->post_info['og_type'] = $tag[5];
								break;
							}
						}
						break;
				}
			}
		}

		public function show_metabox( $user ) {
			if ( ! current_user_can( 'edit_user', $user->ID ) )
				return;

			echo '<div id="poststuff">';
			do_meta_boxes( 'user', 'normal', $user );
			echo '</div>';
		}

		public function show_usermeta( $user ) {
			$def_opts = $this->get_defaults();
			$opts = $this->get_options( $user->ID );
			$screen = get_current_screen();
			$this->post_info['ptn'] = $page = $screen->id;

			$this->form = new SucomForm( $this->p, NGFB_META_NAME, $opts, $def_opts );
			wp_nonce_field( $this->get_nonce(), NGFB_NONCE );

			$metabox = 'user';
			$tabs = apply_filters( $this->p->cf['lca'].'_'.$metabox.'_tabs', array( 
				'header' => 'Header Meta Tags',
				'tags' => 'Header Tags Preview' ) );

			if ( empty( $this->p->is_avail['opengraph'] ) )
				unset( $tabs['tags'] );

			$rows = array();
			foreach ( $tabs as $key => $title )
				$rows[$key] = array_merge( $this->get_rows( $metabox, $key, $this->post_info ), 
					apply_filters( $this->p->cf['lca'].'_'.$metabox.'_'.$key.'_rows', array(), $this->form, $this->post_info ) );
			$this->p->util->do_tabs( $metabox, $tabs, $rows );
		}

		protected function get_rows( $metabox, $key, $post_info ) {
			$rows = array();
			switch ( $metabox.'-'.$key ) {
				case 'user-tags':	
					foreach ( $this->header_tags as $m ) {
						$rows[] = '<th class="xshort">'.$m[1].'</th>'.
							'<th class="xshort">'.$m[2].'</th>'.
							'<td class="short">'.$m[3].'</td>'.
							'<th class="xshort">'.$m[4].'</th>'.
							'<td class="wide">'.( strpos( $m[5], 'http' ) === 0 ? '<a href="'.$m[5].'">'.$m[5].'</a>' : $m[5] ).'</td>';
					}
					sort( $rows );
					break; 
			}
			return $rows;
		}

		public function add_contact_methods( $fields = array() ) { 
			// loop through each social website option prefix
			if ( ! empty( $this->p->cf['opt']['pre'] ) && is_array( $this->p->cf['opt']['pre'] ) ) {
				foreach ( $this->p->cf['opt']['pre'] as $id => $pre ) {
					$cm_opt = 'plugin_cm_'.$pre.'_';
					// not all social websites have a contact fields, so check
					if ( array_key_exists( $cm_opt.'name', $this->p->options ) ) {
						$enabled = $this->p->options[$cm_opt.'enabled'];
						$name = $this->p->options[$cm_opt.'name'];
						$label = $this->p->options[$cm_opt.'label'];
						if ( ! empty( $enabled ) && ! empty( $name ) && ! empty( $label ) )
							$fields[$name] = $label;
					}
				}
			}
			if ( $this->p->check->is_aop() && 
				! empty( $this->p->cf['wp']['cm'] ) && is_array( $this->p->cf['wp']['cm'] ) ) {
				foreach ( $this->p->cf['wp']['cm'] as $id => $name ) {
					$cm_opt = 'wp_cm_'.$id.'_';
					if ( array_key_exists( $cm_opt.'enabled', $this->p->options ) ) {
						$enabled = $this->p->options[$cm_opt.'enabled'];
						$label = $this->p->options[$cm_opt.'label'];
						if ( ! empty( $enabled ) ) {
							if ( ! empty( $label ) )
								$fields[$id] = $label;
						} else unset( $fields[$id] );
					}
				}
			}
			ksort( $fields, SORT_STRING );
			return $fields;
		}

		public function sanitize_contact_methods( $user_id ) {
			if ( ! current_user_can( 'edit_user', $user_id ) )
				return;

			foreach ( $this->p->cf['opt']['pre'] as $id => $pre ) {
				$cm_opt = 'plugin_cm_'.$pre.'_';
				// not all social websites have a contact fields, so check
				if ( array_key_exists( $cm_opt.'name', $this->p->options ) ) {
					$enabled = $this->p->options[$cm_opt.'enabled'];
					$name = $this->p->options[$cm_opt.'name'];
					$label = $this->p->options[$cm_opt.'label'];
					if ( ! empty( $enabled ) && ! empty( $name ) && ! empty( $label ) ) {
						// sanitize values only for those enabled contact methods
						$val = wp_filter_nohtml_kses( $_POST[$name] );
						if ( ! empty( $val ) ) {
							// use the social prefix id to decide on actions
							switch ( $id ) {
								case 'skype':
									// no change
									break;
								case 'twitter':
									$val = substr( preg_replace( '/[^a-z0-9_]/', '', 
										strtolower( $val ) ), 0, 15 );
									if ( ! empty( $val ) ) 
										$val = '@'.$val;
									break;
								default:
									if ( strpos( $val, '://' ) === false )
										$val = '';
									break;
							}
						}
						$_POST[$name] = $val;
					}
				}
			}
		}

		public function get_article_author( $author_id ) {
			$ret = array();
			if ( ! empty( $author_id ) ) {
				$ret[] = $this->get_author_website_url( $author_id, $this->p->options['og_author_field'] );

				// add the author's name if this is the Pinterest crawler
				if ( SucomUtil::crawler_name( 'pinterest' ) === true )
					$ret[] = $this->get_author_name( $author_id, $this->p->options['rp_author_name'] );

			} else $this->p->debug->log( 'author_id provided is empty' );
			return $ret;
		}

		// called from head and opengraph classes
		public function get_author_name( $author_id, $field_id = 'display_name' ) {
			$name = '';
			switch ( $field_id ) {
				case 'none':
					break;
				case 'fullname':
					$name = trim( get_the_author_meta( 'first_name', $author_id ) ).' '.
						trim( get_the_author_meta( 'last_name', $author_id ) );
					break;
				// sanitation controls, just in case ;-)
				case 'user_login':
				case 'user_nicename':
				case 'display_name':
				case 'nickname':
				case 'first_name':
				case 'last_name':
					$name = get_the_author_meta( $field_id, $author_id );	// since wp 2.8.0 
					break;
			}
			return $name;
		}

		// called from head and opengraph classes
		public function get_author_website_url( $author_id, $field_id = 'url' ) {
			$url = '';
			switch ( $field_id ) {
				case 'none':
					break;
				case 'index':
					$url = get_author_posts_url( $author_id );
					break;
				default:
					$url = get_the_author_meta( $field_id, $author_id );	// since wp 2.8.0 

					// if empty or not a url, then fallback to the author index page,
					// if the requested field is the opengraph or link author field
					if ( empty( $url ) || ! preg_match( '/:\/\//', $url ) ) {
						$this->p->debug->log( 'url value from author meta is invalid' );
						if ( ( $field_id == $this->p->options['og_author_field'] || 
							$field_id == $this->p->options['link_author_field'] ) && 
							$this->p->options['og_author_fallback'] ) {
								$this->p->debug->log( 'fetching the author index page url as fallback' );
								$url = get_author_posts_url( $author_id );
						}
					}
					break;
			}
			return $url;
		}

		public function reset_metabox_prefs( $pagehook, $box_ids = array(), $meta_name = '', $section = '', $force = false ) {
			$user_id = get_current_user_id();	// since wp 3.0
			// define a new state to set for the box_ids given
			switch ( $meta_name ) {
				case 'order':	$meta_states = array( 'meta-box-order' ); break ;
				case 'hidden':	$meta_states = array( 'metaboxhidden' ); break ;
				case 'closed':	$meta_states = array( 'closedpostboxes' ); break ;
				default: $meta_states = array( 'meta-box-order', 'metaboxhidden', 'closedpostboxes' ); break;
			}
			foreach ( $meta_states as $state ) {
				// define the meta_key for that option
				$meta_key = $state.'_'.$pagehook; 
				// an empty box_ids array means reset the whole page
				if ( $force && empty( $box_ids ) )
					delete_user_option( $user_id, $meta_key, true );
				$is_changed = false;
				$is_default = false;
				$opts = get_user_option( $meta_key, $user_id );
				if ( ! is_array( $opts ) ) {
					$is_changed = true;
					$is_default = true;
					$opts = array();
				}
				if ( $is_default || $force ) {
					foreach ( $box_ids as $id ) {
						// change the order only if forced (default is controlled by add_meta_box() order)
						if ( $force && $state == 'meta-box-order' && ! empty( $opts[$section] ) ) {
							// don't proceed if the metabox is already first
							if ( strpos( $opts[$section], $pagehook.'_'.$id ) !== 0 ) {
								$boxes = explode( ',', $opts[$section] );
								// remove the box, no matter its position in the array
								if ( $key = array_search( $pagehook.'_'.$id, $boxes ) !== false )
									unset( $boxes[$key] );
								// assume we want to be top-most
								array_unshift( $boxes, $pagehook.'_'.$id );
								$opts[$section] = implode( ',', $boxes );
								$is_changed = true;
							}
						} else {
							// check to see if the metabox is present for that state
							$key = array_search( $pagehook.'_'.$id, $opts );

							// if we're not targetting , then clear it
							if ( empty( $meta_name ) && $key !== false ) {
								unset( $opts[$key] );
								$is_changed = true;
							// otherwise if we want a state, add if it's missing
							} elseif ( ! empty( $meta_name ) && $key === false ) {
								$opts[] = $pagehook.'_'.$id;
								$is_changed = true;
							}
						}
					}
				}
				if ( $is_default || $is_changed )
					update_user_option( $user_id, $meta_key, array_unique( $opts ), true );
			}
		}

		static function delete_metabox_prefs( $user_id = false ) {
			$cf = NgfbConfig::get_config();

			$parent_slug = 'options-general.php';
			foreach ( array_keys( $cf['lib']['setting'] ) as $id ) {
				$menu_slug = $cf['lca'].'-'.$id;
				self::delete_metabox_pagehook( $user_id, $menu_slug, $parent_slug );
			}

			$parent_slug = $cf['lca'].'-'.key( $cf['lib']['submenu'] );
			foreach ( array_keys( $cf['lib']['submenu'] ) as $id ) {
				$menu_slug = $cf['lca'].'-'.$id;
				self::delete_metabox_pagehook( $user_id, $menu_slug, $parent_slug );
			}
		}

		static function delete_metabox_pagehook( $user_id, $menu_slug, $parent_slug ) {
			$pagehook = get_plugin_page_hookname( $menu_slug, $parent_slug);
			foreach ( array( 'meta-box-order', 'metaboxhidden', 'closedpostboxes' ) as $state ) {
				$meta_key = $state.'_'.$pagehook;
				if ( $user_id !== false )
					delete_user_option( $user_id, $meta_key, true );
				else foreach ( get_users( array( 'meta_key' => $meta_key ) ) as $user )
					delete_user_option( $user->ID, $meta_key, true );
			}
		}

		public function get_options( $user_id = false, $idx = '' ) {
			if ( ! empty( $idx ) ) return false;
			else return array();
		}

		public function get_defaults( $idx = '' ) {
			if ( ! empty( $idx ) ) return false;
			else return array();
		}

		public function save_options( $user_id = false ) {
			return;
		}

		protected function get_nonce() {
			return plugin_basename( __FILE__ );
		}
	}
}

?>

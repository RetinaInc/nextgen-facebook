<?php
/*
Plugin Name: NextGEN Facebook
Plugin URI: http://wordpress.org/extend/plugins/nextgen-facebook/
Description: Adds Open Graph meta tags for Facebook, Google+, LinkedIn, etc. Also supports optional Like &amp; Send Facebook buttons.
Version: 1.5.2
Author: Jean-Sebastien Morisset
Author URI: http://trtms.com/

This plugin is based on the WP Facebook Like Send & Open Graph Meta v1.2.3
plugin by Marvie Pons.

The NextGEN Facebook plugin adds Open Graph meta tags to all webpage headers,
including the "artical" object type for posts and pages. The featured image
thumbnails, from a NextGEN Gallery or Media Library, are also correctly listed
in the "image" meta tag. This plugin goes well beyond any other plugins I know
in handling various archive-type webpages. It will create appropriate title
and description meta tags for category, tag, date based archive (day, month,
or year), author webpages and search results. You can also, optionally, add Facebook like and
send buttons to your posts and pages.

The Open Graph protocol enables any web page to become a rich object in a
social graph. For instance, this is used on Facebook to allow any web page to
have the same functionality as any other object on Facebook. The Open Graph
meta tags are read by almost all social websites, including Facebook, Google
(Search and Google+), and LinkedIn.

NextGEN Facebook was specifically written to support featured images located
in a NextGEN Gallery, but also works just as well with the WordPress Media
Library. The NextGEN Gallery plugin is not required to use this plugin - all
features work just as well without it. The image used in the Open Graph meta
tag is chosen in this sequence; a featured image from a NextGEN Gallery or
WordPress Media Library, the first NextGEN [singlepic] or IMG HTML tag in the
content, a default image defined in the plugin settings. If none of these
conditions can be satisfied, then the Open Graph image tag will be left empty.

This plugin is being actively developed and supported. Post your comments and
suggestions to the NextGEN Facebook Support Page at
http://wordpress.org/support/plugin/nextgen-facebook.

Copyright 2012 Jean-Sebastien Morisset

This script is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This script is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

function ngfb_requires_wordpress_version() {
	global $wp_version;
	$plugin = plugin_basename( __FILE__ );
	$plugin_data = get_plugin_data( __FILE__, false );
	if ( version_compare($wp_version, "3.0", "<" ) ) {
		if( is_plugin_active($plugin) ) {
			deactivate_plugins( $plugin );
			wp_die( "'".$plugin_data['Name']."' requires WordPress 3.0 or
			higher and has been deactivated. Please upgrade WordPress and try
			again.<br /><br />Back to <a href='".admin_url()."'>WordPress
			admin</a>." );
		}
	}
}
add_action( 'admin_init', 'ngfb_requires_wordpress_version' );

register_activation_hook(__FILE__, 'ngfb_add_defaults');
register_uninstall_hook(__FILE__, 'ngfb_delete_plugin_options');
add_action('admin_init', 'ngfb_init' );
add_action('admin_menu', 'ngfb_add_options_page');
add_filter('plugin_action_links', 'ngfb_plugin_action_links', 10, 2);

// Delete options table entries ONLY when plugin deactivated AND deleted
function ngfb_delete_plugin_options() {
	delete_option('ngfb_options');
}

// Define default option settings
function ngfb_add_defaults() {

	$tmp = get_option('ngfb_options');

    if( ( $tmp['ngfb_reset'] == '1' ) || ( !is_array($tmp) ) ) {
		delete_option('ngfb_options');	// remove old options, if any
		$arr = array(
			"og_art_section" => "",
			"og_img_size" => "thumbnail",
			"og_def_img_id_pre" => "",
			"og_def_img_id" => "",
			"og_def_img_url" => "",
			"og_def_on_home" => "",
			"og_def_on_search" => "true",
			"og_ngg_tags" => "",
			"og_desc_strip" => "",
			"og_desc_wiki" => "",
			"og_wiki_tag" => "Wiki-",
			"og_desc_len" => "300",
			"og_admins" => "",
			"og_app_id" => "",
			"fb_enable" => "",
			"fb_on_home" => "",
			"fb_send" => "true",
			"fb_layout" => "button_count",
			"fb_colorscheme" => "light",
			"fb_font" => "arial",
			"fb_show_faces" => "false",
			"fb_action" => "like",
			"gp_enable" => "",
			"gp_on_home" => "",
			"gp_size" => "small",
			"gp_annotation" => "bubble",
			"twitter_enable" => "",
			"twitter_on_home" => "",
			"twitter_count" => "horizontal",
			"twitter_size" => "medium",
			"twitter_dnt" => "true",
		);
		update_option('ngfb_options', $arr);
	}
}

// Init plugin options to white list our options
function ngfb_init(){
	register_setting( 'ngfb_plugin_options', 'ngfb_options', 'ngfb_validate_options' );
}

// Add menu page
function ngfb_add_options_page() {
	add_options_page('NextGEN Facebook Options Page', 'NextGEN Facebook', 'manage_options', 'ngfb', 'ngfb_render_form');
}

// Render the Plugin options form
function ngfb_render_form() {

	// list from http://en.wikipedia.org/wiki/Category:Websites_by_topic
	$article_sections = array(
		'Animation',
		'Architecture',
		'Art',
		'Automotive',
		'Aviation',
		'Chat',
		'Children\'s',
		'Comics',
		'Commerce',
		'Community',
		'Dance',
		'Dating',
		'Digital Media',
		'Documentary',
		'Download',
		'Economics',
		'Educational',
		'Employment',
		'Entertainment',
		'Environmental',
		'Erotica and Pornography',
		'Fashion',
		'File Sharing',
		'Food and Drink',
		'Fundraising',
		'Genealogy',
		'Health',
		'History',
		'Humor',
		'Law Enforcement',
		'Legal',
		'Literature',
		'Medical',
		'Military',
		'News',
		'Nostalgia',
		'Parenting',
		'Photography',
		'Political',
		'Religious',
		'Review',
		'Reward',
		'Route Planning',
		'Satirical',
		'Science Fiction',
		'Science',
		'Shock',
		'Social Networking',
		'Spiritual',
		'Sport',
		'Technology',
		'Travel',
		'Vegetarian',
		'Webmail',
		'Women\'s',
	);
	sort ( $article_sections );

	?>
	<div class="wrap">
	<div class="icon32" id="icon-options-general"><br></div>
	<h2>NextGEN Facebook Plugin</h2>

	<p>Once enabled, the NextGEN Facebook plugin will add Open Graph meta tags to your webpages. If your Post or Page has a featured image defined, it will be included in the meta tags for Facebook's share and like features. All options bellow are optional. You can enable share / like buttons, add a default image when there's no featured image defined, etc.</p>

	<p>The image used in the Open Graph meta tag will be determined in this sequence; a featured image from a NextGEN Gallery or WordPress Media Library, the first NextGEN [singlepic] or IMG HTML tag in the content, the default image defined bellow. If none of these conditions can be satisfied, then the Open Graph image tag will be left empty.</p>

	<p><strong>If you like NextGEN Facebook, <a href="http://wordpress.org/extend/plugins/nextgen-facebook/"><strong>please take a moment to rate it</strong></a> on the WordPress plugin page.</strong></p>

	<div class="metabox-holder">
		<div class="postbox">
			<h3>Facebook Open Graph Settings</h3>
			<div class="inside">	
	
	<!-- Beginning of the Plugin Options Form -->
	<form method="post" action="options.php">
		<?php 
			settings_fields('ngfb_plugin_options');
			$options = get_option('ngfb_options');

			// update option field names
			if ( ! $options['og_def_img_url'] && $options['og_def_img'] ) {
				$options['og_def_img_url'] = $options['og_def_img'];
				delete_option($options['og_def_img']);
			}
			if ( ! $options['og_def_on_home'] && $options['og_def_home']) {
				$options['og_def_on_home'] = $options['og_def_home'];
				delete_option($options['og_def_home']);
			}

		?>
		<table class="form-table">

			<tr>
				<th scope="row">Website Topic</th>
				<td valign="top">
					<select name='ngfb_options[og_art_section]' style="width:250px;">
						<?php
							echo '<option value="" ', 
								selected($options['og_art_section'], ''), 
								'></option>', "\n";

							foreach ( $article_sections as $s ) {
								echo '<option value="', $s, '" ',
									selected( $options['og_art_section'], $s),
										'>', $s, '</option>', "\n";
							}
							unset ( $s );
						?>
					</select>
				</td><td>
					<p>The topic which best describes the posts and pages on
					your website. This topic name will be used in the
					"article:section" meta tag of all your posts and pages. You
					can leave the topic name blank, if you would prefer not to
					include an "article:section" meta tag.</p>
				</td>
			</tr>

			<tr>
				<th>Image Size Name</th>
				<td valign="top">
					<select name='ngfb_options[og_img_size]' style="width:250px;">
					<?php
						global $_wp_additional_image_sizes;
						// Display the sizes in the array
						foreach ( get_intermediate_image_sizes() as $s ) {
							// Don't make or numeric sizes that appear
							if( is_integer( $s ) ) continue;
	
							if ( isset( $_wp_additional_image_sizes[$s]['width'] ) ) // For theme-added sizes
								$width = intval( $_wp_additional_image_sizes[$s]['width'] );
							else                                                     // For default sizes set in options
								$width = get_option( "{$s}_size_w" );
			
							if ( isset( $_wp_additional_image_sizes[$s]['height'] ) ) // For theme-added sizes
								$height = intval( $_wp_additional_image_sizes[$s]['height'] );
							else                                                      // For default sizes set in options
								$height = get_option( "{$s}_size_h" );
			
							if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) )   // For theme-added sizes
								$crop = intval( $_wp_additional_image_sizes[$s]['crop'] );
							else                                                      // For default sizes set in options
								$crop = get_option( "{$s}_crop" );

							echo "<option value='$s' ".(selected($options['og_img_size'], $s)).">$s (${width} x ${height}".($crop ? " cropped" : "").")</option>\n";
						}
						unset ( $s );
					?>
					</select>
				</td><td>
					<p>The WordPress Media Library size name for the image used
					in the Open Graph meta tag. Generally this would be
					"thumbnail" (currently defined as <?php echo
					get_option('thumbnail_size_w'); ?> x <?php echo
					get_option('thumbnail_size_h'); ?>, <?php echo
					get_option('thumbnail_crop') == "1" ? "" : "not"; ?>
					cropped), or other size names like "medium", "large", etc.
					Choose a size name that is at least 200px or more in width
					and height, and preferably cropped. You can use the <a
					href="http://wordpress.org/extend/plugins/simple-image-sizes/"
					target="_blank">Simple Image Size</a> plugin (or others) to
					define your own custom size names on the <a
					href="options-media.php">Settings -&gt; Media</a> page. I
					would suggest creating a "facebook-thumbnail" size name of
					200 x 200 (or larger) cropped, to manage the size of Open
					Graph images independently from those of your theme.</p>
				</td>
			</tr>

			<tr>
				<th scope="row">Default Image ID</th>
				<td valign="top"><input type="text" name="ngfb_options[og_def_img_id]" size="6"
					value="<?php echo $options['og_def_img_id']; ?>" />
					in the
					<select name='ngfb_options[og_def_img_id_pre]' style="width:150px;">
						<option value='' <?php selected($options['og_def_img_id_pre'], ''); ?>>Media Library</option>
						<option value='ngg' <?php selected($options['og_def_img_id_pre'], 'ngg'); ?>>NextGEN Gallery</option>
					</select>
				</td><td>
					<p>The ID number and location of your default image (example: 123).</p>
				</td>
			</tr>

			<tr>
				<th scope="row">Default Image URL</th>
				<td colspan="2"><input type="text" name="ngfb_options[og_def_img_url]" size="80"
					value="<?php echo $options['og_def_img_url']; ?>" style="width:100%;"/>

					<p>You can specify a Default Image URL (including the
					http:// prefix) instead of a Default Image ID. This would
					allow you to use an image outside of a managed collection
					(Media Library or NextGEN Gallery). The image should be at
					least 200px or more in width and height. If both are
					specified, the Default Image ID takes precedence.</p>
				</td>
			</tr>

			<tr>
				<th scope="row" nowrap>Default Image on Multi-Entry Pages</th>
				<td valign="top"><input name="ngfb_options[og_def_on_home]" type="checkbox" value="1" 
					<?php if (isset($options['og_def_on_home'])) { checked('1', $options['og_def_on_home']); } ?> />
				</td><td>
					<p>Check this box if you would like to use the default
					image on page types with more than one entry (homepage,
					archives, categories, etc.). If you leave this un-checked,
					NextGEN Facebook will attempt to use the first featured
					image, [singlepic] shortcode, or IMG HTML tag within the
					list of entries on the page.</p>
				</td>
			</tr>

			<tr>
				<th scope="row" nowrap>Default Image on Search Page</th>
				<td valign="top"><input name="ngfb_options[og_def_on_search]" type="checkbox" value="1" 
					<?php if (isset($options['og_def_on_search'])) { checked('1', $options['og_def_on_search']); } ?> />
				</td><td>
					<p>Check this box if you would like to use the default
					image on search page results as well.</p>
				</td>
			</tr>

			<tr>
				<th scope="row" nowrap>Content Begins at First Paragraph</th>
				<td valign="top"><input name="ngfb_options[og_desc_strip]" type="checkbox" value="1" 
					<?php if (isset($options['og_desc_strip'])) { checked('1', $options['og_desc_strip']); } ?> />
				</td><td>
					<p>For a page or post <i>without</i> an excerpt, the plugin
					will ignore all text until the first &lt;p&gt; paragraph
					HTML tag in <i>the content</i>. If an excerpt exists, then it's
					complete text will be used instead.</p>
				</td>
			</tr>

			<?php 
				// hide WP-WikiBox option if not installed and activated
				if ( ! function_exists( 'wikibox_summary' ) ) echo "<!-- "; 
			?>
			<tr>
				<th scope="row" nowrap>Use WP-WikiBox for Pages</th>
				<td valign="top"><input name="ngfb_options[og_desc_wiki]" type="checkbox" value="1" 
					<?php if (isset($options['og_desc_wiki'])) { checked('1', $options['og_desc_wiki']); } ?> />
				</td><td>
					<p>NextGEN Facebook can ignore the content of your pages
					when creating the "description" Open Graph meta tag, and
					retrieve it from Wikipedia instead. This only aplies to
					pages, not posts. Here's how it works; the plugin will
					check for the page's tags and use their names to retrieve
					content from Wikipedia. If no tags are defined, then the
					page title will be used. If Wikipedia does not return a
					summary for the tags or title, then the content of your
					page will be used.</p>
				</td>
			</tr>

			<tr>
				<th scope="row">WP-WikiBox Tag Prefix</th>
				<td valign="top"><input type="text" size="6" name="ngfb_options[og_wiki_tag]" 
					value="<?php echo $options['og_wiki_tag']; ?>" />
				</td><td>
					<p>A prefix to identify the WordPress tag names used to
					retrieve Wikipedia content. Leave this option blank to use
					all tags associated to a post, or choose a prefix (like
					"Wiki-") to use only tag names starting with that
					prefix.</p>
				</td>
			</tr>
			<?php if ( ! function_exists( 'wikibox_summary' ) ) echo "--> "; ?>

			<tr>
				<th scope="row">Max Description Length</th>
				<td valign="top"><input type="text" size="6" name="ngfb_options[og_desc_len]" 
					value="<?php echo $options['og_desc_len']; ?>" />
				</td><td>
					<p>The maximum length of text, from your post / page
					excerpt or content, used in the Open Graph description tag.
					The length must be 160 characters or more (the default is
					300).</p>
				</td>
			</tr>

			<tr>
				<th scope="row" nowrap>Add NextGEN Gallery Tags</th>
				<td valign="top"><input name="ngfb_options[og_ngg_tags]" type="checkbox" value="1" 
					<?php if (isset($options['og_ngg_tags'])) { checked('1', $options['og_ngg_tags']); } ?> />
				</td><td>
					<p>If the featured or default image is from a NextGEN
					Gallery, then add that image's tags to the Open Graph tag
					list.</p>
				</td>
			</tr>

			<tr>
				<th scope="row">Facebook Admin(s)</th>
				<td valign="top"><input type="text" size="40" name="ngfb_options[og_admins]" 
					value="<?php echo $options['og_admins']; ?>" style="width:250px;" />
				</td><td>
					<p>Enter one of more Facebook account names (generally your
					own), seperated with a comma. When you are viewing your own
					Facebook wall, your account name is located in the URL.
					(example:
					https://www.facebook.com/<b>account_name</b>).</p>
				</td>
			</tr>

			<tr>
				<th scope="row">Facebook App ID</th>
				<td valign="top"><input type="text" size="40" name="ngfb_options[og_app_id]" 
					value="<?php echo $options['og_app_id']; ?>" style="width:250px;" />
				</td><td>
					<p>If you have a Facebook App ID, enter it here.</p>
				</td>
			</tr>
		</table>
			</div>
		</div>

		<div class="postbox">
			<h3>Facebook Button Settings</h3>
			<div class="inside">	
		<table class="form-table">
			<tr valign="top">
				<th scope="row" nowrap>Enable Facebook Button(s)</th>
				<td valign="top"><input name="ngfb_options[fb_enable]" type="checkbox" value="1" 
					<?php if (isset($options['fb_enable'])) { checked('1', $options['fb_enable']); } ?> />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" nowrap>Include on Homepage</th>
				<td valign="top"><input name="ngfb_options[fb_on_home]" type="checkbox" value="1"
					<?php if (isset($options['fb_on_home'])) { checked('1', $options['fb_on_home']); } ?> /></td>
			</tr>
			<tr valign="top">
				<th scope="row" nowrap>Add Send Button</th>
				<td valign="top"><input name="ngfb_options[fb_send]" type="checkbox" value="true"
					<?php if (isset($options['fb_send'])) { checked('true', $options['fb_send']); } ?> /></td>
			</tr>
			<tr>
				<th scope="row">Button Layout Style</th>
				<td valign="top">
					<select name='ngfb_options[fb_layout]' style="width:250px;">
						<option value='standard' <?php selected($options['fb_layout'], 'standard'); ?>>Standard</option>
						<option value='button_count' <?php selected($options['fb_layout'], 'button_count'); ?>>Button Count</option>
						<option value='box_count' <?php selected($options['fb_layout'], 'box_count'); ?>>Box Count</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">Show Facebook Faces</th>
				<td valign="top">
					<select name='ngfb_options[fb_show_faces]' style="width:250px;">
						<option value='true' <?php selected($options['fb_show_faces'], 'true'); ?>>Show</option>
						<option value='false' <?php selected($options['fb_show_faces'], 'false'); ?>>Hide</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">Button Font</th>
				<td valign="top">
					<select name='ngfb_options[fb_font]' style="width:250px;">
						<option value='arial' <?php selected('arial', $options['fb_font']); ?>>Arial</option>
						<option value='lucida grande' <?php selected('lucida grande', $options['fb_font']); ?>>Lucida Grande</option>
						<option value='segoe ui' <?php selected('segoe ui', $options['fb_font']); ?>>Segoe UI</option>
						<option value='tahoma' <?php selected('tahoma', $options['fb_font']); ?>>Tahoma</option>
						<option value='trebuchet ms' <?php selected('trebuchet ms', $options['fb_font']); ?>>Trebuchet MS</option>
						<option value='verdana' <?php selected('verdana', $options['fb_font']); ?>>Verdana</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">Button Color Scheme</th>
				<td valign="top">
					<select name='ngfb_options[fb_colorscheme]' style="width:250px;">
						<option value='light' <?php selected('light', $options['fb_colorscheme']); ?>>Light</option>
						<option value='dark' <?php selected('dark', $options['fb_colorscheme']); ?>>Dark</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">Facebook Action Name</th>
				<td valign="top">
					<select name='ngfb_options[fb_action]' style="width:250px;">
						<option value='like' <?php selected('like', $options['fb_action']); ?>>Like</option>
						<option value='recommend' <?php selected('recommend', $options['fb_action']); ?>>Recommend</option>
					</select>
				</td>
			</tr>				
		</table>
			</div>
		</div>

		<div class="postbox">
			<h3>Google+ Button Settings</h3>
			<div class="inside">	
		<table class="form-table">
			<tr valign="top">
				<th scope="row" nowrap>Enable Google+ Button</th>
				<td valign="top"><input name="ngfb_options[gp_enable]" type="checkbox" value="1" 
					<?php if (isset($options['gp_enable'])) { checked('1', $options['gp_enable']); } ?> />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" nowrap>Include on Homepage</th>
				<td valign="top"><input name="ngfb_options[gp_on_home]" type="checkbox" value="1"
					<?php if (isset($options['gp_on_home'])) { checked('1', $options['gp_on_home']); } ?> /></td>
			</tr>
			<tr>
				<th scope="row">Button Size</th>
				<td valign="top">
					<select name='ngfb_options[gp_size]' style="width:250px;">
						<option value='small' <?php selected($options['gp_size'], 'small'); ?>>Small (15px)</option>
						<option value='medium' <?php selected($options['gp_size'], 'medium'); ?>>Medium (20px)</option>
						<option value='standard' <?php selected($options['gp_size'], 'standard'); ?>>Standard (24px)</option>
						<option value='tall' <?php selected($options['gp_size'], 'tall'); ?>>Tall (60px)</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">Annotation</th>
				<td valign="top">
					<select name='ngfb_options[gp_annotation]' style="width:250px;">
						<option value='inline' <?php selected($options['gp_annotation'], 'inline'); ?>>Inline</option>
						<option value='bubble' <?php selected($options['gp_annotation'], 'bubble'); ?>>Bubble</option>
						<option value='none' <?php selected($options['gp_annotation'], 'none'); ?>>None</option>
					</select>
				</td>
			</tr>
		</table>
			</div>
		</div>

		<div class="postbox">
			<h3>Twitter Button Settings</h3>
			<div class="inside">	
		<table class="form-table">
			<tr valign="top">
				<th scope="row" nowrap>Enable Twitter Button</th>
				<td valign="top"><input name="ngfb_options[twitter_enable]" type="checkbox" value="1" 
					<?php if (isset($options['twitter_enable'])) { checked('1', $options['twitter_enable']); } ?> />
				</td>
			</tr>
			<tr valign="top">
				<th scope="row" nowrap>Include on Homepage</th>
				<td valign="top"><input name="ngfb_options[twitter_on_home]" type="checkbox" value="1"
					<?php if (isset($options['twitter_on_home'])) { checked('1', $options['twitter_on_home']); } ?> /></td>
			</tr>
			<tr>
				<th scope="row">Count Box Position</th>
				<td valign="top">
					<select name='ngfb_options[twitter_count]' style="width:250px;">
						<option value='horizontal' <?php selected($options['twitter_count'], 'horizontal'); ?>>Horizontal</option>
						<option value='vertical' <?php selected($options['twitter_count'], 'vertical'); ?>>Vertical</option>
						<option value='none' <?php selected($options['twitter_count'], 'none'); ?>>None</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">Button Size</th>
				<td valign="top">
					<select name='ngfb_options[twitter_size]' style="width:250px;">
						<option value='medium' <?php selected($options['twitter_size'], 'medium'); ?>>Medium</option>
						<option value='large' <?php selected($options['twitter_size'], 'large'); ?>>Large</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">Do Not Track</th>
				<td valign="top">
					<select name='ngfb_options[twitter_dnt]' style="width:250px;">
						<option value='true' <?php selected($options['twitter_dnt'], 'true'); ?>>True</option>
						<option value='false' <?php selected($options['twitter_dnt'], 'false'); ?>>False</option>
					</select>
				</td>
			</tr>
		</table>
			</div>
		</table>
			</div>
		</div>

		<div class="postbox">
			<h3>Plugin Settings</h3>
			<div class="inside">	
		<table class="form-table">
			<tr>
				<th scope="row" nowrap>Reset Settings on Activate</th>
				<td valign="top"><input name="ngfb_options[ngfb_reset]" type="checkbox" value="1" 
					<?php if (isset($options['ngfb_reset'])) { checked('1', $options['ngfb_reset']); } ?> />
				</td><td>
					<p>Check this option to reset NextGEN Facebook settings to
					their default values <u>when you deactivate, and then
					reactivate the plugin</u>.</p>
				</td>
			</tr>
		</table>
			</div><!-- .inside -->
		</div><!-- .postbox -->
	</div><!-- .metabox-holder -->

		<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />

	</form>
</div>
	<?php	
}

// Sanitize and validate input
function ngfb_validate_options($input) {

	$input['og_img_size'] = wp_filter_nohtml_kses($input['og_img_size']);
	if (! $input['og_img_size']) $input['og_img_size'] = "thumbnail";

	$input['og_def_img_url'] = wp_filter_nohtml_kses($input['og_def_img_url']);
	$input['og_admins'] = wp_filter_nohtml_kses($input['og_admins']);
	$input['og_app_id'] = wp_filter_nohtml_kses($input['og_app_id']);

	if ( ! is_numeric( $input['og_def_img_id'] ) ) $input['og_def_img_id'] = null;

	if ( ! $input['og_desc_len'] 
		|| ! is_numeric( $input['og_desc_len'] ) 
		|| ! $input['og_desc_len'] > 160 )
			$input['og_desc_len'] = 160;

	if ( ! isset( $input['og_desc_strip'] ) ) $input['og_desc_strip'] = null;
	$input['og_desc_strip'] = ( $input['og_desc_strip'] == 1 ? 1 : 0 );
	
	if ( ! isset( $input['og_desc_wiki'] ) ) $input['og_desc_wiki'] = null;
	$input['og_desc_wiki'] = ( $input['og_desc_wiki'] == 1 ? 1 : 0 );
	
	if ( ! isset( $input['og_ngg_tags'] ) ) $input['og_ngg_tags'] = null;
	$input['og_ngg_tags'] = ( $input['og_ngg_tags'] == 1 ? 1 : 0 );
	
	if ( ! isset( $input['og_def_on_home'] ) ) $input['og_def_on_home'] = null;
	$input['og_def_on_home'] = ( $input['og_def_on_home'] == 1 ? 1 : 0 );
	
	if ( ! isset( $input['og_def_on_search'] ) ) $input['og_def_on_search'] = null;
	$input['og_def_on_search'] = ( $input['og_def_on_search'] == 1 ? 1 : 0 );
	
	if ( ! isset( $input['fb_enable'] ) ) $input['fb_enable'] = null;
	$input['fb_enable'] = ( $input['fb_enable'] == 1 ? 1 : 0 );
	
	if ( ! isset( $input['fb_on_home'] ) ) $input['fb_on_home'] = null;
	$input['fb_on_home'] = ( $input['fb_on_home'] == 1 ? 1 : 0 );
	
	if ( ! isset( $input['fb_send'] ) ) $input['fb_send'] = null;
	$input['fb_send'] = ( $input['fb_send'] == "true" ? "true" : "false" );

	if ( ! isset( $input['gp_enable'] ) ) $input['gp_enable'] = null;
	$input['gp_enable'] = ( $input['gp_enable'] == 1 ? 1 : 0 );
	
	if ( ! isset( $input['gp_on_home'] ) ) $input['gp_on_home'] = null;
	$input['gp_on_home'] = ( $input['gp_on_home'] == 1 ? 1 : 0 );
	
	$input['gp_size'] = wp_filter_nohtml_kses($input['gp_size']);
	if (! $input['gp_size']) $input['gp_size'] = "medium";

	$input['gp_annotate'] = wp_filter_nohtml_kses($input['gp_annotate']);
	if (! $input['gp_annotate']) $input['gp_annotate'] = "bubble";

	if ( ! isset( $input['twitter_enable'] ) ) $input['twitter_enable'] = null;
	$input['twitter_enable'] = ( $input['twitter_enable'] == 1 ? 1 : 0 );
	
	if ( ! isset( $input['twitter_on_home'] ) ) $input['twitter_on_home'] = null;
	$input['twitter_on_home'] = ( $input['twitter_on_home'] == 1 ? 1 : 0 );
	
	$input['twitter_count'] = wp_filter_nohtml_kses($input['twitter_count']);
	if (! $input['twitter_count']) $input['twitter_count'] = "horizontal";

	$input['twitter_size'] = wp_filter_nohtml_kses($input['twitter_size']);
	if (! $input['twitter_size']) $input['twitter_size'] = "medium";

	$input['twitter_dnt'] = wp_filter_nohtml_kses($input['twitter_dnt']);
	if (! $input['twitter_dnt']) $input['twitter_dnt'] = "true";

	if ( ! isset( $input['ngfb_reset'] ) ) $input['ngfb_reset'] = null;
	$input['ngfb_reset'] = ( $input['ngfb_reset'] == 1 ? 1 : 0 );
	
	return $input;
}

// Display a Settings link on the main Plugins page
function ngfb_plugin_action_links( $links, $file ) {

	if ( $file == plugin_basename( __FILE__ ) ) {
		$ngfb_links = '<a href="'.get_admin_url().'options-general.php?page=ngfb">'.__('Settings').'</a>';
		// make the 'Settings' link appear first
		array_unshift( $links, $ngfb_links );
	}

	return $links;
}

function ngfb_add_buttons( $content ) {

	$options = get_option('ngfb_options');

	if ($options['fb_enable']) $buttons .= ngfb_fb_button( );
	if ($options['gp_enable']) $buttons .= ngfb_gp_button( );
	if ($options['twitter_enable']) $buttons .= ngfb_twitter_button( );

	return $content.'<div class="ngfb-buttons">'.$buttons.'</div>';
}
add_action('the_content', 'ngfb_add_buttons');

function ngfb_fb_button( $content ) {

	$options = get_option('ngfb_options');

	$fb_send = $options['fb_send'];
	if($fb_send == '') { $fb_send = 'true'; }
	
	$fb_layout = $options['fb_layout'];
	if($fb_layout == '') { $fb_layout = 'button_count'; }
	
	$fb_show_faces = $options['fb_show_faces'];
	if($fb_show_faces == '') { $fb_show_faces = 'false'; }
	
	$fb_colorscheme = $options['fb_colorscheme'];
	if($fb_colorscheme == '') { $fb_colorscheme = 'light'; }
	
	$fb_action = $options['fb_action'];
	if($fb_action == '') { $fb_action = 'like'; }
	
	$fb_font = $options['fb_font'];
	if($fb_font == '') { $fb_font = 'arial'; }

	$button = "\n<!-- Facebook Button(s) Added by NextGEN Facebook Plugin -->\n";

	$button .= '<div class="facebook-button"><span class="fb-root"><fb:like 
		href="'.get_permalink($post->ID).'"
		send="'.$fb_send.'" layout="'.$fb_layout.'" width="400"
		show_faces="'.$fb_show_faces.'" font="'.$fb_font.'" action="'.$fb_action.'"
		colorscheme="'.$fb_colorscheme.'"></fb:like></span></div>'."\n";

	$button .= '<script src="http://connect.facebook.net/en_US/all.js#xfbml=1"></script>'."\n";

	if ( !is_feed() && !is_home() ) $content .= $button;
	elseif ( $options['fb_on_home'] ) $content .= $button;

	return $content;
}

function ngfb_gp_button( $content ) {

	$options = get_option('ngfb_options');

	$gp_size = $options['gp_size'];
	if($gp_size == '') { $gp_size = 'small'; }
	
	$gp_annotation = $options['gp_annotation'];
	if($gp_annotation == '') { $gp_annotation = 'bubble'; }
	
	$button = "\n<!-- Google+ Button Added by NextGEN Facebook Plugin -->\n";

	$button .= '<div class="g-plusone-button"><span class="g-plusone" 
		data-size="'.$gp_size.'" data-href="'.get_permalink($post->ID).'"></span></div>'."\n";

	$button .= '<script type="text/javascript">(function() {
		var po = document.createElement("script"); po.type = "text/javascript"; po.async = true;
		po.src = "https://apis.google.com/js/plusone.js";
		var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(po, s); })();</script>'."\n";

	if ( !is_feed() && !is_home() ) $content .= $button;
	elseif ( $options['fb_on_home'] ) $content .= $button;
	return $content;
}

function ngfb_twitter_button( $content ) {

	$options = get_option('ngfb_options');

	$twitter_count = $options['twitter_count'];
	if($twitter_count == '') { $twitter_count = 'horizontal'; }
	
	$twitter_size = $options['twitter_size'];
	if($twitter_size == '') { $twitter_size = 'medium'; }
	
	$twitter_dnt = $options['twitter_dnt'];
	if($twitter_dnt == '') { $twitter_dnt = 'true'; }
	
	$button = "\n<!-- Twitter Button Added by NextGEN Facebook Plugin -->\n";

	$button .= '<a href="https://twitter.com/share" class="twitter-share-button" 
		data-url="'.get_permalink($post->ID).'" 
		data-count="'.$twitter_count.'" 
		data-size="'.$twitter_size.'" 
		data-dnt="'.$twitter_dnt.'">Tweet</a>'."\n";

	$button .= '<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>'."\n";

	if ( !is_feed() && !is_home() ) $content .= $button;
	elseif ( $options['fb_on_home'] ) $content .= $button;

	return $content;
}

function ngfb_get_ngg_thumb_tags( $thumb_id ) {

    if (! method_exists( 'nggdb', 'find_image' ) ) return;

	if ( is_string($thumb_id) && substr($thumb_id, 0, 4) == 'ngg-') {

		$thumb_id = substr($thumb_id, 4);
		$img_tags = wp_get_object_terms($thumb_id, 'ngg_tag', 'fields=names');
	}
	return $img_tags;
}

// thumb_id must be 'ngg-#'
function ngfb_get_ngg_thumb_url( $thumb_id ) {

    if (! method_exists( 'nggdb', 'find_image' ) ) return;

	if ( is_string($thumb_id) && substr($thumb_id, 0, 4) == 'ngg-') {

		$thumb_id = substr($thumb_id, 4);
		$image = nggdb::find_image($thumb_id);	// returns an nggImage object

		if ($image != null) {

			$options = get_option('ngfb_options');
			$size = $options['og_img_size'];

			global $_wp_additional_image_sizes;
			$tmp = get_intermediate_image_sizes();
	
			if ( isset( $_wp_additional_image_sizes[$size]['width'] ) )
				$width = intval( $_wp_additional_image_sizes[$size]['width'] );
			else $width = get_option( "{$size}_size_w" );

			if ( isset( $_wp_additional_image_sizes[$size]['height'] ) )
				$height = intval( $_wp_additional_image_sizes[$size]['height'] );
			else $height = get_option( "{$size}_size_h" );

			if ( isset( $_wp_additional_image_sizes[$size]['crop'] ) )
				$crop = intval( $_wp_additional_image_sizes[$size]['crop'] );
			else $crop = get_option( "{$size}_crop" );

			$crop = ( $crop == 1 ? 'crop' : '' );

			// Check to see if the image already exists
			$image_url = $image->cached_singlepic_file( $width, $height, $crop );

			// If not, then use the dynamic image url
			if (empty($image_url)) 
				$image_url = trailingslashit(site_url()).'index.php?callback=image&amp;pid='.$thumb_id.'&amp;width='.$width.'&amp;height='.$height.'&amp;mode='.$crop;
		}
    }
    return $image_url;
}

function ngfb_str_decode( $str ) {
	$str = preg_replace('/&#8230;/', '...', $str );
	return preg_replace('/&#\d{2,5};/ue', "ctx_aj_utf8_entity_decode('$0')", $str );
}

function ngfb_utf8_entity_decode( $entity ) {
	$convmap = array( 0x0, 0x10000, 0, 0xfffff );
	return mb_decode_numericentity( $entity, $convmap, 'UTF-8' );
}

function ngfb_add_meta() {
	global $post;
	
	$options = get_option('ngfb_options');

	/* define the list of tags
	-------------------------------------------------------------- */

	if ( is_single() || is_page() ) {
			
			$tag_names = array();
			$page_tags = wp_get_post_tags( $post->ID );
			$tag_prefix = $options['og_wiki_tag'];

			foreach ( $page_tags as $tag ) {
				$tag_name = $tag->name;
				if ( $tag_prefix ) $tag_name = preg_replace( "/^$tag_prefix/", "", $tag_name );
				$tag_names[] = $tag_name;
			}
			unset ( $tag );
			
			if ( $options['og_ngg_tags'] ) {
				if ( function_exists('has_post_thumbnail') && 
					has_post_thumbnail( $post->ID ) ) {

					$thumb_id = get_post_thumbnail_id( $post->ID );

					if ( is_string( $thumb_id ) && substr( $thumb_id, 0, 4 ) == 'ngg-' ) {
						$image_tags = ngfb_get_ngg_thumb_tags( $thumb_id );
					}

				} elseif ( $options['og_def_img_id'] != '' && $options['og_def_img_id_pre'] == 'ngg') {

						$image_tags = ngfb_get_ngg_thumb_tags( $options['og_def_img_id_pre'].'-'.$options['og_def_img_id'] );
				}
				if ( is_array( $image_tags ) ) $tag_names = array_merge( $tag_names, $image_tags );
			}

	}

	/* define the image_url
	-------------------------------------------------------------- */

	if ( is_single() || is_page() || ! $options['og_def_on_home'] ) {

		if ( function_exists('has_post_thumbnail') && has_post_thumbnail( $post->ID ) ) {
	
			$thumb_id = get_post_thumbnail_id( $post->ID );
	
			// if the post thumbnail id has the form ngg- then it's a NextGEN image
			if ( is_string( $thumb_id ) && substr( $thumb_id, 0, 4 ) == 'ngg-' ) {
				$image_url = ngfb_get_ngg_thumb_url( $thumb_id );
			} else {
				$out = wp_get_attachment_image_src( $thumb_id, $options['og_img_size'] );
				$image_url = $out[0];
			}
		}
	
		// if there's no featured image, search post for images and display first one
		if( ! $image_url ) {

			if ( preg_match_all( '/\[singlepic[^\]]+id=([0-9]+)/i', $post->post_content, $match) > 0 ) {
				$thumb_id = $match[1][0];					
				$image_url = ngfb_get_ngg_thumb_url( 'ngg-'.$thumb_id );
			} elseif ( preg_match_all( '/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $post->post_content, $match) > 0 )
					$image_url = $match[1][0];
		}
	}

	if ( is_search() && ! $options['og_def_on_search'] ) {

	} else {

		if ( ! $image_url && $options['og_def_img_id'] != '' ) {
			if ($options['og_def_img_id_pre'] == 'ngg') {
				$image_url = ngfb_get_ngg_thumb_url( $options['og_def_img_id_pre'].'-'.$options['og_def_img_id'] );
			} else {
				$out = wp_get_attachment_image_src( $options['og_def_img_id'], $options['og_img_size'] );
				$image_url = $out[0];
			}
		}
	
		if ( ! $image_url ) $image_url = $options['og_def_img_url'];	// if still empty, use the default url.
	}

	/* define the site_title
	-------------------------------------------------------------- */

	$site_title = get_bloginfo( 'name', 'display' );

	/* define the page_title
	-------------------------------------------------------------- */

	global $page, $paged;
	$page_title = trim( wp_title( '|', false, 'right' ), ' |');

	if ( is_singular() ) {

		$parent_id  = $post->post_parent;
		if ($parent_id) $parent_title = get_the_title($parent_id);
		if ($parent_title) $page_title .= ' ('.$parent_title.')';

	} elseif ( is_category() ) { 

		// wordpress does not include parents - we want the parents too
		$page_title = ngfb_str_decode( single_cat_title( '', false ) );
		$page_title = trim( get_category_parents( get_cat_ID( $page_title ), false, ' | ', false ), ' |');
		$page_title = preg_replace('/\.\.\. \| /', '... ', $page_title);	// my own little quirk ;-)
	}

	if ( ! $page_title ) $page_title = $site_title;

	if ( $paged >= 2 || $page >= 2 ) 
		$page_title .= ' | ' . sprintf( 'Page %s', max( $paged, $page ) );	// add a page number if necessary

	/* define the page_desc
	-------------------------------------------------------------- */

	if ( is_search() && ! $options['og_def_on_search'] ) {

	} elseif ( is_singular() ) {
	
		if ( has_excerpt($post->ID) ) {

			$page_text = strip_tags( get_the_excerpt( $post->ID ) );

		// use WP-WikiBox for page content, if option is true
		} elseif ( is_page() && $options['og_desc_wiki'] && function_exists( 'wikibox_summary' ) ) {
			$tags = wp_get_post_tags( $post->ID );
			if ( $tags ) {
				$tag_prefix = $options['og_wiki_tag'];
				foreach ( $tags as $tag ) {
					$tag_name = $tag->name;
					if ( $tag_prefix )
						if ( preg_match( "/^$tag_prefix/", $tag_name ) > 0 )
							$tag_name = preg_replace( "/^$tag_prefix/", "", $tag_name );
						else continue;
					$page_text .= wikibox_summary( $tag_name, '', false ); 
				}
				unset ( $tag );
				unset ( $tag_name );
				unset ( $tag_prefix );
			} else {
				$page_text .= wikibox_summary( the_title( '', '', false ), '', false );
			}
		} 
	
		// fallback to regular content
		if ( ! $page_text ) {

			// remove shortcodes not to screw-up NGG's album tag parsing
			$page_text = apply_filters('the_content', strip_shortcodes( $post->post_content ) );
		
			// ignore everything until the first paragraph tag
			if (  $options['og_desc_strip'] )
				$page_text = preg_replace( '/^.*<p>/s', '', $page_text );

		}

		$page_text = preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( $page_text ) );
		$page_text = substr( $page_text, 0, $options['og_desc_len'] );
		$page_text = preg_replace( '/[^ ]*$/', '', $page_text );	// remove trailing bits of words
		$page_desc = esc_attr( trim( $page_text ) );

	} elseif ( is_author() ) { 

		the_post();
		$page_desc = sprintf( 'Authored by %s', get_the_author_meta( 'display_name' ) );
		$author_desc = get_the_author_meta( 'description' );
		if ($author_desc) $page_desc .= ': '.$author_desc;	// add the author's profile description, if there is one

	} elseif ( is_tag() ) {

		$page_desc = sprintf( 'Tagged with %s', single_tag_title('', false) );
		$tag_desc = esc_attr( trim( substr( preg_replace( '/[\r\n]/', ' ', 
			strip_tags( strip_shortcodes( tag_description() ) ) ), 0, 
			$options['og_desc_len'] - strlen($page_desc) ) ) );
		if ($tag_desc) $page_desc .= ': '.$tag_desc;	// add the tag description, if there is one

	} elseif ( is_category() ) { 

		$page_desc = sprintf( '%s Category', single_cat_title( '', false ) ); 
		$cat_desc = esc_attr( trim( substr( preg_replace( '/[\r\n]/', ' ', 
			strip_tags( strip_shortcodes( category_description() ) ) ), 0, 
			$options['og_desc_len'] - strlen($page_desc) ) ) );
		if ($cat_desc) $page_desc .= ': '.$cat_desc;	// add the category description, if there is one
	}
	elseif ( is_day() ) $page_desc = sprintf( 'Daily Archives for %s', get_the_date() );
	elseif ( is_month() ) $page_desc = sprintf( 'Monthly Archives for %s', get_the_date('F Y') );
	elseif ( is_year() ) $page_desc = sprintf( 'Yearly Archives for %s', get_the_date('Y') );
	else $page_desc = get_bloginfo( 'description', 'display' );

	/* define the page_type
	-------------------------------------------------------------- */

	if ( is_single() || is_page() ) $page_type = "article";
	else $page_type = "blog";	// 'website' could also be another choice

?>

<!-- NextGEN Facebook Meta Tags BEGIN -->
<?php
	if ( $options['og_admins'] )
		echo '<meta property="fb:admins" content="', $options['og_admins'], '" />', "\n";

	if ( $options['og_app_id'] )
		echo '<meta property="fb:app_id" content="', $options['og_app_id'], '" />', "\n";
?>
<meta property="og:site_name" content="<?php echo $site_title; ?>" />
<meta property="og:title" content="<?php echo $page_title; ?>" />
<meta property="og:type" content="<?php echo $page_type ?>" />
<meta property="og:url" content="http://<?php echo $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>" />
<?php
	if ( $page_desc )
		echo '<meta property="og:description" content="', $page_desc, '" />', "\n";

	if ( $image_url )
		echo '<meta property="og:image" content="', $image_url, '" />', "\n";

	if ($page_type == "article") {

			echo '<meta property="article:published_time" content="', 
				get_the_date('c'), '" />', "\n";

			echo '<meta property="article:modified_time" content="',
				get_the_modified_date('c'), '" />', "\n";

			if ($options['og_art_section'])
				echo '<meta property="article:section" content="', 
					$options['og_art_section'], '" />', "\n";

			echo '<meta property="article:author" content="',
				trailingslashit(site_url()), 'author/', 
				get_the_author_meta( 'user_login', 
				$post->post_author ), '/" />', "\n";

			foreach ( $tag_names as $tag )
				echo '<meta property="article:tag" content="', $tag, '" />', "\n";
			unset ( $tag );
	}
?>
<!-- NextGEN Facebook Meta Tags END -->

<?php
}
add_action('wp_head', 'ngfb_add_meta');

// It would be better to use '<head prefix="">' but WP doesn't offer hooks into <head>
function ngfb_add_og_doctype( $output ) {
	return $output . '
		xmlns:og="http://ogp.me/ns"
		xmlns:fb="http://ogp.me/ns/fb"';
}
add_filter('language_attributes', 'ngfb_add_og_doctype');

?>

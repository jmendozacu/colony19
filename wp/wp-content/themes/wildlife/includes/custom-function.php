<?php
	// Loading child theme textdomain
	load_child_theme_textdomain( CURRENT_THEME, CHILD_DIR . '/languages' );

	// WP Pointers
	add_action('admin_enqueue_scripts', 'myHelpPointers');

	function myHelpPointers() {

	//First we define our pointers 
	$pointers = array(
		array(
			'id'       => 'xyz1', // unique id for this pointer
			'screen'   => 'options-permalink', // this is the page hook we want our pointer to show on
			'target'   => '#submit', // the css selector for the pointer to be tied to, best to use ID's
			'title'    => theme_locals("submit_permalink"),
			'content'  => theme_locals("submit_permalink_desc"),
			'position' => array( 
								'edge'   => 'top', //top, bottom, left, right
								'align'  => 'left', //top, bottom, left, right, middle
								'offset' => '0 5'
								)
			),

		array(
			'id'       => 'xyz2', // unique id for this pointer
			'screen'   => 'themes', // this is the page hook we want our pointer to show on
			'target'   => '#toplevel_page_options-framework', // the css selector for the pointer to be tied to, best to use ID's
			'title'    => theme_locals("import_sample_data"),
			'content'  => theme_locals("import_sample_data_desc"),
			'position' => array( 
								'edge'   => 'bottom', //top, bottom, left, right
								'align'  => 'top', //top, bottom, left, right, middle
								'offset' => '0 -10'
								)
			),

		array(
			'id'       => 'xyz3', // unique id for this pointer
			'screen'   => 'toplevel_page_options-framework', // this is the page hook we want our pointer to show on
			'target'   => '#toplevel_page_options-framework', // the css selector for the pointer to be tied to, best to use ID's
			'title'    => theme_locals("import_sample_data"),
			'content'  => theme_locals("import_sample_data_desc_2"),
			'position' => array( 
								'edge'   => 'left', //top, bottom, left, right
								'align'  => 'top', //top, bottom, left, right, middle
								'offset' => '0 18'
								)
			)
		// more as needed
		);

		//Now we instantiate the class and pass our pointer array to the constructor 
		$myPointers = new WP_Help_Pointer($pointers);
	};

	add_filter( 'cherry_slider_params', 'child_slider_params' );
	function child_slider_params( $params ) {
	    $params['minHeight'] = '"110px"';
	    $params['height'] = '"49.915%"';
	return $params;
	}	

	/*-----------------------------------------------------------------------------------*/
	/*	Pagination
	/*-----------------------------------------------------------------------------------*/
	if ( !function_exists( 'pagination' ) ) {
		function pagination($pages = '', $range = 1) { 
			$showitems = ($range * 2)+1; 
			global $paged;
			
			if(empty($paged)) $paged = 1;

			if($pages == '') {
				global $wp_query;
				$pages = $wp_query->max_num_pages;
				if(!$pages) {
					$pages = 1;
				}
			}

			if(1 != $pages) {
				echo "<div class=\"pagination pagination__posts\">";

				if($paged > 2 && $paged > $range+1 && $showitems < $pages) echo "<div class='first'><a href='".get_pagenum_link(1)."'>".theme_locals("first")."</a></div>";
				if($paged > 1 && $showitems < $pages) echo "<div class='prev'><a href='".get_pagenum_link($paged - 1)."'>".__("prev.", CURRENT_THEME)."</a></div>";

				echo "<ul>";					

				for ($i=1; $i <= $pages; $i++) {
					if (1 != $pages &&( !($i >= $paged+$range+1 || $i <= $paged-$range-1) || $pages <= $showitems )) {
						echo ($paged == $i)? "<li class=\"active\"><a href=''>".$i."</a></li>":"<li><a href='".get_pagenum_link($i)."' class=\"inactive\">".$i."</a></li>";
					}
				}

				
				echo "</ul>";

				if ($paged < $pages-1 &&  $paged+$range-1 < $pages && $showitems < $pages) echo "<div class='last'><a href='".get_pagenum_link($pages)."'>".theme_locals("last")."</a></div>";	
				if ($paged < $pages && $showitems < $pages) echo "<div class='next'><a href=\"".get_pagenum_link($paged + 1)."\">".theme_locals("next")."</a></div>"; 					

				echo "</div>\n";
			}
		}
	}

	//------------------------------------------------------
	//  Related Posts
	//------------------------------------------------------
		if(!function_exists('cherry_related_posts')){
			function cherry_related_posts($args = array()){
				global $post;
				$default = array(
					'post_type' => get_post_type($post),
					'class' => 'related-posts',
					'class_list' => 'related-posts_list',
					'class_list_item' => 'related-posts_item',
					'display_title' => true,
					'display_link' => true,
					'display_thumbnail' => true,
					'width_thumbnail' => 170,
					'height_thumbnail' => 163,
					'before_title' => '<h3 class="related-posts_h">',
					'after_title' => '</h3>',
					'posts_count' => 4
				);
				extract(array_merge($default, $args));

				$post_tags = wp_get_post_terms($post->ID, $post_type.'_tag', array("fields" => "slugs"));
				$tags_type = $post_type=='post' ? 'tag' : $post_type.'_tag' ;
				$suppress_filters = get_option('suppress_filters');// WPML filter
				if ($post_tags && !is_wp_error($post_tags)) {
					$args = array(
						"$tags_type" => implode(',', $post_tags),
						'post_status' => 'publish',
						'posts_per_page' => $posts_count,
						'ignore_sticky_posts' => 1,
						'post__not_in' => array($post->ID),
						'post_type' => $post_type,
						'suppress_filters' => $suppress_filters
						);
					query_posts($args);
					if ( have_posts() ) {
						$output = '<div class="'.$class.'">';
						$output .= $display_title ? $before_title.of_get_option('blog_related', theme_locals("posts_std")).$after_title : '' ;
						$output .= '<ul class="'.$class_list.' clearfix">';
						while( have_posts() ) {
							the_post();
							$thumb   = has_post_thumbnail() ? get_post_thumbnail_id() : PARENT_URL.'/images/empty_thumb.gif';
							$blank_img = stripos($thumb, 'empty_thumb.gif');
							$img_url = $blank_img ? $thumb : wp_get_attachment_url( $thumb,'full');
							$image   = $blank_img ? $thumb : aq_resize($img_url, $width_thumbnail, $height_thumbnail, true) or $img_url;

							$output .= '<li class="'.$class_list_item.'">';
							$output .= $display_thumbnail ? '<figure class="thumbnail featured-thumbnail"><a href="'.get_permalink().'" title="'.get_the_title().'"><img data-src="'.$image.'" alt="'.get_the_title().'" /></a></figure>': '' ;
							$output .= $display_link ? '<a href="'.get_permalink().'" >'.get_the_title().'</a>': '' ;
							$output .= '</li>';
						}
						$output .= '</ul></div>';
						echo $output;
					}
					wp_reset_query();
				}
			}
		}

		/**
		 * Post Grid
		 *
		 */
		if (!function_exists('posts_grid_shortcode')) {

			function posts_grid_shortcode($atts, $content = null) {
				extract(shortcode_atts(array(
					'type'            => 'post',
					'category'        => '',
					'custom_category' => '',
					'columns'         => '3',
					'rows'            => '3',
					'order_by'        => 'date',
					'order'           => 'DESC',
					'thumb_width'     => '370',
					'thumb_height'    => '250',
					'meta'            => '',
					'excerpt_count'   => '15',
					'link'            => 'yes',
					'link_text'       => __('Read more', CHERRY_PLUGIN_DOMAIN),
					'custom_class'    => ''
				), $atts));

				$spans = $columns;
				$rand  = rand();

				// columns
				switch ($spans) {
					case '1':
						$spans = 'span12';
						break;
					case '2':
						$spans = 'span6';
						break;
					case '3':
						$spans = 'span4';
						break;
					case '4':
						$spans = 'span3';
						break;
					case '6':
						$spans = 'span2';
						break;
				}

				// check what order by method user selected
				switch ($order_by) {
					case 'date':
						$order_by = 'post_date';
						break;
					case 'title':
						$order_by = 'title';
						break;
					case 'popular':
						$order_by = 'comment_count';
						break;
					case 'random':
						$order_by = 'rand';
						break;
				}

				// check what order method user selected (DESC or ASC)
				switch ($order) {
					case 'DESC':
						$order = 'DESC';
						break;
					case 'ASC':
						$order = 'ASC';
						break;
				}

				// show link after posts?
				switch ($link) {
					case 'yes':
						$link = true;
						break;
					case 'no':
						$link = false;
						break;
				}

					global $post;
					global $my_string_limit_words;

					$numb = $columns * $rows;

					// WPML filter
					$suppress_filters = get_option('suppress_filters');

					$args = array(
						'post_type'         => $type,
						'category_name'     => $category,
						$type . '_category' => $custom_category,
						'numberposts'       => $numb,
						'orderby'           => $order_by,
						'order'             => $order,
						'suppress_filters'  => $suppress_filters
					);

					$posts      = get_posts($args);
					$i          = 0;
					$count      = 1;
					$output_end = '';
					if ($numb > count($posts)) {
						$output_end = '</ul>';
					}

					$output = '<ul class="posts-grid row-fluid unstyled '. $custom_class .'">';

					for ( $j=0; $j < count($posts); $j++ ) {
						// Unset not translated posts
						if ( function_exists( 'wpml_get_language_information' ) ) {
							global $sitepress;

							$check              = wpml_get_language_information( $posts[$j]->ID );
							$language_code      = substr( $check['locale'], 0, 2 );
							if ( $language_code != $sitepress->get_current_language() ) unset( $posts[$j] );

							// Post ID is different in a second language Solution
							if ( function_exists( 'icl_object_id' ) ) $posts[$j] = get_post( icl_object_id( $posts[$j]->ID, $type, true ) );
						}
						$post_id        = $posts[$j]->ID;
						setup_postdata($posts[$j]);
						$excerpt        = get_the_excerpt();
						$attachment_url = wp_get_attachment_image_src( get_post_thumbnail_id($post_id), 'full' );
						$url            = $attachment_url['0'];
						$image          = aq_resize($url, $thumb_width, $thumb_height, true);
						$mediaType      = get_post_meta($post_id, 'tz_portfolio_type', true);
						$prettyType     = 0;

						if ($count > $columns) {
							$count = 1;
							$output .= '<ul class="posts-grid row-fluid unstyled '. $custom_class .'">';
						}

						$output .= '<li class="'. $spans .'">';
							if(has_post_thumbnail($post_id) && $mediaType == 'Image') {

								$prettyType = 'prettyPhoto-'.$rand;

								$output .= '<figure class="featured-thumbnail thumbnail">';
								$output .= '<a href="'.$url.'" title="'.get_the_title($post_id).'" rel="' .$prettyType.'">';
								$output .= '<img  src="'.$image.'" alt="'.get_the_title($post_id).'" />';
								$output .= '<span class="zoom-icon"></span></a></figure>';
							} elseif ($mediaType != 'Video' && $mediaType != 'Audio') {

								$thumbid = 0;
								$thumbid = get_post_thumbnail_id($post_id);

								$images = get_children( array(
									'orderby'        => 'menu_order',
									'order'          => 'ASC',
									'post_type'      => 'attachment',
									'post_parent'    => $post_id,
									'post_mime_type' => 'image',
									'post_status'    => null,
									'numberposts'    => -1
								) ); 

								if ( $images ) {

									$k = 0;
									//looping through the images
									foreach ( $images as $attachment_id => $attachment ) {
										$prettyType = "prettyPhoto-".$rand ."[gallery".$i."]";
										//if( $attachment->ID == $thumbid ) continue;

										$image_attributes = wp_get_attachment_image_src( $attachment_id, 'full' ); // returns an array
										$img = aq_resize( $image_attributes[0], $thumb_width, $thumb_height, true ); //resize & crop img
										$alt = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
										$image_title = $attachment->post_title;

										if ( $k == 0 ) {
											if (has_post_thumbnail($post_id)) {
												$output .= '<figure class="featured-thumbnail thumbnail">';
												$output .= '<a href="'.$image_attributes[0].'" title="'.get_the_title($post_id).'" rel="' .$prettyType.'">';
												$output .= '<img src="'.$image.'" alt="'.get_the_title($post_id).'" />';
											} else {
												$output .= '<figure class="featured-thumbnail thumbnail">';
												$output .= '<a href="'.$image_attributes[0].'" title="'.get_the_title($post_id).'" rel="' .$prettyType.'">';
												$output .= '<img  src="'.$img.'" alt="'.get_the_title($post_id).'" />';
											}
										} else {
											$output .= '<figure class="featured-thumbnail thumbnail" style="display:none;">';
											$output .= '<a href="'.$image_attributes[0].'" title="'.get_the_title($post_id).'" rel="' .$prettyType.'">';
										}
										$output .= '<span class="zoom-icon"></span></a></figure>';
										$k++;
									}
								} elseif (has_post_thumbnail($post_id)) {
									$prettyType = 'prettyPhoto-'.$rand;
									$output .= '<figure class="featured-thumbnail thumbnail">';
									$output .= '<a href="'.$url.'" title="'.get_the_title($post_id).'" rel="' .$prettyType.'">';
									$output .= '<img  src="'.$image.'" alt="'.get_the_title($post_id).'" />';
									$output .= '<span class="zoom-icon"></span></a></figure>';
								}
							} else {

								// for Video and Audio post format - no lightbox
								$output .= '<figure class="featured-thumbnail thumbnail"><a href="'.get_permalink($post_id).'" title="'.get_the_title($post_id).'">';
								$output .= '<img  src="'.$image.'" alt="'.get_the_title($post_id).'" />';
								$output .= '</a></figure>';
							}

							$output .= '<div class="clear"></div>';

							$output .= '<h5><a href="'.get_permalink($post_id).'" title="'.get_the_title($post_id).'">';
								$output .= get_the_title($post_id);
							$output .= '</a></h5>';

							if ($meta == 'yes') {
								// begin post meta
								$output .= '<div class="post_meta">';

									// post date
									$output .= '<span class="post_date">';
									$output .= '<time datetime="'.get_the_time('Y-m-d\TH:i:s', $post_id).'">' .get_the_date(). '</time>';
									$output .= '</span>';

								$output .= '</div>';
								// end post meta
							}
							$output .= cherry_get_post_networks(array('post_id' => $post_id, 'display_title' => false, 'output_type' => 'return'));
							if($excerpt_count >= 1){
								$output .= '<p class="excerpt">';
									$output .= my_string_limit_words($excerpt,$excerpt_count);
								$output .= '</p>';
							}
							if($link){
								$output .= '<a href="'.get_permalink($post_id).'" class="btn btn-primary btn-large" title="'.get_the_title($post_id).'">';
								$output .= $link_text;
								$output .= '</a>';
							}
							$output .= '</li>';
							if ($j == count($posts)-1) {
								$output .= $output_end;
							}
						if ($count % $columns == 0) {
							$output .= '</ul><!-- .posts-grid (end) -->';
						}
					$count++;
					$i++;

				} // end for

				return $output;
			}
			add_shortcode('posts_grid', 'posts_grid_shortcode');
		}

		/*-----------------------------------------------------------------------------------*/
		/* Custom Comments Structure
		/*-----------------------------------------------------------------------------------*/
		if ( !function_exists( 'mytheme_comment' ) ) {
			function mytheme_comment($comment, $args, $depth) {
				$GLOBALS['comment'] = $comment;
			?>
			<li <?php comment_class('clearfix'); ?> id="li-comment-<?php comment_ID() ?>">
				<div id="comment-<?php comment_ID(); ?>" class="comment-body clearfix">
					<div class="wrapper">
						<div class="comment-author vcard">
							<?php echo get_avatar( $comment->comment_author_email, 59 ); ?>
							<?php printf('<span class="author">%1$s</span>', get_comment_author_link()) ?>
						</div>
						<?php if ($comment->comment_approved == '0') : ?>
							<em><?php echo theme_locals("your_comment") ?></em>
						<?php endif; ?>
						<div class="extra-wrap">
							<?php comment_text() ?>
						</div>
					</div>
					<div class="wrapper">
						<div class="reply">
							<?php comment_reply_link(array_merge( $args, array('depth' => $depth, 'max_depth' => $args['max_depth']))) ?>
						</div>
						<div class="comment-meta commentmetadata"><?php printf('%1$s', get_comment_date()) ?></div>
					</div>
				</div>
		<?php }
		}

?>
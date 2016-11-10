<?php
/**
* Template Name: Home Page (Blog)
*/

get_header(); 
?>

<div class="motopress-wrapper content-holder clearfix">
	<div class="container">
		<div class="row">
			<div class="span12" data-motopress-wrapper-file="page-home.php" data-motopress-wrapper-type="content">
				<div class="row">
					<div class="<?php //echo cherry_get_layout_class( 'full_width_content' ); ?>" data-motopress-type="static" data-motopress-static-file="static/static-slider.php">
						<?php //get_template_part("static/static-slider"); ?>
					</div>
				</div>



				<div class="row">
					<div class="span9" id="content" data-motopress-type="loop" data-motopress-loop-file="loop/loop-blog.php">

						<?php  
						
						if ( get_query_var('paged') ) {
						              $paged = get_query_var('paged');
						      } elseif ( get_query_var('page') ) {
						              $paged = get_query_var('page');
						      } else {
						              $paged = 1;
						      }
						      query_posts( array(
									 'post_type' => 'post',
									 'posts_per_page' => 3,
									 'paged' => $paged
									 )
						      );
						
						
							get_template_part("loop/loop-blog");
							get_template_part('includes/post-formats/post-nav'); wp_reset_query(); ?>
						
					</div>
					<div class="sidebar span3" id="sidebar" data-motopress-type="static-sidebar"  data-motopress-sidebar-file="sidebar.php">
					
						<?php get_sidebar(); ?>

					</div>
				</div>

			</div>
		</div>
	</div>
</div>





<?php get_footer(); ?>
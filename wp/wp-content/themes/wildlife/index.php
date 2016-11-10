<?php 
	get_header();

	$blog_sidebar_pos = of_get_option('blog_sidebar_pos');
	$blog_class = cherry_get_layout_class( 'content' );
	$display_sidebar = true;
	$blog_before = $blog_after = '';

	switch ($blog_sidebar_pos) {
		case 'masonry':
			$blog_class = cherry_get_layout_class( 'full_width_content' );
			$blog_before = '<div class="isotope">';
			$blog_after = '</div>';
			$display_sidebar = false;
		break;
		case 'none':
			$blog_class = cherry_get_layout_class( 'full_width_content' );
			$display_sidebar = false;
		break;
	}
?>


<div id="motopress-main" class="main-holder">
	<div class="motopress-wrapper content-holder clearfix">
		<div class="container">
			
			
			<?php get_template_part('front-header'); ?>



			<div class="row">
				<div class="span9" id="Slider">
					<?php get_slider_template_part(); ?>
				</div>

				<div class="span3" id="Contact">
					<div class="zone-title">Contact</div>
					<?php echo do_shortcode('[contact-form-7 id="4" title="Formulaire de contact 1"]'); ?>
				</div>

			</div>

			<div class="row">
				<div class="span9" id="selectedProducts">
					<?php 
					get_template_part('products'); 
					?>
				</div>
				<div class="span3" id="galImages">
					<?php get_template_part('slideshow'); ?>
				</div>
			</div>





			<div class="row">

				<div class="span9" id="lastArticles">
					
					
					<?php
					get_template_part('last-posts');
					?>

					<?php //get_template_part('includes/post-formats/post-nav'); ?>
					
				</div>
				<div class="span3" id="Utilities">
					<?php get_template_part('sidebar'); ?>
				</div>

			</div>

			<div class="row">
				<div class="span12" id="lastVideos">
					<?php get_template_part('last-videos'); ?>
				</div>
			</div>

			<div class="row">
				<div class="span12">

					<?php 
					$footer_colony = wpws_get_content("http://www.colony-perroquet.fr/", ".footer-container", array('output' => 'html')); 
					echo utf8_decode($footer_colony);

					?>
				</div>
			</div>






		</div>
	</div>
</div>





<?php get_template_part('end'); ?>
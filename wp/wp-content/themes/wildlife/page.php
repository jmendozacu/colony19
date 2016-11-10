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
			
			
			<div class="row">
				<div class="span12">
					<?php 
					$header_colony = wpws_get_content("http://www.colony-perroquet.fr/", ".header", array('output' => 'html')); 
					echo utf8_decode($header_colony);
					?>
				</div>
			</div>

			<div class="row">
				<div class="span12">
					<?php
					$nav_colony = wpws_get_content("http://www.colony-perroquet.fr/", "#nav", array('output' => 'html'));
					echo utf8_decode($nav_colony);
					?>
				</div>
			</div>

			<div class="row">
				<div class="span12">
					<?php 
					$pubs_colony = wpws_get_content("http://www.colony-perroquet.fr/", ".zblock-menu-bottom", array('output' => 'html')); 
					echo utf8_decode($pubs_colony);
					?>
				</div>
			</div>

			<div class="row">
				<div class="span12">
					<?php 
					get_template_part("static/static-nav");
					?>
				</div>
			</div>

			<div class="row">
				<div class="span12" id="Page">
					<?php get_template_part("loop/loop-page"); ?>
				</div>
			</div>


			<div class="row">
				<div class="span12" id="Footer">
					<?php get_footer(); ?>
				</div>
			</div>


		</div>
	</div>





</div>

<?php echo get_template_part('end'); ?>
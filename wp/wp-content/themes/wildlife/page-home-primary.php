<?php
/**
* Template Name: Home Page (Primary)
*/

//get_header(); ?>

<div class="motopress-wrapper content-holder clearfix">
	<div class="container">
		<div class="row">
			<?php do_action( 'cherry_before_home_page_content' ); ?>
			<div class="<?php echo apply_filters( 'cherry_home_layout', 'span12' ); ?>" data-motopress-wrapper-file="page-home.php" data-motopress-wrapper-type="content">
				<div class="row">
					<div class="<?php echo cherry_get_layout_class( 'full_width_content' ); ?>" data-motopress-type="static" data-motopress-static-file="static/static-slider.php">
						<?php get_template_part("static/static-slider"); ?>
					</div>
				</div>
				<div class="row">
					<div class="<?php echo cherry_get_layout_class( 'full_width_content' ); ?>" data-motopress-type="loop" data-motopress-loop-file="loop/loop-page.php">
						<?php get_template_part("loop/loop-page"); ?>
					</div>
				</div>
			</div>
			<?php do_action( 'cherry_after_home_page_content' ); ?>
		</div>
	</div>
</div>








<!--Ajout Chris-->
<div class="motopress-wrapper content-holder clearfix">
	<div class="container">
		<div class="row">
			<div class="span12">Header</div>
		</div>
		<div class="row">
			<div class="span12">Menu boutique</div>
		</div>

	</div>


</div>


<?php //get_footer(); ?>
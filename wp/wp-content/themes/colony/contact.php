<?php
/*
Template Name: Contact
*/


get_header(); 


?>

	<?php 
	//provoque une boucle infinie au chargement
	get_template_part('parts/front-header'); 
	?>

	<div class="row">
		<div class="col-md-12">
			<?php get_template_part('parts/navigation'); ?>
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
			<?php
			// Start the loop.
			while ( have_posts() ) : the_post();

				// Include the page content template.
				//get_template_part( 'template-parts/content', 'page' );
			?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<header class="entry-header">
						
					</header><!-- .entry-header -->

					<?php //twentysixteen_post_thumbnail(); ?>

					<div class="entry-content">
						<?php
						the_content();

						wp_link_pages( array(
							'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'twentysixteen' ) . '</span>',
							'after'       => '</div>',
							'link_before' => '<span>',
							'link_after'  => '</span>',
							'pagelink'    => '<span class="screen-reader-text">' . __( 'Page', 'twentysixteen' ) . ' </span>%',
							'separator'   => '<span class="screen-reader-text">, </span>',
						) );
						?>
					</div><!-- .entry-content -->

					<?php

						edit_post_link( __( 'Editer' ), '<div class="btn btn-success"><span class="glyphicon glyphicon-pencil"></span> ', '</div>' ); 
					?>

				</article><!-- #post-## -->
			<?php
				// If comments are open or we have at least one comment, load up the comment template.
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}

				// End of the loop.
			endwhile;
			?>
		</div>
	</div>

	<div class="row mg-v-20">
		<div class="col-md-12 pd-0">
			<?php 
			$footer_colony = wpws_get_content("http://www.colony-perroquet.fr/", ".footer-container", array('output' => 'html')); 
			echo utf8_decode($footer_colony);

			?>
		</div>
	</div>


<?php get_footer();?>
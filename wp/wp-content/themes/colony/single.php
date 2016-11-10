<?php 
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
		<div class="col-md-8">
		

		<?php 
		while ( have_posts() ) : the_post();

			// Include the single post content template.
			//get_template_part( 'template-parts/content', 'single' );
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<header class="entry-header">
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</header><!-- .entry-header -->

				<?php //twentysixteen_excerpt(); ?>

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

						if ( '' !== get_the_author_meta( 'description' ) ) {
							get_template_part( 'template-parts/biography' );
						}
					?>
				</div><!-- .entry-content -->

				<footer class="entry-footer">
					<?php //twentysixteen_entry_meta(); ?>
					<?php
						edit_post_link(
							sprintf(
								/* translators: %s: Name of current post */
								__( 'Edit<span class="screen-reader-text"> "%s"</span>', 'twentysixteen' ),
								get_the_title()
							),
							'<span class="edit-link">',
							'</span>'
						);
					?>
				</footer><!-- .entry-footer -->
			</article><!-- #post-## -->
			<?php
			// If comments are open or we have at least one comment, load up the comment template.
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}

			if ( is_singular( 'attachment' ) ) {
				// Parent post navigation.
				the_post_navigation( array(
					'prev_text' => _x( '<span class="meta-nav">Published in</span><span class="post-title">%title</span>', 'Parent post link', 'twentysixteen' ),
				) );
			} elseif ( is_singular( 'post' ) ) {
				// Previous/next post navigation.
				the_post_navigation( array(
					'next_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Next', 'twentysixteen' ) . '</span> ' .
						'<span class="screen-reader-text">' . __( 'Next post:', 'twentysixteen' ) . '</span> ' .
						'<span class="post-title">%title</span>',
					'prev_text' => '<span class="meta-nav" aria-hidden="true">' . __( 'Previous', 'twentysixteen' ) . '</span> ' .
						'<span class="screen-reader-text">' . __( 'Previous post:', 'twentysixteen' ) . '</span> ' .
						'<span class="post-title">%title</span>',
				) );
			}

			// End of the loop.
		endwhile;
		?>



		</div>

<div class="col-md-4"> <?php
				//Permet d'avoir accès aux variables 
				include(locate_template('parts/products.php'));
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
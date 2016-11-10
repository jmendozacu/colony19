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
		
		<p class="lead title bold">
			Catégorie : <?php single_cat_title(); ?>
		</p>












		<?php
		if ( have_posts() ) : 			
		// Display optional category description
		if ( category_description() ) : ?>
			<div class="archive-meta"><?php echo category_description(); ?></div>
			
			<?php endif; ?>

			<?php

			// The Loop
			while ( have_posts() ) : the_post(); 
			?>
				<h2>
					<a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>">
					<?php the_title(); ?>
					</a>
				</h2>
				<small>
					<?php the_time('F jS, Y') ?> by <?php the_author_posts_link() ?>
				</small>

				<div class="entry">
					<?php //the_content(); ?>
					<?php the_excerpt(); ?>

					<div class="text-right mg-v-10">
						<a class="colony-button" href="<?php the_permalink(); ?>">Lire la suite</a>
					</div>

					<p class="postmetadata">
						<?php
						  comments_popup_link( 'Pas encore de commentaire', '1 commentaire', '% commentaires', 'comments-link', 'Comments closed');
						?>
					</p>
				</div>

			<?php endwhile; 

		else: 
		?>
			<p>Désolé, pas d'article dans cette catégorie.</p>
		<?php endif; ?>
































	</div>
	<div class="col-md-4 ">
		

		<?php 
		//Utilisation du plugin cproduits
		$products = $cproduits->colony_get_products_by_category_id(); 
		if (!empty($products)) {
			foreach($products as $product):
				echo $product;
			endforeach;
		}
		

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
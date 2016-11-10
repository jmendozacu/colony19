<?php
/*
Template Name: Archives
*/
get_header(); ?>


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
	<div class="col-md-12 text-center">
		
		<?php the_post(); ?>
		
		<hr>
		
		<h2>Archives par mois:</h2>
		<ul>
			<?php wp_get_archives('type=monthly'); ?>
		</ul>
		
		<h2>Archives par catégorie:</h2>
		<ul>
			 <?php wp_list_categories(); ?>
		</ul>
				
		<hr>
		<div class="row">
			<div class="col-md-offset-4 col-md-4 col-sm-12 text-center">
				<div class="zone-title  text-center">Rechercher</div>		
				<?php get_search_form(); ?>
			</div>
		</div>
		

	</div>
</div>


<?php get_footer(); ?>
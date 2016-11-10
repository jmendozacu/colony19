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
		<div class="col-md-12">
			<h2><?php echo "C'est embarrassant"; ?></h2>
			<p><?php echo "Il semble bien que vous ne puissiez pas trouver ce que vous recherchez ici..."; ?></p>

			<?php get_search_form(); ?>
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
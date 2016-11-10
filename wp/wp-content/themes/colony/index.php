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
    <div class="col-md-9">
            <div class="module" id="Slider">

                <?php 
				//Slider
				echo do_shortcode("[URIS id=37]");
		
				?>
            </div>
       

       <div class="module" id="lastArticles">

            <div class="pd-10">

                <?php
            get_template_part('parts/last-posts');
            ?>
            </div>


        </div>


        <div class="module text-center" id="selectedProducts">
            <?php
				//Permet d'avoir accès aux variables 
				include(locate_template('parts/products.php'));
				?>
        </div>


        <div class="module" id="lastVideos">
            <?php get_template_part('parts/last-videos'); ?>
        </div>
    </div>



<div class="col-md-3">

    <div class="module" id="Contact">
        <div>

            <?php the_widget( 'WP_Widget_Categories' ); ?>
         </div>
     <div class="mg-tp-30" id="Sidebar">
                        <?php dynamic_sidebar('Sidebar'); ?>
                    </div>

    </div>


            <div class="module" id="galImages">
                <?php get_template_part('parts/slideshow'); ?>

            </div>


            
                <div class="module" id="Utilities">


                    <div class="text-center mg-tp-30">
                        <a href="http://www.colony-perroquet.fr" /> </a>
                        <img src="<?php bloginfo('stylesheet_directory'); ?>/img/logo.jpg" class="img-responsive center-block" />
                       
                        <p class="mg-tp-30">
                            <a href="http://www.colony-perroquet.fr">Visitez notre oisellerie en ligne</a>
                        </p>

                        <?php 
		//Utilisation du plugin cproduits
		$products = $cproduits->colony_get_products(); 
		if (!empty($products)) {
			foreach($products as $product):
				echo $product;
			endforeach;
		}
		

		?>
                    </div>

                </div>
            </div>
</div>


<?php get_footer();?>
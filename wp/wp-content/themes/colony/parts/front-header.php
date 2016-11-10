<div class="row" id="xsHeader">
	<div class="col-md-12">
		<a href="<?php echo home_url(); ?>">
			<img src="<?php bloginfo('stylesheet_directory'); ?>/img/logo.jpg" class="img-responsive center-block" />
		</a>
	</div>
</div>

<div class="row">
	<div class="col-md-12">
		<?php 
		//C'est celui-ci qui pose problème (provoque un chargement infini)
		$header_colony = wpws_get_content("http://www.colony-perroquet.fr/", ".header", array('output' => 'html')); 
		echo $header_colony;
		/*
		<div class="pd-10">
			<img src="<?php bloginfo('stylesheet_directory'); ?>/img/logo.jpg" class="img-responsive" />
		</div>
		*/
		?>
	</div>
</div>

<div class="row">
	<div class="col-md-12">
		<?php
		$nav_colony = wpws_get_content("http://www.colony-perroquet.fr/", "#nav", array('output' => 'html'));
		echo utf8_decode($nav_colony);
		?>
	</div>
</div>

<div class="row mg-tp-10">
	<div class="col-md-12">
		<?php 
		$pubs_colony = wpws_get_content("http://www.colony-perroquet.fr/", ".zblock-menu-bottom", array('output' => 'html')); 
		echo utf8_decode($pubs_colony);
		?>
	</div>
</div>
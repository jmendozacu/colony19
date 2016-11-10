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
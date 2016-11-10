<div class="zone-title">Nos produits</div>
<div class="row text-center" id="productsList">
	<?php 
	//Utilisation du plugin cproduits				
	$products = $cproduits->colony_get_products(); 

	foreach($products as $product):
		echo $product;
	endforeach;

	?>
</div>
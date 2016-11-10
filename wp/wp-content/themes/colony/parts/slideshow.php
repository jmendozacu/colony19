<div class="zone-title">Galerie</div>
<?php 
$slideshows = getAllCustomPostType('slideshow');
?>
<div class="fadein text-center">
	<?php
	if(!empty($slideshows)):
		foreach($slideshows as $slideshow):
		?>
			<img src="<?php echo $slideshow['image_url'];?>" class="img-responsive center-block w-100" />
		<?php
		endforeach;
	endif;
	?>
</div>
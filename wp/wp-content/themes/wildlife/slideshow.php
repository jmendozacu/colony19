<div class="zone-title">Gallerie</div>
<?php 
$slideshows = getAllCustomPostType('slideshow');
?>
<div class="fadein">
	<?php
	if(!empty($slideshows)):
		foreach($slideshows as $slideshow):
		?>
			<img src="<?php echo $slideshow['image_url'];?>" class="img-responsive center-block" />
		<?php
		endforeach;
	endif;
	?>
</div>
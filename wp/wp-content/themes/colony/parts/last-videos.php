<?php
$r = new WP_Query( array(
   'posts_per_page' => 4,
   'no_found_rows' => true, /*suppress found row count*/
   'post_status' => 'publish',
   'post_type' => 'video',
   'ignore_sticky_posts' => true,
));
?>
<div class="zone-title">Dernières vidéos</div>
<div class="row mg-v-20 text-center blocvideo">
<?php
if ($r->have_posts()) :
while ( $r->have_posts() ) : $r->the_post(); ?>

	<div class="col-md-3">

		<div class="embed-responsive embed-responsive-4by3">
			<?php var_dump(the_content()); ?>
		  <!-- <iframe class="embed-responsive-item" src=''></iframe> -->
		</div>

	   <a href="<?php the_permalink(); ?>"><?php get_the_title() ? the_title() : the_ID(); ?></a>
	   <br>
	   <?php echo get_the_date(); ?>

	</div>

<?php endwhile; ?>
<?php
// Reset the global $the_post as this query will have stomped on it
wp_reset_postdata();
endif; ?>


</div>



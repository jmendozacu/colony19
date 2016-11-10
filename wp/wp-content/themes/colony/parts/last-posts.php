<?php 
$args = array( 'numberposts' => '3' );
$recent_posts = wp_get_recent_posts( $args );
foreach($recent_posts as $recent){
		
	echo '<a class="Post-Title" href="' . get_permalink($recent["ID"]) . '">' .   $recent["post_title"].'</a>';
	
	echo '<div class="Post-Infos">Par '.$recent['post_author'].' | '.$recent['post_date'].'</div>';

	

	
	    $exploded = explode("<!--more-->",$recent["post_content"]);

     	echo '<div class=" Post-Content">';

 		if(count($exploded) >= 1) {
			echo do_shortcode ($exploded[0]);

?>
<div class="text-right">
		<a href="<?php echo get_permalink($recent["ID"]); ?>">Lire la suite</a>
	</div>
<?php
		} else {
			echo do_shortcode ($recent["post_content"]);
		}
		echo '</div>';
	

	
	echo '<hr>';
	}

?>


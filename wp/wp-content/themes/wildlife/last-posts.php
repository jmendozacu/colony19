<?php 
$args = array( 'numberposts' => '3' );
$recent_posts = wp_get_recent_posts( $args );
foreach($recent_posts as $recent){
		
	echo '<a class="Post-Title" href="' . get_permalink($recent["ID"]) . '">' .   $recent["post_title"].'</a>';
	
	echo '<div class="Post-Infos">Par '.$recent['post_author'].' | '.$recent['post_date'].'</div>';

	echo '<div class=" Post-Content">';
		echo $recent["post_content"];
	echo '</div>';
}
?>

<h1>héhohého</h1>
<?php
//Ce fichier est appelé par le header.php pour déterminer le titre de la page
if ( is_category() ) {
	echo theme_locals("category_for")." &quot;"; single_cat_title(); echo '&quot; | '; bloginfo( 'name' );
} elseif ( is_tag() ) {
	echo theme_locals("tag_for")." &quot;"; single_tag_title(); echo '&quot; | '; bloginfo( 'name' );
} elseif ( is_archive() ) {
	wp_title(''); echo " ".theme_locals("archive")." | "; bloginfo( 'name' );
} elseif ( is_search() ) {
	echo theme_locals("fearch_for")." &quot;".esc_html($s).'&quot; | '; bloginfo( 'name' );
} elseif ( is_home() || is_front_page()) {
	bloginfo( 'name' ); echo ' | '; bloginfo( 'description' );
}  elseif ( is_404() ) {
	echo theme_locals("error_404")." | "; bloginfo( 'name' );
} elseif ( is_single() ) {
	wp_title('');
} else {
	wp_title( ' | ', true, 'right' ); bloginfo( 'name' );
} ?>
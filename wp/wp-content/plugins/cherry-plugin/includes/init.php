<?php 
	include_once (CHERRY_PLUGIN_DIR . 'includes/plugin_functions.php');

//includ js and css files
	include_once (CHERRY_PLUGIN_DIR . 'includes/plugin_script.php');

//includ Aqua Resizer
	if(!function_exists('aq_resize')){
		include_once (CHERRY_PLUGIN_DIR . 'includes/aq_resizer.php');
	}

//Shortcodes
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/columns.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/shortcodes.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/posts_grid.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/posts_list.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/mini_posts_list.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/mini_posts_grid.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/alert.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/tabs.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/toggle.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/html.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/misc.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/service_box.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/post_cycle.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/carousel.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/carousel_owl.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/progressbar.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/banner.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/table.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/hero_unit.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/roundabout.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/categories.php');
	include_once (CHERRY_PLUGIN_DIR . 'includes/shortcodes/media.php');
	

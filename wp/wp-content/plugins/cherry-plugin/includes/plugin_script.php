<?php
//includ js files
	if(!function_exists('cherry_include_stylesheet')){
		function cherry_include_stylesheet(){
			//wp_enqueue_style('font-awesome', 'http://netdna.bootstrapcdn.com/font-awesome/3.2.1/css/font-awesome.css', false, '3.2.1', 'all');
			//wp_enqueue_style('stylesheet', CHERRY_PLUGIN_URL.'includes/assets/css/cherry_plugin.css', false, '0.1', 'all');
		}
		add_action( 'wp_enqueue_scripts', 'cherry_include_stylesheet', 20);
	}
//includ css files
	if(!function_exists('cherry_include_script')){
		function cherry_include_script(){
			wp_enqueue_script('script', CHERRY_PLUGIN_URL.'includes/assets/js/cherry_plugin.js', array('jquery'), '0.1', true);
			//wp_enqueue_script('bootstrap', CHERRY_PLUGIN_URL.'includes/bootstrap/js/bootstrap.min.js', array('jquery'), '0.1', true);
		}
		add_action( 'wp_enqueue_scripts', 'cherry_include_script' );
	}
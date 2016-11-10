<!DOCTYPE html>
<html>
  <head <?php language_attributes(); ?>>
    <meta charset="<?php bloginfo('charset'); ?>">
    <title><?php the_title(); ?></title>
   

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php 
    $site_url = site_url();
    ?>


    <link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/bootstrap/css/bootstrap.css" type="text/css" media="screen" />

    <link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/css/colony.css" type="text/css" media="screen" />
    <link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/style.css" type="text/css" media="screen" />
    <link rel="stylesheet" href="<?php bloginfo('stylesheet_directory'); ?>/css/helpers.css" type="text/css" media="screen" />
    
  <script type="text/javascript" src="<?php bloginfo('template_directory'); ?>/js/jquery-1.11.3.min.js"></script>

    <?php wp_head(); ?>
  </head>
  <body>
    <!--<div class="wrap">
      <header>
        <h1><a><?php bloginfo('name'); ?></a></h1>
        <h2><?php bloginfo('description'); ?></h2>
      </header>-->

    <div class="container main-content">
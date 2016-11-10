<?php
/**
* @package      Taxonomy Order Uninstaller
* @author       Shiva Poudel <info.shivapoudel@gmail.com>
* @link         http://css-gen.com
* @version      1.1
* @category 	Core
* @license      GPL3
* @copyright    Copyright (c) 2013, Shiva Poudel
* 
* Uninstalling Taxonomy Order Deletes Additional Table created by this Plugin in the Database.
*
*/

if( !defined('WP_UNINSTALL_PLUGIN') ) exit();

global $wpdb;

/**
 * Remove the 'term_order' Column from Database Created by this Plugin
 */
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "interface_taxonomy" );

?>
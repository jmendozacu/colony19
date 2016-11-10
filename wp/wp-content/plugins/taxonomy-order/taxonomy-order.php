<?php
/**
* Plugin Name: Taxonomy Order
* Plugin URI:  http://css-gen.com
* Description: Order your categories, tags or any taxonomy's terms with simple Ajax Drag and Drop Interface right from the standard terms list.
* Author:      Shiva Poudel
* Author URI:  http://css-gen.com
* Version:     1.1
*
* License: GPLv3
* Contributors: Shiva Poudel
* Tags: order, re-order, ordering, taxonomy, taxonomies, manage, ajax, drag-and-drop, admin
* Requires at least: 3.0+
* Tested up to: 3.6
* Stable tag: 1.1
* Donate link: http://css-gen.com/donate
*
* Copyright (C) 2013  Shiva Poudel  (email : info.shivapoudel@gmail.com)
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*
* @package      Taxonomy Order
* @author       Shiva Poudel <info.shivapoudel@gmail.com>
* @link         http://css-gen.com
* @version      1.1
* @category 	Core
* @license      GPLv3
* @copyright    Copyright (c) 2013, http://css-gen.com
* @tags         order, re-order, ordering, taxonomy, taxonomies, manage, ajax, drag-and-drop, admin
* @contributors Shiva Poudel
* @donate link: http://css-gen.com/donate
*
*/

/**
* OOP Class Loader or Constructor for Interface Taxonomy Order.
* This class *requires* PHP 5.3. Make sure you have it running.
*/
class Interface_Taxonomy_Order {
	/**
	 * Singleton Instance
	 * @var Interface_Taxonomy_Order
	 */
	private static $instance;

	/**
	 * Default _builtin Taxonomies
	 * @var array
	 */
	private static $taxonomies = array( 'post_tag', 'category' );

	/**
	 * As I have avoided the use of __FILE__ because I like to allow for the plugin to be outside of the plugins directory and symlinked as recommended in the WordPress Docs.
	 * If plugin is symlinked into the plugin directory __FILE__ will not work as needed, it will return the actual path to the plugin rather than the symlinked path.
	 * @var string $plugin_folder stores this plugin folder name
	 * @var string $plugin_dir will fetch this plugin directory
	 * @var string $plugin_url will fetch this plugin URI
	 * @var string $plugin_file will stores this plugin main file
	 */
	private static $plugin_folder;
	private static $plugin_dir;
	private static $plugin_url;
	private static $plugin_file;

	/**
	 * Private Constructor (Singleton)
	 */
	private function __construct() {
		/**
		 * Setup Plugins Paths
		 */
		self::$plugin_folder = 'Taxonomy-Order';
		self::$plugin_dir = WP_PLUGIN_DIR . '/' . self::$plugin_folder;
		self::$plugin_url = WP_PLUGIN_URL . '/' . self::$plugin_folder;
		self::$plugin_file = self::$plugin_dir . '/taxonomy-order.php';

		register_activation_hook( self::$plugin_file, array( $this, 'activation_hook' ) );

		add_action( 'init', array($this, 'interface_wpdbfix') );
		add_action( 'switch_blog', array($this, 'interface_wpdbfix') );

		add_action( 'plugin_loaded', array( $this, 'plugin_loaded' ),5 );
		add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ), 5 );
		
		add_filter( 'load-edit-tags.php', array( $this, 'contextual_help') );

		add_action( 'admin_init', array( $this, 'admin_init' ) );

		add_filter( 'terms_clauses', array( $this, 'terms_clauses') , 10, 3 );
		add_action( 'created_term', array($this, 'created_term') , 10, 3 );
		add_action( 'delete_term', array($this, 'delete_term') , 10, 3 );
	}

	/**
	 * Returns the singleton instance
	 * @return Interface_Taxonomy_Order
	 */
	public function instance() {
		if ( !isset( self::$instance ) ) {
			$class_name = __CLASS__;
			self::$instance = new $class_name;
		}
		return self::$instance;
	}

	/**
	 * Add Ordering Support to One or More Taxonomies
	 * @param string|array $taxonomy
	 */
	public static function add_taxonomy_support($taxonomy) {
		$taxonomies = (array)$taxonomy;
		self::$taxonomies = array_merge( self::$taxonomies, $taxonomies );
	}

	public static function remove_taxonomy_support($taxonomy) {
		$key = array_search( $taxonomy, self::$taxonomies );
		if ( false !== $key ) unset( self::$taxonomies[$key] );
	}

	public static function has_taxonomy_support($taxonomy) {
		if ( in_array($taxonomy, self::$taxonomies) ) return true;
		return false;
	}

	/**
	 * Checks PHP Version and create the needed database Table on plugin activation
	 */
	public function activation_hook() {
		// Checks the PHP version
		if ( version_compare( PHP_VERSION, '5.0.0', '<' ) ) {
			deactivate_plugins( basename( dirname( self::$plugin_file ) ) . '/' . basename( self::$plugin_file ) );
			wp_die( "Sorry, the Taxonomy Order Plugin requires PHP Version 5.0.0 or higher." );
		}

		// Creates the needed database table
		global $wpdb;

		$collate = '';
		if ( $wpdb->supports_collation() ) {
			if ( !empty( $wpdb->charser ) ) $collate  = "DEFAULT CHARACTER SET $wpdb->charset";
			if ( !empty( $wpdb->collate ) ) $collate .= " COLLATE $wpdb->collate";			
		}

		$sql = "CREATE TABLE IF NOT EXISTS ". $wpdb->prefix . "interface_taxonomy" ." (
				`meta_id` bigint(20) unsigned NOT NULL auto_increment,
				`term_id` bigint(20) unsigned NOT NULL default '0',
				`meta_key` varchar(255) default NULL,
				`meta_value` longtext,
				PRIMARY KEY (meta_id),
				KEY term_id (term_id),
				KEY meta_key (meta_key) ) $collate;";
		$wpdb->query($sql);
	}

	/**
	 * Sets our table name into wpdb
	 * Runs on the 'init' and 'switch_blog' action hooks
	 */
	public function interface_wpdbfix() {
		global $wpdb;
		$wpdb->termmeta = "{$wpdb->prefix}interface_taxonomy";
	}

	/**
	 * Filters default '_builtin' taxonomies supplied
	 * Runs on the 'plugins_loaded' action hook
	 */
	public function plugin_loaded() {
		self::$taxonomies = apply_filters( 'taxonomy-order-default', self::$taxonomies );
	}

	/**
	 * Filters default '_builtin' taxonomies supplied
	 * Runs on the 'after_setup_theme' action hook
	 */
	public function after_setup_theme() {
		self::$taxonomies = apply_filters( 'taxonomy-order', self::$taxonomies );
	}

	/**
	 * Adds the Contextual Help Tab for Easy Documentation
	 * Runs on the 'load-edit-tags.php' action hook
	 */
	public function contextual_help() {
		$screen = get_current_screen();

		$Taxonomy_Order = '<p>' . __( 'To reposition an item, simply drag and drop the row by "Clicking & Holding" it anywhere (i.e. outside the links and form controls) and moving it to its new position.', 'taxonomy_order' ) . '</p>';
		$Taxonomy_Order .= '<p>' . __( 'To keep things relatively simple, the current version only allows you to reposition items within their current tree / hierarchy (next to pages with the same parent). If you want to move an item into or out of a different part of the page tree, use the "Quick Edit" feature to change the parent.', 'taxonomy_order' ) . '</p>';

		$screen->add_help_tab( array(
			'id'		=> 'taxonomy_order_help_tab',
			'title'		=> __( 'Taxonomy Order' ),
			'content' 	=> $Taxonomy_Order,
		));
	}

	/**
	 * Init the admin
	 * Runs on the 'admin_init' action hook
	 */
	public function admin_init() {
		// Adds scripts and CSS
		add_action( 'admin_footer-edit-tags.php', array( $this, 'admin_enqueue_scripts' ), 10 );
		add_action( 'admin_print_styles-edit-tags.php', array( $this, 'admin_css' ), 1 );

		// Handles Httpr Drag and Drop Taxonomy Terms Ordering
		add_action( 'wp_ajax_interface_taxonomy', array( $this, 'interface_taxonomy_order' ) );
	}

	/**
	 * Register, Enqueue and Localize Scripts
	 * Runs on the 'admin_footer-edit-tags.php' action hook
	 */
	public function admin_enqueue_scripts() {
		if ( !isset( $_GET['taxonomy'] ) || !self::has_taxonomy_support( $_GET['taxonomy'] ) ) return;
		
		wp_register_script( 'taxonomy_order', self::$plugin_url . '/js/taxonomy-order.js', array( 'jquery-ui-sortable' ) );
		wp_enqueue_script( 'taxonomy_order' );
		wp_localize_script( 'taxonomy_order', 'taxonomies_order', array( 'taxonomy'=>$_GET['taxonomy'] ) );
		wp_print_scripts( 'taxonomy_order' );
	}

	/**
	 * Register and Enqueue CSS
	 * Runs on the 'admin_print_styles-edit-tags.php' action hook
	 */
	public function admin_css() {
		if ( !isset( $_GET['taxonomy'] ) || !self::has_taxonomy_support( $_GET['taxonomy'] ) ) return;
		
		wp_register_style( 'taxonomy_order', self::$plugin_url . '/css/style.css' );
		wp_enqueue_style( 'taxonomy_order' );
	}

	/**
	 * Handles Httpr Drag and Drop Taxonomy Terms Ordering
	 * Runs on the 'wp_ajax_interface_taxonomy' action hook
	 */
	public function interface_taxonomy_order() {
		global $wpdb;

		$id = (int) $_POST['id'];
		$next_id = isset( $_POST['nextid'] ) && (int) $_POST['nextid'] ? (int) $_POST['nextid'] : null;
		$taxonomy = isset( $_POST['taxonomy'] ) && $_POST['taxonomy'] ? $_POST['taxonomy'] : null;

		if ( !$id || !$term = get_term_by( 'id', $id, $taxonomy ) ) die(0);

		$this->place_term( $term, $taxonomy, $next_id );

		$children = get_terms( $taxonomy, "child_of=$id&menu_order=ASC&hide_empty=0" );

		if ( $term && sizeof( $children ) ) {
			'children';
			die;
		}
	}
		
	/**
	 * Add term ordering support to get_terms, set it as default
	 * It enables the support a 'menu_order' parameter to get_terms for the configured taxonomy.
	 * By default it is 'ASC'. It accepts 'DESC' too
	 * To disable it, set it ot false (or 0)
	 */
	public function terms_clauses( $clauses, $taxonomies, $args ) {
		global $wpdb;
		
		$taxonomies = (array)$taxonomies;

		if( sizeof( $taxonomies === 1 ) ) $taxonomy = array_shift( $taxonomies );
		else return $clauses;
		
		if( !$this->has_taxonomy_support($taxonomy) ) return $clauses;
		
		// Fields
		if ( strpos( 'COUNT(*)', $clauses['fields'] ) === false ) $clauses['fields'] .= ', tm.meta_key, tm.meta_value ';
	
		// Join
		$clauses['join'] .= " LEFT JOIN {$wpdb->termmeta} AS tm ON (t.term_id = tm.term_id AND tm.meta_key = 'order') ";
		
		// Order
		if( isset( $args['menu_order'] ) && !$args['menu_order'] ) return $clauses; // menu_order is false when not added Order Clause
		
		// Default to ASC
		if( !isset( $args['menu_order'] ) || !in_array( strtoupper( $args['menu_order'] ), array( 'ASC', 'DESC' ) ) ) $args['menu_order'] = 'ASC';
	
		$order = "ORDER BY CAST(tm.meta_value AS SIGNED) " . $args['menu_order'];
		
		if ( $clauses['orderby'] ) {
			$clauses['orderby'] = str_replace('ORDER BY', $order . ',', $clauses['orderby'] );
		}else{
			$clauses['orderby'] = $order;
		}

		return $clauses;
	}
	
	/**
	 * Reorder on Term Insertion
	 * @param int $term_id
	 */
	public function created_term( $term_id, $tt_id, $taxonomy ) {
		if ( !$this->has_taxonomy_support( $taxonomy ) ) return;

		$next_id = null;
		$term = get_term( $term_id, $taxonomy );

		// Fetch all the Siblings (Relative Ordering)
		$siblings = get_terms( $taxonomy, "parent={$term->parent}&menu_order=ASC&hide_empty=0" );

		foreach ( $siblings as $sibling ) {
			if ( $siblings->term_id == $term_id ) continue;
			$next_id = $sibling->term_id;
			break;
		}

		// Reorder
		$this->place_term( $term, $taxonomy, $next_id );
	}
	
	/**
	 * Delete Taxonomy's Terms Metas on Deletion
	 * @param int $term_id
	 */
	public function delete_term( $term_id, $tt_id, $taxonomy ) {
		if ( !$this->has_taxonomy_support( $taxonomy ) ) return;
		if ( !(int) $term_id ) return;
		delete_metadata( 'term', $term_id, 'order' );
	}
	
	/**
	 * Move a Taxonomy's term before the given element of its hierachy level
	 * @param object $the_term
	 * @param int $next_id the id of the next slibling element in save hierachy level
	 * @param int $index
	 * @param int $terms
	 */
	private function place_term( $the_term, $taxonomy, $next_id, $index=0, $terms=null ) {
		if( !$terms ) $terms = get_terms($taxonomy, 'menu_order=ASC&hide_empty=0&parent=0');
		if( empty( $terms ) ) return $index;
		
		$id	= $the_term->term_id;
		
		$term_in_level = false; // flag: is Our Term to Order in this Level of Terms
		
		foreach ( $terms as $term ) {
			// Our Term to Order, we Skip
			if( $term->term_id == $id ) { 
				$term_in_level = true;
				continue;
			}

			// nextid of Our Term to Order, Let's move Our Term Here
			if( null !== $next_id && $term->term_id == $next_id ) { 
				$index = $this->set_term_order( $id, $taxonomy, $index+1, true );
			}		
			
			// Set Order
			$index = $this->set_term_order( $term->term_id, $taxonomy, $index+1 );
			
			// If that Term has Children then Let's Walk through them
			$children = get_terms($taxonomy, "parent={$term->term_id}&menu_order=ASC&hide_empty=0");
			if( !empty($children) ) {
				$index = $this->place_term( $the_term, $taxonomy, $next_id, $index, $children );	
			}
		}
		
		// No nextid means Our Term is in Last Position
		if( $term_in_level && null === $next_id ) {
			$index = $this->set_term_order( $id, $taxonomy, $index+1, true );
		}

		return $index;	
	}
	
	/**
	 * Set the Sort Order of a Taxonomy's Term
	 * @param int $term_id
	 * @param int $index
	 * @param bool $recursive
	 */
	private function set_term_order( $term_id, $taxonomy, $index, $recursive=false ) {
		global $wpdb;
		$term_id = (int) $term_id;
		$index = (int) $index;
		
		update_metadata( 'term', $term_id, 'order', $index );
		
		if( !$recursive ) return $index;
		
		$children = get_terms( $taxonomy, "parent=$term_id&menu_order=ASC&hide_empty=0" );
	
		foreach ( $children as $term ) {
			$index ++;
			$index = $this->set_term_order( $term->term_id, $taxonomy, $index, true );	
		}

		return $index;
	}
}

$Interface_Taxonomy_Order = Interface_Taxonomy_Order::instance();

if( !function_exists('add_interface_taxonomy_order') ) {
	function add_interface_taxonomy_order($taxonomy) {
		Interface_Taxonomy_Order::add_taxonomy_support($taxonomy);
	}
}

if( !function_exists('remove_interface_taxonomy_order') ) {
	function remove_interface_taxonomy_order($taxonomy) {
		Interface_Taxonomy_Order::remove_taxonomy_support($taxonomy);
	}
}

if( !function_exists('has_interface_taxonomy_order') ) {
	function has_interface_taxonomy_order($taxonomy) {
		return Interface_Taxonomy_Order::has_taxonomy_support($taxonomy);
	}
}

?>